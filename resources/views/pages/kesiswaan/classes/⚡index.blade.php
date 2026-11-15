<?php

use App\Livewire\Tables\ClassroomTable;
use Livewire\Attributes\Title;

new #[Title('Kelas')] class extends ClassroomTable {}; ?>

<div>
    <x-datatable :table="$this" />

    <!-- Tambah / ubah kelas -->
    <flux:modal name="class-form" class="w-full max-w-md">
        <form wire:submit="save"
            class="space-y-6 [&_[data-flux-error]]:mt-0 [&_[data-flux-error]]:text-xs [&_[data-flux-error]_svg]:size-3">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Ubah Kelas') : __('Tambah Kelas') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Kelas di unit :unit.', ['unit' => $unitName]) }}</flux:text>
            </div>

            <flux:input wire:model="form.kelas" :label="__('Nama Kelas')" maxlength="50" autofocus />

            <flux:input wire:model="form.jenjang" :label="__('Jenjang')" maxlength="30" />

            <flux:input type="number" wire:model="form.nourut" :label="__('No. Urut')" min="0" max="255"
                :description="__('Opsional. Dipakai untuk mengurutkan kelas.')" />

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="danger">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
