<?php

use App\Livewire\Tables\StudentTable;
use Livewire\Attributes\Title;

new #[Title('Siswa')] class extends StudentTable {}; ?>

<x-datatable :table="$this" />