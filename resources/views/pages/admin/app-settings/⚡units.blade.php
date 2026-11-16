<?php

use App\Http\Controllers\AppSettingsController;
use App\Models\Unit;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

// Pengaturan unit global.
new #[Title('App Settings')] #[Layout('layouts::app.header')] class extends Component {
    #[Locked]
    public string $backUrl = '';

    // Konfirmasi nonaktifkan.
    #[Locked]
    public ?int $targetId = null;

    #[Locked]
    public string $targetName = '';

    public function mount(): void
    {
        $this->guard();

        $this->backUrl = app(AppSettingsController::class)->backUrl(request());
    }

    #[Computed]
    public function units()
    {
        return Unit::query()->orderBy('id')->get();
    }

    public function activate(int $id): void
    {
        $this->guard();

        $unit = Unit::findOrFail($id);
        $unit->update(['is_active' => true]);

        $this->notify("Unit {$unit->unit} diaktifkan.");
    }

    public function confirmDeactivate(int $id): void
    {
        $this->guard();

        $unit = Unit::findOrFail($id);

        $this->targetId = $unit->id;
        $this->targetName = $unit->unit;

        Flux::modal('confirm-deactivate')->show();
    }

    public function deactivate(): void
    {
        $this->guard();

        $unit = Unit::findOrFail($this->targetId);

        Flux::modal('confirm-deactivate')->close();

        if (Unit::query()->where('is_active', true)->count() <= 1) {
            $this->notify('Minimal satu unit harus tetap aktif.', 'danger');

            return;
        }

        $unit->update(['is_active' => false]);

        $this->targetId = null;
        $this->notify("Unit {$unit->unit} dinonaktifkan.");
    }

    // Validasi akses di-cek di tiap aksi karena request Livewire bypass middleware route.
    protected function guard(): void
    {
        abort_unless(auth()->user()?->hasRole('Kepala Sekolah'), 403);
    }

    protected function notify(string $message, string $variant = 'success'): void
    {
        unset($this->units);

        Flux::toast(text: $message, variant: $variant);
    }
}; ?>

@php
    $units = $this->units;
    $activeCount = $units->where('is_active', true)->count();
    $inactiveCount = $units->count() - $activeCount;
    $search = $units->mapWithKeys(
        fn($u) => [
            $u->id => strtolower($u->unit . ' ' . $u->name . ' ' . $u->city),
        ],
    );
@endphp

<flux:main container>
    <div x-data="{
        q: '',
        items: @js($search),
        show(id) { return !this.q || this.items[id].includes(this.q.toLowerCase()) },
        get empty() { return Object.keys(this.items).every(id => !this.show(id)) },
    }" class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 py-10">
        @include('pages.admin.app-settings.nav', ['tab' => 'units'])

        <!-- Ringkasan -->
        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Total</flux:text>
                <flux:heading size="xl" class="tracking-tight">{{ $units->count() }}</flux:heading>
            </flux:card>
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Aktif</flux:text>
                <flux:heading size="xl" class="tracking-tight text-green-600 dark:text-green-400">
                    {{ $activeCount }}
                </flux:heading>
            </flux:card>
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Nonaktif</flux:text>
                <flux:heading size="xl" @class([
                    'tracking-tight',
                    'text-red-600 dark:text-red-400' => $inactiveCount > 0,
                ])>
                    {{ $inactiveCount }}
                </flux:heading>
            </flux:card>
        </div>

        <!-- Pencarian -->
        @if ($units->count() > 5)
            <flux:input x-model="q" icon="magnifying-glass" placeholder="Cari unit atau kota..." clearable />
        @endif

        <!-- Daftar unit -->
        @if ($units->isNotEmpty())
            <flux:card class="divide-y divide-zinc-200 overflow-hidden !p-0 shadow-xs dark:divide-white/10">
                @foreach ($units as $unit)
                    @php($isLast = $unit->is_active && $activeCount <= 1)

                    <div wire:key="unit-{{ $unit->id }}" x-show="show({{ $unit->id }})" x-transition.opacity.duration.150ms
                        class="flex flex-wrap items-center gap-x-4 gap-y-3 p-4 transition-colors duration-150 hover:bg-zinc-50 sm:flex-nowrap dark:hover:bg-zinc-800/60">
                        <flux:avatar icon="building-office-2" :color="$unit->is_active ? 'green' : 'zinc'"
                            class="shrink-0 !rounded-lg {{ $unit->is_active ? '' : 'opacity-60' }}" />

                        <div class="min-w-0 flex-1 space-y-0.5">
                            <div class="flex items-center gap-2">
                                <flux:heading @class([
                                    'truncate font-semibold tracking-tight',
                                    'opacity-60' => !$unit->is_active,
                                ])>{{ $unit->unit }}</flux:heading>

                                @if ($unit->is_active)
                                    <flux:badge size="sm" color="green" icon="check-circle"
                                        inset="top bottom">
                                        Aktif
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Nonaktif
                                    </flux:badge>
                                @endif
                            </div>

                            <flux:text size="sm" class="truncate">{{ $unit->name }}</flux:text>

                            @if ($unit->city)
                                <div class="flex items-center gap-1.5 text-zinc-400 dark:text-zinc-500">
                                    <flux:icon name="map-pin" variant="micro" class="shrink-0" />
                                    <flux:text size="sm" class="!text-zinc-400 dark:!text-zinc-500">
                                        {{ $unit->city }}
                                    </flux:text>
                                </div>
                            @endif
                        </div>

                        <div class="ms-auto shrink-0">
                            @if ($isLast)
                                <flux:tooltip content="Minimal satu unit harus tetap aktif">
                                    <div>
                                        <flux:button size="sm" icon="power" disabled>Nonaktifkan
                                        </flux:button>
                                    </div>
                                </flux:tooltip>
                            @elseif ($unit->is_active)
                                <!-- Nonaktifkan: minta konfirmasi lewat modal -->
                                <flux:button type="button" size="sm" icon="power" variant="ghost"
                                    wire:click="confirmDeactivate({{ $unit->id }})">
                                    Nonaktifkan
                                </flux:button>
                            @else
                                <!-- Aktifkan: langsung kirim -->
                                <flux:button type="button" size="sm" icon="power" variant="primary"
                                    wire:click="activate({{ $unit->id }})">
                                    Aktifkan
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </flux:card>

            <div x-cloak x-show="empty">
                <flux:callout icon="magnifying-glass" heading="Unit tidak ditemukan">
                    <flux:callout.text>
                        Tidak ada unit yang cocok dengan "<span x-text="q"></span>". Coba kata kunci lain.
                    </flux:callout.text>
                </flux:callout>
            </div>
        @else
            <flux:callout icon="building-office-2" heading="Belum ada unit">
                <flux:callout.text>Tambahkan unit di data master terlebih dahulu.</flux:callout.text>
            </flux:callout>
        @endif

        <!-- Modal konfirmasi nonaktifkan -->
        <flux:modal name="confirm-deactivate" class="min-w-[22rem] max-w-md">
            <form wire:submit="deactivate" class="space-y-6">
                <div class="flex items-start gap-4">
                    <flux:avatar icon="exclamation-triangle" color="red" class="shrink-0 !rounded-lg" />

                    <div>
                        <flux:heading size="lg" class="tracking-tight">Nonaktifkan unit?</flux:heading>
                        <flux:text class="mt-2">
                            Unit <span class="font-semibold">{{ $targetName }}</span> tidak akan muncul lagi
                            di pilihan unit. Anda bisa mengaktifkannya kembali kapan saja.
                        </flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" icon="x-mark">Batal</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="danger" icon="power">Nonaktifkan</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</flux:main>