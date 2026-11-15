<?php

namespace App\Support;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitDatabase
{
    // Nama koneksi dinamis untuk database unit aktif.
    public const CONNECTION = 'unit';

    // Nama database unit: prefix + kode unit (mis. masschool_tk-sda).
    public static function name(Unit $unit): string
    {
        return config('database.unit_prefix') . $unit->code;
    }

    // Arahkan koneksi 'unit' ke database milik unit ini.
    public static function connect(Unit $unit): void
    {
        $config = config('database.connections.' . config('database.default'));
        $name = self::name($unit);

        unset($config['url']);
        $config['database'] = $config['driver'] === 'sqlite'
            ? database_path($name . '.sqlite')
            : $name;

        config(['database.connections.' . self::CONNECTION => $config]);

        DB::purge(self::CONNECTION);
    }

    // Cek apakah database unit sudah dibuat (dicek lewat koneksi pusat).
    public static function exists(Unit $unit): bool
    {
        $name = self::name($unit);

        if (config('database.connections.' . config('database.default') . '.driver') === 'sqlite') {
            return file_exists(database_path($name . '.sqlite'));
        }

        return DB::select(
            'select 1 from information_schema.schemata where schema_name = ?',
            [$name]
        ) !== [];
    }
}