<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Support\AcademicYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AcademicPeriodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(2025, 2028) as $year) {
            $semesters = [
                '1' => [CarbonImmutable::create($year, 7, 1), CarbonImmutable::create($year, 12, 31)],
                '2' => [CarbonImmutable::create($year + 1, 1, 1), CarbonImmutable::create($year + 1, 6, 30)],
            ];

            foreach ($semesters as $semester => [$start, $end]) {
                AcademicPeriod::query()->firstOrCreate(
                    ['start_year' => $year, 'semester' => $semester],
                    [
                        'name' => $year . '/' . ($year + 1),
                        'end_year' => $year + 1,
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                    ]
                );
            }
        }

        // Aktifkan periode yang mencakup hari ini.
        AcademicYear::sync();

        $this->command?->info(
            'Periode akademik: aktif ' . (AcademicYear::active()?->label() ?? '-')
            . ', PPDB ' . (implode(', ', AcademicYear::admissionNames()) ?: 'ditutup')
        );
    }
}