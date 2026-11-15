<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Periode akademik global (database pusat): satu baris per semester. */
#[Guarded(['id'])]
class AcademicPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
            'is_admission' => 'boolean',
        ];
    }

    // Periode yang sedang berjalan.
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // Periode yang dibuka untuk pendaftaran siswa baru (PPDB): hanya Semester 1 tiap tahun ajaran.
    public function scopeAdmission(Builder $query): Builder
    {
        return $query->where('is_admission', true)->where('semester', '1');
    }

    // PPDB hanya bisa dibuka/ditutup pada Semester 1.
    public function canAdmit(): bool
    {
        return $this->semester === '1';
    }

    // Terbaru di atas: tahun ajaran menurun, semester menaik.
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('start_year')->orderBy('semester');
    }

    // Contoh: "2026/2027 - Semester 1".
    public function label(): string
    {
        return $this->name . ' - Semester ' . $this->semester;
    }
}