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

    // Logo unit: assets/images/units/{kode-unit}.(png|webp|svg|jpeg|jpg).
    public function logoUrl(): ?string
    {
        foreach (["png", "webp", "svg", "jpeg", "jpg"] as $ext) {
            $path = "assets/images/units/{$this->code}.{$ext}";

            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return null;
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