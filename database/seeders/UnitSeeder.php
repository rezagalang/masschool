<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'unit' => 'TK SDA',
                'code' => 'tk-sda',
                'pic' => null,
                'name' => 'PG/TK TK Islam Kreatif Mutiara Anak Sholeh Sidoarjo',
                'address' => 'Perum Graha Mutiara Blok A1 No 2-4 Kebonagung Sukodono',
                'city' => 'Sidoarjo',
                'short_unit' => 'PG/TK',
                'headmaster' => 'YUSLIVIANUR FIRDHA, ST, S.Si, S.Pd AUD',
                'finance' => 'RIZA FITRIA AYU WANDIRA',
                'chairman' => 'Ir.H.Akhmad Dawawin',
                'email_setting' => 20,
                'is_active' => 1,
            ],
            [
                'unit' => 'SD SDA',
                'code' => 'sd-sda',
                'pic' => null,
                'name' => 'SDI Kreatif Mutiara Anak Sholeh Sidoarjo',
                'address' => 'Jl. Pekarungan no.5 Sukodono, Sidoarjo, JawaTimur',
                'city' => 'Sidoarjo',
                'short_unit' => 'SD',
                'headmaster' => 'Sifa Fauziah Permatasari, S.Pd',
                'finance' => 'Niken Dewi Hestari, S.Pd',
                'chairman' => 'Ir.H.Akhmad Dawawin',
                'email_setting' => 20,
                'is_active' => 1,
            ],
            [
                'unit' => 'TK MJKT',
                'code' => 'tk-mjkt',
                'pic' => null,
                'name' => 'PG/TK Kreatif Mutiara Anak Sholeh Mojokerto',
                'address' => 'Jl. Raya Brangkal 38 Kedungmaling, Sooko, Mojokerto',
                'city' => 'Mojokerto',
                'short_unit' => 'PG/TK',
                'headmaster' => '-',
                'finance' => '-',
                'chairman' => 'Ir.H.Akhmad Dawawin',
                'email_setting' => 20,
                'is_active' => 1,
            ],
            [
                'unit' => 'SD MJKT',
                'code' => 'sd-mjkt',
                'pic' => null,
                'name' => 'SDI Kreatif Mutiara Anak Sholeh Mojokerto',
                'address' => 'Jl. Raya Brangkal 38 Kedungmaling, Sooko, Mojokerto',
                'city' => 'Mojokerto',
                'short_unit' => 'SD',
                'headmaster' => 'Fauzi Abdul Basith, S.Pd',
                'finance' => 'Septi Cahyaningtyas, S.Pd',
                'chairman' => 'Ir.H.Akhmad Dawawin',
                'email_setting' => 20,
                'is_active' => 1,
            ],
            [
                'unit' => 'SMP SDA',
                'code' => 'smp-sda',
                'pic' => null,
                'name' => 'SMP Islam Kreatif Mutiara Anak Sholeh',
                'address' => 'Jl. Sarirogo No.9, Sarirogo, Kec. Sidoarjo, Kabupaten Sidoarjo, Jawa Timur',
                'city' => 'Sidoarjo',
                'short_unit' => 'SMP',
                'headmaster' => 'Edy Rahmawan, ST',
                'finance' => 'Mega Sagita Nanda Putri, S.Pd',
                'chairman' => 'Ir.H.Akhmad Dawawin',
                'email_setting' => 20,
                'is_active' => 1,
            ],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }
    }
}