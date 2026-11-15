<?php

namespace App\Models\Kesiswaan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;

#[Guarded(['id'])]
class Registration extends UnitModel
{
    protected function casts(): array
    {
        return [
            'a_ttl2' => 'date',
            'e_ttl2' => 'date',
            'f_ttl2' => 'date',
            'g_ttl2' => 'date',
            'tglentry' => 'date',
            'status_daftar' => 'boolean',
        ];
    }

    // Pencarian no. pendaftaran, nama, NIK, atau email.
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%' . $term . '%';

            $q->where('no_pendaftaran', 'like', $like)
                ->orWhere('a_namalengkap', 'like', $like)
                ->orWhere('nik', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function isPending(): bool
    {
        return ! $this->status_daftar;
    }
}