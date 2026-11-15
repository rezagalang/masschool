<?php

namespace App\Support\Kesiswaan;

use Illuminate\Support\Carbon;

/** Field form PPDB (sinkron StudentFields; tanpa NIS, tambah bukti transfer). */
class RegistrationFields
{
    public const PROOF_MAX_KB = 3072;

    public const PROOF_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public static function tabs(): array
    {
        $tabs = StudentFields::tabs();

        unset($tabs['data_diri']['fields']['nis']);

        $tabs['lampiran'] = [
            'label' => 'Lampiran',
            'icon' => 'paper-clip',
            'description' => 'Unggah bukti transfer pembayaran pendaftaran.',
            'fields' => [],
        ];

        return $tabs;
    }

    /** Semua field form dalam satu array datar: nama kolom => spec. */
    public static function fields(): array
    {
        return collect(self::tabs())->flatMap(fn ($tab) => $tab['fields'])->all();
    }

    public static function defaults(): array
    {
        $defaults = StudentFields::defaults();

        unset($defaults['nis']);

        return $defaults;
    }

    /** Aturan validasi: aturan siswa (tanpa NIS) + file bukti transfer ($proof). */
    public static function rules(): array
    {
        $rules = StudentFields::rules();

        unset($rules['form.nis']);

        $rules['proof'] = [
            'required',
            'file',
            'mimes:' . implode(',', self::PROOF_MIMES),
            'max:' . self::PROOF_MAX_KB,
        ];

        return $rules;
    }

    public static function attributes(): array
    {
        $attributes = StudentFields::attributes();

        unset($attributes['form.nis']);

        return $attributes + ['proof' => 'Bukti Transfer'];
    }

    /** Ubah input form jadi data siap simpan: string kosong -> null. */
    public static function normalize(array $form): array
    {
        $data = StudentFields::normalize($form);

        unset($data['nis']);

        return $data;
    }

    /** Data pendaftaran siap tampil untuk admin (label => nilai, format select/tanggal, skip kosong). */
    public static function display(array $data): array
    {
        $sections = [];

        foreach (self::tabs() as $tab) {
            $rows = [];

            foreach ($tab['fields'] as $name => $spec) {
                $value = $data[$name] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                if ($spec['type'] === 'select') {
                    $value = $spec['options'][$value] ?? $value;
                } elseif ($spec['type'] === 'date') {
                    $value = Carbon::parse($value)->format('d/m/Y');
                }

                $rows[$spec['label']] = $value;
            }

            if ($rows !== []) {
                $sections[] = ['label' => $tab['label'], 'icon' => $tab['icon'] ?? null, 'rows' => $rows];
            }
        }

        return $sections;
    }
}