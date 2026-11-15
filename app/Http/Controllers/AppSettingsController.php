<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function units(Request $request): View
    {
        return view('pages.admin.app-settings.units', [
            'units' => Unit::query()->orderBy('id')->get(),
            'backUrl' => $this->backUrl($request),
        ]);
    }

    public function toggleUnit(Unit $unit): RedirectResponse
    {
        if ($unit->is_active && Unit::query()->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Minimal satu unit harus tetap aktif.');
        }

        $unit->update(['is_active' => ! $unit->is_active]);

        return back()->with('status', "Unit {$unit->unit} ".($unit->is_active ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function modules(Request $request): View
    {
        return view('pages.admin.app-settings.modules', [
            'modules' => config('modules'),
            'backUrl' => $this->backUrl($request),
        ]);
    }

    private function backUrl(Request $request): string
    {
        $previous = url()->previous();
        $path = parse_url($previous, PHP_URL_PATH) ?: '/';

        if ($path !== '/' && ! str_starts_with($path, '/pengaturan')) {
            $request->session()->put('settings_back', $previous);
        }

        if ($stored = $request->session()->get('settings_back')) {
            return $stored;
        }

        // Cadangan: unit/modul terakhir dari session.
        $unit = $request->session()->get('unit_id') ? Unit::find($request->session()->get('unit_id')) : null;
        $module = $request->session()->get('module');

        if ($unit && $module && config("modules.{$module}")) {
            return route('unit.dashboard', ['unit' => $unit, 'module' => $module]);
        }

        return $unit ? route('modules.select', $unit) : route('dashboard');
    }
}