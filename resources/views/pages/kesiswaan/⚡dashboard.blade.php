<?php

use App\Models\Unit;
use App\Support\Kesiswaan\DashboardStats;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Kesiswaan')] class extends Component {
    #[Locked]
    public string $unitCode = '';

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;

        // Dulu diisi ModuleSelectionController@dashboard.
        session()->put('module', 'kesiswaan');
    }

    #[Computed]
    public function stats(): array
    {
        return DashboardStats::make();
    }
}; ?>

@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;

    $stats = $this->stats;
    $module = config('modules.kesiswaan');

    $s = $stats['students'];
    $reg = $stats['registrations'];
    $att = $stats['attendance'];
    $classes = $stats['classes'];
    $trend = $stats['trend'];
    $logs = $stats['rfid_logs'];

    // Kelas dengan siswa terbanyak jadi acuan panjang bar.
    $maxClass = max(1, (int) $classes->max('total'));

    // Status absensi hari ini: warna ditulis utuh agar terbaca Tailwind.
    $statusTiles = [
        ['key' => 'hadir', 'label' => __('Hadir'), 'dot' => 'bg-green-500'],
        ['key' => 'terlambat', 'label' => __('Terlambat'), 'dot' => 'bg-amber-500'],
        ['key' => 'sakit', 'label' => __('Sakit'), 'dot' => 'bg-sky-500'],
        ['key' => 'izin', 'label' => __('Izin'), 'dot' => 'bg-purple-500'],
        ['key' => 'izin_pulang', 'label' => __('Izin Pulang'), 'dot' => 'bg-zinc-400'],
        ['key' => 'alpha', 'label' => __('Alpha'), 'dot' => 'bg-red-500'],
    ];

    $aksiColor = ['aktivasi' => 'green', 'ganti' => 'amber', 'nonaktif' => 'red'];

    // Kartu ringkasan: [label, nilai, keterangan, ikon, kelas chip, route]
    $cards = [
        [
            'label' => __('Siswa Aktif'),
            'value' => $fmt($s['active']),
            'hint' => $fmt($s['male']) . ' ' . __('laki-laki') . ' · ' . $fmt($s['female']) . ' ' . __('perempuan'),
            'icon' => 'users',
            'chip' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300',
            'route' => 'kesiswaan.students.index',
        ],
        [
            'label' => __('Calon Siswa Menunggu'),
            'value' => $fmt($reg['pending']),
            'hint' => __('dari :total pendaftar', ['total' => $fmt($reg['total'])]),
            'icon' => 'user-plus',
            'chip' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300',
            'route' => 'kesiswaan.registrations.index',
        ],
        [
            'label' => __('Kehadiran Hari Ini'),
            'value' => $att['rate'] === null ? '–' : $att['rate'] . '%',
            'hint' => $att['total'] > 0
                ? __(':present dari :total siswa tercatat', ['present' => $fmt($att['present']), 'total' => $fmt($att['total'])])
                : __('Absensi belum diinput'),
            'icon' => 'clipboard-document-check',
            'chip' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-300',
            'route' => 'kesiswaan.attendances.index',
        ],
        [
            'label' => __('Kartu RFID Aktif'),
            'value' => $fmt($s['with_rfid']),
            'hint' => __(':percent% siswa aktif', ['percent' => $pct($s['with_rfid'], $s['active'])]),
            'icon' => 'credit-card',
            'chip' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-300',
            'route' => 'kesiswaan.rfid.index',
        ],
    ];

    $shortcuts = [
        ['label' => __('Tambah Siswa'), 'icon' => 'user-plus', 'route' => 'kesiswaan.students.create'],
        ['label' => __('Input Absensi'), 'icon' => 'clipboard-document-check', 'route' => 'kesiswaan.attendances.index'],
        ['label' => __('Pengelompokan'), 'icon' => 'user-group', 'route' => 'kesiswaan.groupings.index'],
        ['label' => __('Aktivasi RFID'), 'icon' => 'credit-card', 'route' => 'kesiswaan.rfid.index'],
        ['label' => __('Rekap Absensi'), 'icon' => 'chart-bar', 'route' => 'kesiswaan.attendance-recap.index'],
    ];
@endphp

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <flux:avatar :icon="$module['icon']" :color="$module['tone'] ?? 'zinc'" size="lg" />
            <div>
                <flux:heading size="xl" level="1">{{ __('Dashboard :modul', ['modul' => $module['label']]) }}</flux:heading>
                <flux:text>{{ $module['description'] }}</flux:text>
            </div>
        </div>

        @if ($stats['period'])
            <flux:badge color="emerald" icon="calendar-days">{{ $stats['period']->label() }}</flux:badge>
        @endif
    </div>


    <!-- Perlu perhatian -->
    @if ($stats['period'] && $s['ungrouped'] > 0)
        <flux:callout variant="warning" icon="exclamation-triangle" inline>
            <flux:callout.heading>
                {{ __(':n siswa aktif belum masuk kelas', ['n' => $fmt($s['ungrouped'])]) }}
            </flux:callout.heading>
            <flux:callout.text>
                {{ __('Kelompokkan ke kelas untuk periode :periode agar absensi dan rekap akurat.', ['periode' => $stats['period']->label()]) }}
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" :href="route('kesiswaan.groupings.index', ['unit' => $unitCode])" wire:navigate>
                    {{ __('Kelompokkan') }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endif

    <!-- Ringkasan -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ route($card['route'], ['unit' => $unitCode]) }}" wire:navigate class="group block">
                <flux:card class="h-full transition group-hover:bg-zinc-50 dark:group-hover:bg-zinc-800/60">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:text size="sm">{{ $card['label'] }}</flux:text>
                            <flux:heading size="xl" class="mt-1 tabular-nums">{{ $card['value'] }}</flux:heading>
                        </div>

                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $card['chip'] }}">
                            <flux:icon :name="$card['icon']" class="size-5" />
                        </span>
                    </div>

                    <flux:text size="sm" class="mt-3 truncate">{{ $card['hint'] }}</flux:text>
                </flux:card>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Absensi -->
        <flux:card class="flex flex-col gap-5 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ __('Absensi Hari Ini') }}</flux:heading>
                    <flux:text size="sm">{{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</flux:text>
                </div>

                <flux:button size="sm" variant="subtle" icon-trailing="arrow-right"
                    :href="route('kesiswaan.attendance-recap.index', ['unit' => $unitCode])" wire:navigate>
                    {{ __('Rekap') }}
                </flux:button>
            </div>

            @if ($att['total'] > 0)
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($statusTiles as $tile)
                        <div class="rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full {{ $tile['dot'] }}"></span>
                                <flux:text size="sm">{{ $tile['label'] }}</flux:text>
                            </div>
                            <flux:heading size="lg" class="mt-1 tabular-nums">
                                {{ $fmt($att['status'][$tile['key']]) }}
                            </flux:heading>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-300 px-4 py-8 text-center dark:border-zinc-600">
                    <flux:icon.clipboard-document-check class="size-8 text-zinc-400" />
                    <flux:text>{{ __('Belum ada absensi yang diinput hari ini.') }}</flux:text>
                    <flux:button size="sm" variant="primary"
                        :href="route('kesiswaan.attendances.index', ['unit' => $unitCode])" wire:navigate>
                        {{ __('Input Absensi') }}
                    </flux:button>
                </div>
            @endif

            <flux:separator />

            <!-- Tren kehadiran 7 hari -->
            <div>
                <flux:heading>{{ __('Tren Kehadiran 7 Hari') }}</flux:heading>

                @if ($trend->isNotEmpty())
                    <div class="mt-4 flex h-32 items-end gap-2 sm:gap-3">
                        @foreach ($trend as $point)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5"
                                title="{{ $point['date']->locale('id')->isoFormat('dddd, D MMM') }}: {{ $point['rate'] }}%">
                                <flux:text size="xs" class="tabular-nums">{{ $point['rate'] }}%</flux:text>
                                <div class="w-full max-w-10 rounded-t-md bg-emerald-500/80 dark:bg-emerald-400/70"
                                    style="height: {{ max(4, $point['rate']) * 0.7 }}%"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex gap-2 sm:gap-3">
                        @foreach ($trend as $point)
                            <flux:text size="xs" class="flex-1 text-center">
                                {{ $point['date']->locale('id')->isoFormat('dd, D') }}
                            </flux:text>
                        @endforeach
                    </div>
                @else
                    <flux:text size="sm" class="mt-2">{{ __('Belum ada data absensi dalam 7 hari terakhir.') }}</flux:text>
                @endif
            </div>
        </flux:card>

        <!-- Aksi cepat + aktivitas RFID -->
        <div class="flex flex-col gap-4">
            <flux:card>
                <flux:heading size="lg">{{ __('Aksi Cepat') }}</flux:heading>

                <div class="mt-3 flex flex-col gap-1">
                    @foreach ($shortcuts as $item)
                        <flux:button variant="subtle" align="start" :icon="$item['icon']" class="w-full"
                            :href="route($item['route'], ['unit' => $unitCode])" wire:navigate>
                            {{ $item['label'] }}
                        </flux:button>
                    @endforeach
                </div>
            </flux:card>

            <flux:card class="flex-1">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="lg">{{ __('Aktivitas RFID') }}</flux:heading>
                    <flux:button size="xs" variant="ghost" icon-trailing="arrow-right"
                        :href="route('kesiswaan.rfid-logs.index', ['unit' => $unitCode])" wire:navigate>
                        {{ __('Semua') }}
                    </flux:button>
                </div>

                @forelse ($logs as $log)
                    <div @class(['flex items-center justify-between gap-3 py-2.5', 'border-t border-zinc-200 dark:border-zinc-700' => ! $loop->first, 'mt-2' => $loop->first])>
                        <div class="min-w-0">
                            <flux:text variant="strong" class="truncate">{{ $log->nama }}</flux:text>
                            <flux:text size="xs">{{ $log->created_at?->locale('id')->diffForHumans() }}</flux:text>
                        </div>

                        <flux:badge size="sm" :color="$aksiColor[$log->aksi] ?? 'zinc'">
                            {{ ucfirst($log->aksi) }}
                        </flux:badge>
                    </div>
                @empty
                    <flux:text size="sm" class="mt-2">{{ __('Belum ada aktivitas RFID.') }}</flux:text>
                @endforelse
            </flux:card>
        </div>
    </div>

    <!-- Siswa per kelas -->
    <flux:card>
        <div class="flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('Siswa per Kelas') }}</flux:heading>
                <flux:text size="sm">
                    {{ $stats['period'] ? $stats['period']->label() : __('Belum ada periode aktif') }}
                </flux:text>
            </div>

            <flux:button size="sm" variant="subtle" icon-trailing="arrow-right"
                :href="route('kesiswaan.classes.index', ['unit' => $unitCode])" wire:navigate>
                {{ __('Kelola Kelas') }}
            </flux:button>
        </div>

        @if ($classes->isNotEmpty())
            <div class="mt-5 grid gap-x-10 gap-y-4 md:grid-cols-2">
                @foreach ($classes as $class)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-medium text-zinc-700 dark:text-zinc-200">{{ $class['name'] }}</span>
                            <span class="tabular-nums text-zinc-500 dark:text-zinc-400">
                                {{ $fmt($class['total']) }} {{ __('siswa') }}
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700/60">
                            <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400"
                                style="width: {{ $pct($class['total'], $maxClass) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <flux:text size="sm" class="mt-4">{{ __('Belum ada data kelas.') }}</flux:text>
        @endif
    </flux:card>
</div>