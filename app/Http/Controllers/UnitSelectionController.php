<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitSelectionController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Kepala Sekolah')) {
            return redirect()->route('units.select');
        }

        $unit = $user->units()->where('units.is_active', true)->orderBy('units.id')->first();

        abort_unless($unit, 403, 'Akun Anda belum terhubung ke unit aktif.');

        return redirect()->route('modules.select', $unit);
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasRole('Kepala Sekolah')) {
            return redirect()->route('dashboard');
        }

        return view('pages.admin.units.select', [
            'units' => Unit::query()->where('is_active', true)->orderBy('id')->get(),
            'currentUnitId' => $request->session()->get('unit_id'),
        ]);
    }
}