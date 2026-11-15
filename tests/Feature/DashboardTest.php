<?php

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('kepala sekolah is redirected to the unit selection page', function () {
    $user = User::factory()->create();
    $user->assignRole('Kepala Sekolah');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('units.select'));
});

test('users with an active unit are redirected to their module selection page', function () {
    $unit = Unit::create(['unit' => 'SD SDA', 'code' => 'sd-sda', 'is_active' => true]);

    $user = User::factory()->create();
    $user->units()->attach($unit);
    $user->assignRole('Umum');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('modules.select', $unit));
});

test('users without an active unit are forbidden from the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('Umum');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('kepala sekolah can visit the unit selection and module pages', function () {
    $unit = Unit::create(['unit' => 'SD SDA', 'code' => 'sd-sda', 'is_active' => true]);

    $user = User::factory()->create();
    $user->assignRole('Kepala Sekolah');
    $this->actingAs($user);

    $this->get(route('units.select'))->assertOk();
    $this->get(route('modules.select', $unit))->assertOk();
    $this->get(route('unit.dashboard', ['unit' => $unit, 'module' => 'kesiswaan']))->assertOk();
    $this->get(route('app-settings.units'))->assertOk();
    $this->get(route('app-settings.modules'))->assertOk();
});

test('users cannot open a unit that is not theirs', function () {
    $own = Unit::create(['unit' => 'SD SDA', 'code' => 'sd-sda', 'is_active' => true]);
    $other = Unit::create(['unit' => 'SMP SDA', 'code' => 'smp-sda', 'is_active' => true]);

    $user = User::factory()->create();
    $user->units()->attach($own);
    $user->assignRole('Umum');
    $this->actingAs($user);

    $this->get(route('modules.select', $own))->assertOk();
    $this->get(route('modules.select', $other))->assertForbidden();
});