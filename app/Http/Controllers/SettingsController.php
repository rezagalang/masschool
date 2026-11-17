<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function modules(Request $request): View
    {
        return view('pages.admin.settings.modules', [
            'modules' => config('modules'),
            'backUrl' => $this->backUrl($request),
        ]);
    }

    // Dipakai juga oleh halaman Livewire Settings (mis. Tahun Ajaran).
    public function backUrl(Request $request): string
    {
        $previous = url()->previous();
        $path = parse_url($previous, PHP_URL_PATH) ?: '/';

        if ($path !== '/' && ! str_starts_with($path, '/pengaturan') && ! str_starts_with($path, '/preferences')) {
            session()->put('settings_back', $previous);
        }

        if ($stored = session()->get('settings_back')) {
            return $stored;
        }

        // Cadangan: unit/modul terakhir dari session.
        $unit = session()->get('unit_id') ? Unit::find(session()->get('unit_id')) : null;
        $module = session()->get('module');

        if ($unit && $module && config("modules.{$module}")) {
            return route('unit.dashboard', ['unit' => $unit, 'module' => $module]);
        }

        return $unit ? route('modules.select', $unit) : route('dashboard');
    }
}