<?php

namespace App\Models;

use App\Support\UnitDatabase;
use Illuminate\Database\Eloquent\Model;

/** Base model tabel modul untuk database unit aktif. */
abstract class UnitModel extends Model
{
    protected $connection = UnitDatabase::CONNECTION;
}