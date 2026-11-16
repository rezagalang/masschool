<?php

namespace App\Models\Kesiswaan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Riwayat aktivasi / penggantian / penonaktifan RFID (tabel `rfid_logs`, database unit). */
#[Guarded(['id'])]
class RfidLog extends UnitModel
{
    public const AKSI_AKTIVASI = 'aktivasi';

    public const AKSI_GANTI = 'ganti';

    public const AKSI_NONAKTIF = 'nonaktif';

    protected $table = 'rfid_logs';

    protected function casts(): array
    {
        return [
            'saldo' => 'float',
            'student_id' => 'integer',
            'class_id' => 'integer',
            'academic_period_id' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}