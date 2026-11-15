<?php

namespace App\Models\Kesiswaan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;

#[Guarded(['id'])]
class Student extends UnitModel
{
    protected function casts(): array
    {
        return [
            'a_ttl2' => 'date',
            'e_ttl2' => 'date',
            'f_ttl2' => 'date',
            'g_ttl2' => 'date',
            'tglentry' => 'date',
        ];
    }

    // Pencarian NIS, NISN, NIK, nama, atau email.
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%' . $term . '%';

            $q->where('nis', 'like', $like)
                ->orWhere('nisn', 'like', $like)
                ->orWhere('nik', 'like', $like)
                ->orWhere('a_namalengkap', 'like', $like)
                ->orWhere('a_namapanggilan', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function isActive(): bool
    {
        return (string) $this->aktif === '1';
    }
}