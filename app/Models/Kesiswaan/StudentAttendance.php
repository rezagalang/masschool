<?php

namespace App\Models\Kesiswaan;

use App\Models\AcademicPeriod;
use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Absensi harian siswa per tanggal. */
#[Guarded(['id'])]
class StudentAttendance extends UnitModel
{
    protected function casts(): array
    {
        return [
            'academic_period_id' => 'integer',
            'student_id' => 'integer',
            'class_id' => 'integer',
            'tgl_absen' => 'date:Y-m-d',
            'terlambat' => 'boolean',
            'ijin_pulang' => 'boolean',
            'sakit' => 'boolean',
            'ijin_tidakmasuk' => 'boolean',
            'alpha' => 'boolean',
            'hadir' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    // Database pusat: dimuat lewat query terpisah.
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->where('tgl_absen', $date);
    }

    // Status sebagai satu nilai.
    public function status(): string
    {
        return match (true) {
            $this->sakit => 'sakit',
            $this->ijin_tidakmasuk => 'izin',
            $this->alpha => 'alpha',
            $this->terlambat => 'terlambat',
            $this->ijin_pulang => 'izin_pulang',
            default => 'hadir',
        };
    }
}