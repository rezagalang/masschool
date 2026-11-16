<?php

use App\Livewire\Tables\StudentRfidTable;
use Livewire\Attributes\Title;

new #[Title('Aktivasi RFID')] class extends StudentRfidTable {}; ?>

<div>
    <x-datatable :table="$this">
        {{-- Aksi massal pada baris yang dicentang --}}
        <x-slot:bulk>
            <flux:button size="xs" variant="primary" icon="credit-card" wire:click="openBulkRfid"
                wire:loading.attr="disabled" wire:target="openBulkRfid">
                {{ __('Aktivasi / Ganti RFID') }}
            </flux:button>

            <flux:button size="xs" variant="danger" icon="x-circle" wire:click="confirmBulkDeactivate"
                wire:loading.attr="disabled" wire:target="confirmBulkDeactivate">
                {{ __('Nonaktifkan RFID') }}
            </flux:button>
        </x-slot:bulk>
    </x-datatable>

    <!-- Aktivasi / ganti RFID satu siswa -->
    <flux:modal name="rfid-form" class="w-full max-w-md">
        <form wire:submit="save"
            class="space-y-6 [&_[data-flux-error]]:mt-0 [&_[data-flux-error]]:text-xs [&_[data-flux-error]_svg]:size-3">
            <div>
                <flux:heading size="lg">
                    {{ $currentRfid !== '' ? __('Ganti RFID') : __('Aktivasi RFID') }}
                </flux:heading>
                <flux:text class="mt-2">{{ $studentLabel }}</flux:text>
            </div>

            @if ($currentRfid !== '')
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-white/10 dark:bg-white/5">
                    <div class="flex justify-between gap-4">
                        <span class="text-zinc-500">{{ __('RFID saat ini') }}</span>
                        <span class="font-mono">{{ $currentRfid }}</span>
                    </div>
                    <div class="mt-1 flex justify-between gap-4">
                        <span class="text-zinc-500">{{ __('Saldo (ikut dipindahkan)') }}</span>
                        <span class="tabular-nums">Rp {{ number_format($currentSaldo, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif

            <flux:input wire:model="form.rfid" :label="__('Nomor RFID')" maxlength="30" autofocus
                :placeholder="__('Tempelkan kartu pada reader...')"
                :description="__('Kursor otomatis di sini; scan kartu lalu tekan Enter.')" />

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

    <!-- Nonaktifkan RFID satu siswa -->
    <flux:modal name="rfid-deactivate" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Nonaktifkan RFID?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Kartu :rfid milik', ['rfid' => $currentRfid]) }}
                    <strong>{{ $studentLabel }}</strong>
                    {{ __('akan dilepas dan datanya dicatat di log. Saldo kartu ini (Rp :saldo) dihapus dari saldo aktif.', ['saldo' => number_format($currentSaldo, 0, ',', '.')]) }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" icon="x-mark">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" icon="x-circle" wire:click="deactivate" wire:loading.attr="disabled"
                    wire:target="deactivate">
                    {{ __('Nonaktifkan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Aktivasi / ganti RFID massal: scan berurutan -->
    <flux:modal name="rfid-bulk" class="w-full max-w-lg">
        <form wire:submit="saveBulk"
            class="space-y-6 [&_[data-flux-error]]:mt-1 [&_[data-flux-error]]:text-xs [&_[data-flux-error]_svg]:size-3">
            <div>
                <flux:heading size="lg">{{ __('Aktivasi / Ganti RFID Massal') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __(':count siswa dipilih. Scan kartu lalu tekan Enter untuk pindah ke siswa berikutnya. Baris yang dikosongkan tidak diubah, dan tidak ada yang tersimpan sebelum semua nomor valid.', ['count' => count($bulkRows)]) }}
                </flux:text>
            </div>

            <div class="max-h-[55vh] space-y-3 overflow-y-auto pr-1" x-data
                x-on:keydown.enter.prevent="
                    const inputs = [...$el.querySelectorAll('input')];
                    const i = inputs.indexOf($event.target);
                    if (i < 0) return;
                    (inputs[i + 1] ?? $el.closest('form').querySelector('button[type=submit]'))?.focus();
                ">
                @foreach ($bulkRows as $row)
                    <div wire:key="rfid-bulk-{{ $row['id'] }}"
                        class="rounded-lg border border-zinc-200 p-3 dark:border-white/10">
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-zinc-800 dark:text-white">{{ $row['nama'] ?: '-' }}</div>
                                <div class="text-xs text-zinc-500">NIS {{ $row['nis'] ?: '-' }}</div>
                            </div>

                            @if ($row['rfid'] !== '')
                                <flux:badge size="sm" color="zinc">
                                    <span class="font-mono">{{ $row['rfid'] }}</span>
                                </flux:badge>
                            @else
                                <flux:badge size="sm" color="amber">{{ __('Belum aktif') }}</flux:badge>
                            @endif
                        </div>

                        <flux:input size="sm" wire:model="form.bulk.{{ $row['id'] }}" maxlength="30"
                            :autofocus="$loop->first"
                            :placeholder="$row['rfid'] !== '' ? __('Nomor RFID baru (kosongkan jika tidak diganti)') : __('Scan kartu...')" />
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="danger" icon="x-mark">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="saveBulk">
                    {{ __('Simpan semua') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Nonaktifkan RFID massal -->
    <flux:modal name="rfid-bulk-deactivate" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ __('Nonaktifkan RFID :count siswa?', ['count' => number_format(count($bulkDeactivateIds), 0, ',', '.')]) }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Kartu akan dilepas dari siswa dan dicatat di log. Saldo tiap kartu dihapus dari saldo aktif. Siswa terpilih yang belum punya RFID dilewati.') }}
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost" icon="x-mark">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" icon="x-circle" wire:click="deactivateBulk" wire:loading.attr="disabled"
                    wire:target="deactivateBulk">
                    {{ __('Nonaktifkan') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>