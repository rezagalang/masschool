<?php

namespace App\Models\Kesiswaan;

use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Attributes\Guarded;

/** Saldo kartu RFID (tabel `rfid_balances`, database unit). Primary key = nomor RFID. */
#[Guarded([])]
class RfidBalance extends UnitModel
{
    protected $table = 'rfid_balances';

    protected $primaryKey = 'rfid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'saldo' => 'integer',
        ];
    }
}