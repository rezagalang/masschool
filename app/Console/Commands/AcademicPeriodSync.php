<?php

namespace App\Console\Commands;

use App\Support\AcademicYear;
use Illuminate\Console\Command;

class AcademicPeriodSync extends Command
{
    protected $signature = 'academic:sync';

    protected $description = 'Aktifkan periode akademik yang rentang tanggalnya mencakup hari ini';

    public function handle(): int
    {
        $report = AcademicYear::sync();

        if ($report['activated']) {
            $this->components->info("Periode aktif: {$report['activated']}");
        }

        $active = AcademicYear::active();

        $this->line('Aktif sekarang: ' . ($active?->label() ?? '-'));
        $this->line('PPDB sekarang : ' . (implode(', ', AcademicYear::admissionNames()) ?: 'ditutup'));

        return self::SUCCESS;
    }
}