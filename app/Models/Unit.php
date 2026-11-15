<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Guarded(['id'])]
class Unit extends Model
{
    // URL memakai kode unit.
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'unit_users',
            'unit_id',
            'user_id'
        )->withTimestamps();
    }
}