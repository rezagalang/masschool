<?php

namespace App\Models\Kesiswaan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Master kelas per unit (tabel `classes`). "Class" tidak bisa dipakai sebagai nama class PHP. */
#[Guarded(['id'])]
class Classroom extends UnitModel
{
    protected $table = 'classes';

    protected function casts(): array
    {
        return [
            'nourut' => 'integer',
        ];
    }

    // Penempatan siswa di kelas ini (semua periode).
    public function histories(): HasMany
    {
        return $this->hasMany(StudentHistory::class, 'class_id');
    }

    // Pencarian nama kelas atau jenjang.
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('kelas', 'like', $like)
                ->orWhere('jenjang', 'like', $like);
        });
    }
}