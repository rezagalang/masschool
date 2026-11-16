<?php

use App\Livewire\Tables\RfidLogTable;
use Livewire\Attributes\Title;

new #[Title('Log Perubahan RFID')] class extends RfidLogTable {}; ?>

<div>
    <x-datatable :table="$this" />
</div>