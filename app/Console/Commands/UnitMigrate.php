<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Support\UnitDatabase;
use Database\Seeders\StudentSeeder;
use Illuminate\Console\Command;

class UnitMigrate extends Command
{
    protected $signature = 'unit:migrate
        {--unit=* : Kode unit (mis. tk-sda). Kosong = semua unit}
        {--rollback : Rollback batch terakhir, bukan migrate}
        {--fresh : Hapus semua tabel di database unit lalu migrate ulang}
        {--seed : Isi data contoh siswa (StudentSeeder) setelah migrate}
        {--force : Jalankan tanpa konfirmasi di production}';

    protected $description = 'Migrate skema modul (database/migrations/unit) ke database tiap unit';

    public function handle(): int
    {
        $units = Unit::query()
            ->when($this->option('unit'), fn ($q, $codes) => $q->whereIn('code', $codes))
            ->orderBy('id')
            ->get();

        if ($units->isEmpty()) {
            $this->components->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        if ($this->option('fresh') && $this->option('rollback')) {
            $this->components->error('Opsi --fresh dan --rollback tidak bisa dipakai bersamaan.');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($units as $unit) {
            $name = UnitDatabase::name($unit);
            $this->components->info("{$unit->code} → {$name}");

            if (! UnitDatabase::exists($unit)) {
                $this->components->error(
                    "Database belum ada. Buat dulu di MySQL: CREATE DATABASE `{$name}` "
                    . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; '
                    . 'lalu beri hak akses ke user aplikasi.'
                );
                $failed++;

                continue;
            }

            UnitDatabase::connect($unit);

            $command = match (true) {
                (bool) $this->option('fresh') => 'migrate:fresh',
                (bool) $this->option('rollback') => 'migrate:rollback',
                default => 'migrate',
            };

            $exit = $this->call($command, [
                '--database' => UnitDatabase::CONNECTION,
                '--path' => 'database/migrations/unit',
                '--force' => (bool) $this->option('force'),
            ]);

            if ($exit !== self::SUCCESS) {
                $failed++;
            }
        }

        if ($failed === 0 && $this->option('seed')) {
            $this->call('db:seed', [
                '--class' => StudentSeeder::class,
                '--force' => (bool) $this->option('force'),
            ]);
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}