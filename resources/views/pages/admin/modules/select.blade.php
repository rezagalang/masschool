<x-layouts::app.header :title="__('Pilih Modul')">
    <flux:main container>
        @php
            $fallbackColors = ['blue', 'emerald', 'violet', 'amber', 'rose', 'cyan', 'indigo', 'teal'];
        @endphp

        <div x-data="{ search: '' }" class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 py-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <flux:breadcrumbs class="mb-3">
                        <flux:breadcrumbs.item href="{{ route('units.select') }}" wire:navigate icon="building-office-2">
                            Unit
                        </flux:breadcrumbs.item>
                        <flux:breadcrumbs.item>{{ $unit->unit }}</flux:breadcrumbs.item>
                    </flux:breadcrumbs>

                    <flux:heading size="xl" level="1" class="tracking-tight">Pilih modul</flux:heading>
                    <flux:text>
                        Unit {{ $unit->unit }}. Buka modul yang ingin Anda kerjakan; bisa diganti kapan saja dari
                        sidebar.
                    </flux:text>
                </div>

                @if (count($modules) > 4)
                    <div class="w-full sm:w-72">
                        <flux:input x-model="search" icon="magnifying-glass" placeholder="Cari modul..." clearable />
                    </div>
                @endif
            </div>

            <flux:separator />

            <!-- Grid modul -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-ref="grid">
                @foreach ($modules as $key => $module)
                    @php
                        $active = $currentModule === $key;
                        $color = $module['tone'] ?? $fallbackColors[$loop->index % count($fallbackColors)];
                    @endphp

                    <a href="{{ route('unit.dashboard', ['unit' => $unit, 'module' => $key]) }}" wire:navigate
                        x-show="!search || @js(strtolower($module['label'] . ' ' . $module['description'])).includes(search.toLowerCase())"
                        x-transition.opacity.duration.150ms
                        class="group block rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900">
                        <flux:card @class([
                            'flex h-full flex-col gap-3 !p-5 shadow-xs transition-colors duration-150',
                            'group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800/60' => !$active,
                            '!border-green-500 ring-1 ring-green-500/30' => $active,
                        ])>
                            <!-- Ikon + status -->
                            <div class="flex items-start justify-between gap-3">
                                <flux:avatar :icon="$module['icon']" :color="$color"
                                    class="!rounded-lg transition-transform duration-150 group-hover:scale-105" />

                                @if ($active)
                                    <flux:badge size="sm" color="green" icon="clock" inset="top bottom">
                                        Terakhir dibuka
                                    </flux:badge>
                                @else
                                    <flux:icon name="arrow-up-right" variant="micro"
                                        class="text-zinc-400 opacity-0 transition duration-150 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100" />
                                @endif
                            </div>

                            <!-- Judul & deskripsi -->
                            <div class="min-w-0 space-y-0.5">
                                <flux:heading class="truncate font-semibold tracking-tight">
                                    {{ $module['label'] }}
                                </flux:heading>
                                <flux:text size="sm" class="line-clamp-2">{{ $module['description'] }}</flux:text>
                            </div>
                        </flux:card>
                    </a>
                @endforeach
            </div>

            <!-- Hasil pencarian kosong -->
            <div x-cloak
                x-show="search && $refs.grid.querySelectorAll('a:not([style*=\'display: none\'])').length === 0">
                <flux:callout icon="magnifying-glass" heading="Modul tidak ditemukan">
                    <flux:callout.text>
                        Tidak ada modul yang cocok dengan "<span x-text="search"></span>". Coba kata kunci lain.
                    </flux:callout.text>
                </flux:callout>
            </div>
        </div>
    </flux:main>
</x-layouts::app.header>