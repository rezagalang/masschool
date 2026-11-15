<?php

namespace App\Http\Middleware;

use App\Models\Unit;
use App\Support\UnitDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnitAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $unit = $request->route('unit');

        // Pada request Livewire, parameter route bisa berupa string kode.
        if (is_string($unit)) {
            $unit = Unit::where('code', $unit)->first();
        }

        abort_unless($unit instanceof Unit && $unit->is_active, 404);

        $user = $request->user();

        if (! $user->hasRole('Kepala Sekolah')) {
            abort_unless($user->units()->whereKey($unit->id)->exists(), 403);
        }

        // Arahkan koneksi 'unit' ke database unit ini.
        UnitDatabase::connect($unit);

        // Ingat unit terakhir.
        $request->session()->put('unit_id', $unit->id);

        return $next($request);
    }
}