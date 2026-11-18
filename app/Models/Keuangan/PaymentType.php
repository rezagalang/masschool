<?php

namespace App\Models\Keuangan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Builder;

/** Master jenis pembayaran per unit. */
#[Guarded(['id'])]
class PaymentType extends UnitModel
{
    protected $table = 'payment_types';

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('nama', 'like', $like)
                ->orWhere('keterangan', 'like', $like)
                ->orWhere('alias', 'like', $like);
        });
    }
}