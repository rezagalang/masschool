<?php

namespace App\Http\Middleware;

use App\Models\Unit;
use App\Support\UnitDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Halaman publik per unit (tanpa login): validasi unit & set koneksi DB. */
class EnsurePublicUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $unit = $request->route('unit');

        // Pada request Livewire, parameter route bisa berupa string kode.
        if (is_string($unit)) {
            $unit = Unit::where('code', $unit)->first();
        }

        abort_unless($unit instanceof Unit && $unit->is_active, 404);

        UnitDatabase::connect($unit);

        return $next($request);
    }
}