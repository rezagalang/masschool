<?php

use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\Kesiswaan\RegistrationProofController;
use App\Http\Controllers\ModuleSelectionController;
use App\Http\Controllers\UnitSelectionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.auth.login')->name('home');

// Sebelum route {unit} agar /settings tidak tertangkap.
require __DIR__ . '/settings.php';

// Halaman publik per unit (tanpa login): /{kode-unit}/ppdb.
Route::middleware('unit.public')
    ->prefix('{unit}')
    ->where(['unit' => '[a-z0-9]+(-[a-z0-9]+)*'])
    ->group(function () {
        Route::livewire('ppdb', 'pages::ppdb.register')->name('ppdb.register');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    // Entry point pasca-login: Kepsek pilih unit, role lain langsung ke unitnya.
    Route::get('dashboard', [UnitSelectionController::class, 'home'])->name('dashboard');

    // Khusus Kepsek: daftar semua unit.
    Route::get('pilih-unit', [UnitSelectionController::class, 'index'])->name('units.select');

    // Pengaturan aplikasi (global): hanya Kepsek.
    Route::middleware('headmaster')
        ->prefix('pengaturan')
        ->name('app-settings.')
        ->group(function () {
            Route::redirect('/', '/pengaturan/unit');
            Route::livewire('unit', 'pages::admin.app-settings.units')->name('units');
            Route::get('modul', [AppSettingsController::class, 'modules'])->name('modules');

            // Tahun ajaran (global).
            Route::livewire('tahun-ajaran', 'pages::admin.app-settings.academic-periods')->name('academic-periods');
        });

    // Semua halaman unit: /{kode-unit}.
    Route::middleware('unit.access')
        ->prefix('{unit}')
        ->where([
            'unit' => '[a-z0-9]+(-[a-z0-9]+)*',
            'module' => implode('|', array_keys(config('modules'))),
        ])
        ->group(function () {
            Route::get('/', [ModuleSelectionController::class, 'index'])->name('modules.select');
            Route::get('{module}/dashboard', [ModuleSelectionController::class, 'dashboard'])->name('unit.dashboard');

            // Modul Kesiswaan.
            Route::prefix('kesiswaan')->name('kesiswaan.')->group(function () {
                Route::livewire('siswa', 'pages::kesiswaan.students.index')->name('students.index');
                Route::livewire('siswa/tambah', 'pages::kesiswaan.students.form')->name('students.create');
                Route::livewire('siswa/{student}', 'pages::kesiswaan.students.show')
                    ->whereNumber('student')->name('students.show');
                Route::livewire('siswa/{student}/edit', 'pages::kesiswaan.students.form')
                    ->whereNumber('student')->name('students.edit');

                // Master kelas.
                Route::livewire('kelas', 'pages::kesiswaan.classes.index')->name('classes.index');

                // Pengelompokan siswa ke kelas per tahun ajaran.
                Route::livewire('pengelompokan', 'pages::kesiswaan.groupings.index')->name('groupings.index');

                // Aktivasi kartu RFID siswa.
                Route::livewire('rfid', 'pages::kesiswaan.rfid.index')->name('rfid.index');

                // Calon siswa.
                Route::livewire('calon-siswa', 'pages::kesiswaan.registrations.index')->name('registrations.index');
                Route::get('calon-siswa/{registration}/bukti', RegistrationProofController::class)
                    ->whereNumber('registration')->name('registrations.proof');
            });
        });
});