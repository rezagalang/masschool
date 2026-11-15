<?php

namespace Database\Seeders;

use App\Models\UnitUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Reza',
            'username' => 'reza',
            'email' => 'reza@dev.com',
            'password' => bcrypt('reza1234'),
            'email_verified_at' => now(),
        ])->assignRole('Kepala Sekolah');

        User::create([
            'name' => 'Umum',
            'username' => 'umum',
            'email' => 'umum@dev.com',
            'password' => bcrypt('umum1234'),
            'email_verified_at' => now(),
        ])->assignRole('Umum');

        UnitUser::create([
            'user_id' => 2,
            'unit_id' => 1,
        ]);
    }
}