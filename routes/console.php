<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ganti periode aktif & buat tahun ajaran baru otomatis (butuh cron: php artisan schedule:run).
Schedule::command('academic:sync')->dailyAt('00:10')->withoutOverlapping();