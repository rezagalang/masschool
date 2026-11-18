<?php

use App\Livewire\Tables\PaymentTypeTable;
use Livewire\Attributes\Title;

new #[Title('Jenis Pembayaran')] class extends PaymentTypeTable {}; ?>

<div>
    <x-datatable :table="$this" />

    <!-- Tambah / ubah jenis pembayaran -->
    <flux:modal name="payment-type-form" class="w-full max-w-md">
        <form wire:submit="save"
            class="space-y-6 [&_[data-flux-error]]:mt-0 [&_[data-flux-error]]:text-xs [&_[data-flux-error]_svg]:size-3">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Ubah Jenis Pembayaran') : __('Tambah Jenis Pembayaran') }}
                </flux:heading>
                <flux:text class="mt-2">{{ __('Jenis pembayaran di unit :unit.', ['unit' => $unitName]) }}</flux:text>
            </div>

            <flux:input wire:model="form.nama" :label="__('Nama')" maxlength="50" autofocus />

            <flux:input wire:model="form.keterangan" :label="__('Keterangan')" maxlength="50" />

            <flux:input wire:model="form.alias" :label="__('Alias')" maxlength="10"
                :description="__('Opsional. Singkatan, maksimal 10 karakter.')" />

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="danger" icon="x-mark">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="save">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>