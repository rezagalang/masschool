<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleSelectionController extends Controller
{
    public function index(Request $request, Unit $unit): View
    {
        return view('pages.admin.modules.select', [
            'unit' => $unit,
            'modules' => config('modules'),
            'currentModule' => $request->session()->get('module'),
        ]);
    }

    public function dashboard(Request $request, Unit $unit, string $module): View
    {
        $request->session()->put('module', $module);

        return view('dashboard', [
            'unit' => $unit,
            'moduleKey' => $module,
            'module' => config("modules.{$module}"),
        ]);
    }
}