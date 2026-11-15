<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\Kesiswaan\Registration;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tampilkan file Bukti Transfer (private disk, akses terbatas). */
class RegistrationProofController extends Controller
{
    public function __invoke(Unit $unit, int $registration): StreamedResponse
    {
        $path = Registration::findOrFail($registration)->bukti_transfer;

        // File harus berada di folder unit ini.
        abort_unless(
            str_starts_with($path, "ppdb/{$unit->code}/")
                && Storage::disk('local')->exists($path),
            404
        );

        return Storage::disk('local')->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}