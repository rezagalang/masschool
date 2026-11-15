<?php

namespace App\Support;

use App\Models\AcademicPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Auto-aktif berdasarkan rentang tanggal hari ini (`sync()`). */
class AcademicYear
{
    /** Cadangan awal tahun (MM-DD) jika data periode kosong. */
    public const DEFAULT_SEM1_START = '07-01';

    /* Baca */

    /** Periode aktif (auto-sync berdasarkan tanggal). */
    public static function active(): ?AcademicPeriod
    {
        $active = AcademicPeriod::query()->active()->first();

        if (! $active || ($active->end_date && $active->end_date->lt(self::today()))) {
            self::sync();

            $active = AcademicPeriod::query()->active()->first();
        }

        return $active;
    }

    public static function hasAdmission(): bool
    {
        return AcademicPeriod::query()->admission()->exists();
    }

    // Nama tahun ajaran aktif.
    public static function activeName(): string
    {
        return self::active()?->name ?? self::yearName(self::startYearFor(self::today()));
    }

    // Nama tahun ajaran yang dibuka PPDB unik.
    public static function admissionNames(): array
    {
        return AcademicPeriod::query()
            ->admission()
            ->select('name')
            ->distinct()
            ->orderByDesc('name')
            ->pluck('name')
            ->all();
    }

    // Opsi dropdown tahun ajaran PPDB.
    public static function admissionOptions(): array
    {
        return array_combine($names = self::admissionNames(), $names);
    }

    /** Pilihan awal form: tahun ajaran PPDB terdekat (berjalan/akan datang) atau terbaru. */
    public static function defaultAdmissionName(): ?string
    {
        $names = self::admissionNames();

        if ($names === []) {
            return null;
        }

        $active = self::activeName();
        $upcoming = array_values(array_filter($names, fn (string $name) => $name >= $active));

        return $upcoming !== [] ? end($upcoming) : $names[0];
    }

    // Opsi dropdown tahun ajaran (terbaru di atas).
    public static function options(): array
    {
        $names = self::names();

        if ($names->isEmpty()) {
            $fallback = self::yearName(self::startYearFor(self::today()));

            return [$fallback => $fallback];
        }

        return $names->mapWithKeys(fn (string $name) => [$name => $name])->all();
    }

    public static function yearName(int $startYear): string
    {
        return $startYear . '/' . ($startYear + 1);
    }

    /* Aktivasi otomatis */

    /**
     * Mengaktifkan periode yang mencakup hari ini (tanpa membuat baru/mengubah PPDB).
     * @return array{activated: ?string}
     */
    public static function sync(?CarbonInterface $today = null): array
    {
        $today ??= self::today();

        return DB::transaction(function () use ($today) {
            $report = ['activated' => null];

            $current = AcademicPeriod::query()
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderByDesc('start_date')
                ->first();

            if ($current && ! $current->is_active) {
                self::setActive($current);
                $report['activated'] = $current->label();
            }

            return $report;
        });
    }

    /* Ubah status */

    // Jadikan satu-satunya periode aktif.
    public static function setActive(AcademicPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            AcademicPeriod::query()
                ->where('id', '!=', $period->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $period->update(['is_active' => true]);
        });
    }

    // Buka/tutup PPDB tahun ajaran ini (hanya Semester 1).
    public static function toggleAdmission(AcademicPeriod $period): bool
    {
        if (! $period->canAdmit()) {
            return false;
        }

        $period->update(['is_admission' => ! $period->is_admission]);

        return $period->is_admission;
    }

    /* Internal */

    // Tahun mulai tahun ajaran yang mencakup tanggal ini.
    public static function startYearFor(CarbonInterface $date): int
    {
        $covering = AcademicPeriod::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();

        if ($covering) {
            return $covering->start_year;
        }

        $sem1Start = self::DEFAULT_SEM1_START;

        return $date->format('m-d') >= $sem1Start ? (int) $date->format('Y') : (int) $date->format('Y') - 1;
    }

    protected static function names()
    {
        return AcademicPeriod::query()->select('name')->distinct()->orderByDesc('name')->pluck('name');
    }

    protected static function today(): CarbonInterface
    {
        return now()->startOfDay();
    }
}