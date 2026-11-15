<?php

namespace App\Support\DataTable;

use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use DateTimeInterface;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export hasil query ke CSV / XLSX / PDF.
 * - CSV  : native PHP (streaming, tanpa package).
 * - XLSX : openspout/openspout (streaming, hemat memori).
 * - PDF  : barryvdh/laravel-dompdf (dimuat penuh ke memori; batasi jumlah baris).
 */
class DataTableExporter
{
    private const MIME = [
        'csv' => 'text/csv; charset=UTF-8',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf',
    ];

    public static function download(
        string $format,
        Builder $query,
        array $columns,
        string $filename,
        string $title = '',
        array $options = [],
    ): StreamedResponse {
        $columns = array_filter($columns, fn ($column) => ($column['export'] ?? true) !== false);
        $name = Str::slug($filename, '-') . '.' . $format;

        $callback = match ($format) {
            'csv' => self::csv($query, $columns, $options['delimiter'] ?? ','),
            'xlsx' => self::xlsx($query, $columns),
            'pdf' => self::pdf($query, $columns, $title, $options['orientation'] ?? 'landscape'),
        };

        return response()->streamDownload($callback, $name, ['Content-Type' => self::MIME[$format]]);
    }

    private static function csv(Builder $query, array $columns, string $delimiter): Closure
    {
        return function () use ($query, $columns, $delimiter) {
            @set_time_limit(120);

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($out, self::headings($columns), $delimiter);

            foreach (self::rows($query, $columns) as $row) {
                // Cegah CSV/formula injection saat dibuka di Excel.
                $row = array_map(
                    fn ($v) => is_string($v) && $v !== '' && ! is_numeric($v) && str_contains("=+-@\t\r", $v[0])
                        ? "'" . $v
                        : $v,
                    $row,
                );

                fputcsv($out, $row, $delimiter);
            }

            fclose($out);
        };
    }

    private static function xlsx(Builder $query, array $columns): Closure
    {
        return function () use ($query, $columns) {
            @set_time_limit(120);

            // openspout menulis ZIP, jadi tulis ke file sementara lalu kirim.
            $tmp = tempnam(sys_get_temp_dir(), 'dt_');

            try {
                $writer = new Writer();
                $writer->openToFile($tmp);

                $writer->addRow(Row::fromValuesWithStyle(self::headings($columns), self::boldStyle()));

                foreach (self::rows($query, $columns) as $row) {
                    $writer->addRow(Row::fromValues($row));
                }

                $writer->close();

                readfile($tmp);
            } finally {
                @unlink($tmp);
            }
        };
    }

    private static function pdf(Builder $query, array $columns, string $title, string $orientation): Closure
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $rows = iterator_to_array(self::rows($query, $columns), false);

        $binary = Pdf::loadView('exports.datatable-pdf', [
            'title' => $title,
            'headings' => self::headings($columns),
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', $orientation)->output();

        return function () use ($binary) {
            echo $binary;
        };
    }

    private static function headings(array $columns): array
    {
        return array_values(array_map(fn ($column) => (string) $column['label'], $columns));
    }

    // Baca per 500 baris agar memori tetap kecil.
    private static function rows(Builder $query, array $columns): Generator
    {
        foreach ($query->lazy(500) as $model) {
            $row = [];

            foreach ($columns as $key => $column) {
                $value = match (true) {
                    ($column['export'] ?? null) instanceof Closure => ($column['export'])($model),
                    ($column['badge'] ?? null) instanceof Closure => ($column['badge'])($model)['label'] ?? '',
                    ($column['value'] ?? null) instanceof Closure => ($column['value'])($model),
                    default => data_get($model, $key),
                };

                $row[] = self::normalize($value);
            }

            yield $row;
        }
    }

    private static function normalize(mixed $value): string|int|float
    {
        return match (true) {
            $value === null => '',
            is_int($value), is_float($value) => $value,
            is_bool($value) => $value ? __('Ya') : __('Tidak'),
            $value instanceof DateTimeInterface => $value->format(
                $value->format('H:i:s') === '00:00:00' ? 'Y-m-d' : 'Y-m-d H:i'
            ),
            $value instanceof BackedEnum => (string) $value->value,
            is_array($value) => implode(', ', array_map('strval', $value)),
            default => (string) $value,
        };
    }

    private static function boldStyle(): Style
    {
        $style = new Style();

        if (method_exists($style, 'withFontBold')) {
            return $style->withFontBold(true);
        }

        /** @disregard P1013 (hanya ada di openspout v4) */
        return $style->setFontBold();
    }
}