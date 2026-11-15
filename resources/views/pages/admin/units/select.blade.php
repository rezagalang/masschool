<x-layouts::app.header :title="__('Pilih Unit')">
    <flux:main container>
        <div x-data="{ search: '' }" class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 py-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <flux:heading size="xl" level="1" class="tracking-tight">Pilih unit</flux:heading>
                    <flux:text>
                        Halo, {{ auth()->user()->name }}. Unit mana yang ingin Anda kelola hari ini?
                    </flux:text>
                </div>

                @if ($units->count() > 3)
                    <div class="w-full sm:w-72">
                        <flux:input x-model="search" icon="magnifying-glass" placeholder="Cari unit..." clearable />
                    </div>
                @endif
            </div>

            <flux:separator />

            @if ($units->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-ref="list">
                    @foreach ($units as $unit)
                        @php($active = (int) $currentUnitId === $unit->id)

                        <a href="{{ route('modules.select', $unit) }}" wire:navigate
                            x-show="!search || @js(strtolower($unit->unit . ' ' . $unit->name . ' ' . $unit->address)).includes(search.toLowerCase())"
                            x-transition.opacity.duration.150ms
                            class="group block rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900">
                            <flux:card @class([
                                'flex h-full flex-col gap-3 !p-5 shadow-xs transition-colors duration-150',
                                'group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800/60' => !$active,
                                '!border-green-500 ring-1 ring-green-500/30' => $active,
                            ])>
                                <!-- Ikon + status -->
                                <div class="flex items-start justify-between gap-3">
                                    <flux:avatar icon="building-office-2" :color="$active ? 'green' : 'zinc'"
                                        class="!rounded-lg transition-transform duration-150 group-hover:scale-105" />

                                    @if ($active)
                                        <flux:badge size="sm" color="green" icon="check-circle" inset="top bottom">
                                            Aktif
                                        </flux:badge>
                                    @else
                                        <flux:icon name="arrow-up-right" variant="micro"
                                            class="text-zinc-400 opacity-0 transition duration-150 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100" />
                                    @endif
                                </div>

                                <!-- Nama unit -->
                                <div class="min-w-0 space-y-0.5">
                                    <flux:heading class="truncate font-semibold tracking-tight">
                                        {{ $unit->unit }}
                                    </flux:heading>
                                    <flux:text size="sm" class="line-clamp-1">{{ $unit->name }}</flux:text>
                                </div>

                                @if ($unit->address)
                                    <flux:text size="sm"
                                        class="mt-auto line-clamp-1 !text-zinc-400 dark:!text-zinc-500">
                                        {{ $unit->address }}
                                    </flux:text>
                                @endif
                            </flux:card>
                        </a>
                    @endforeach
                </div>

                <!-- Hasil pencarian kosong -->
                <div x-cloak
                    x-show="search && $refs.list.querySelectorAll('a:not([style*=\'display: none\'])').length === 0">
                    <flux:callout icon="magnifying-glass" heading="Unit tidak ditemukan">
                        <flux:callout.text>
                            Tidak ada unit yang cocok dengan "<span x-text="search"></span>". Coba kata kunci lain.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            @else
                <flux:callout icon="building-office-2" heading="Belum ada unit aktif">
                    <flux:callout.text>
                        Aktifkan minimal satu unit di data master, lalu muat ulang halaman ini.
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    </flux:main>
</x-layouts::app.header>