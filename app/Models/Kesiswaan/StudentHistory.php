<?php

namespace App\Models\Kesiswaan;

use App\Models\AcademicPeriod;
use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penempatan siswa ke kelas pada satu periode akademik (tabel `student_histories`, database unit).
 *
 * `academic_period_id` merujuk ke `academic_periods` di database PUSAT, jadi kolom ini
 * tidak memakai foreign key (constraint tidak bisa lintas database). Relasi `academicPeriod()`
 * tetap bisa dipakai karena Eloquent menjalankan query terpisah di koneksi masing-masing,
 * tetapi JANGAN di-join langsung dengan tabel pusat.
 */
#[Guarded(['id'])]
class StudentHistory extends UnitModel
{
    protected $table = 'student_histories';

    protected function casts(): array
    {
        return [
            'academic_period_id' => 'integer',
            'student_id' => 'integer',
            'class_id' => 'integer',
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

    // Database pusat: dimuat lewat query terpisah (aman untuk eager loading).
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function scopeForPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('academic_period_id', $periodId);
    }
}