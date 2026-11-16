<?php

use App\Livewire\Kesiswaan\StudentAttendanceForm;
use Livewire\Attributes\Title;

new #[Title('Absensi Siswa')] class extends StudentAttendanceForm {}; ?>

@php
    $members = $this->members;
    $period = $this->period;
    $summary = $this->summary;
    $ready = $this->classes->isNotEmpty();

    $isToday = $date === now()->toDateString();
    $dateLabel = \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y');
    $currentClass = $this->classes->firstWhere('id', (int) $classId);

    $savedCount = count($saved);
    $total = $members->count();
    $savedState = match (true) {
        $total === 0 || $savedCount === 0 => ['color' => 'zinc', 'label' => __('Belum diabsen')],
        $savedCount >= $total => ['color' => 'green', 'label' => __('Sudah diabsen')],
        default => ['color' => 'amber', 'label' => __('Sebagian diabsen')],
    };

    // Satu pilihan per siswa. Kelas Tailwind ditulis utuh agar ikut terbaca saat build.
    $options = [
        'hadir' => ['label' => __('Hadir'), 'badge' => 'green', 'accent' => 'accent-green-600'],
        'terlambat' => ['label' => __('Terlambat'), 'badge' => 'amber', 'accent' => 'accent-amber-500'],
        'sakit' => ['label' => __('Sakit'), 'badge' => 'sky', 'accent' => 'accent-sky-500'],
        'izin' => ['label' => __('Izin'), 'badge' => 'purple', 'accent' => 'accent-purple-500'],
        'izin_pulang' => ['label' => __('Izin Pulang'), 'badge' => 'zinc', 'accent' => 'accent-zinc-500'],
        'alpha' => ['label' => __('Alpha'), 'badge' => 'red', 'accent' => 'accent-red-600'],
    ];

    // Kolom daftar (layar md ke atas): No | Nama | 6 status | Keterangan.
    $cols = 'md:grid-cols-[2rem_minmax(0,1fr)_repeat(6,4.5rem)_11rem]';
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Absensi Siswa') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Isi atau ubah absensi harian siswa per kelas. Unit :unit.', ['unit' => $unitName]) }}
        </flux:text>
    </div>

    @if (! $ready)
        <flux:card>
            <flux:heading>{{ __('Data belum lengkap') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Belum ada kelas. Tambahkan kelas di menu Kelas terlebih dahulu.') }}</flux:text>
        </flux:card>
    @else
        <!-- Pilih kelas & tanggal -->
        <flux:card class="@container flex flex-col gap-4 p-4 sm:p-6">
            <div class="grid gap-4 @md:grid-cols-2">
                <flux:select wire:model.live="classId" :label="__('Kelas')">
                    @foreach ($this->classes as $class)
                        <flux:select.option value="{{ $class->id }}">{{ $class->kelas }} ({{ $class->jenjang }})</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:field>
                    <flux:label>{{ __('Tanggal') }}</flux:label>

                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="shiftDate(-1)"
                            aria-label="{{ __('Hari sebelumnya') }}" />

                        <div class="min-w-0 flex-1">
                            <x-date-picker wire:model.live="date" />
                        </div>

                        <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="shiftDate(1)"
                            :disabled="$isToday" aria-label="{{ __('Hari berikutnya') }}" />

                        <flux:button size="sm" variant="subtle" wire:click="today" :disabled="$isToday">
                            {{ __('Hari ini') }}
                        </flux:button>
                    </div>

                    <flux:error name="date" />
                </flux:field>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:text size="sm">
                    {{ $dateLabel }}
                    @if ($period)
                        · {{ $period->label() }}
                    @endif
                </flux:text>

                @if ($period && $total > 0)
                    <flux:badge size="sm" :color="$savedState['color']">{{ $savedState['label'] }}</flux:badge>
                @endif
            </div>
        </flux:card>

        @if (! $period)
            <flux:callout variant="warning" icon="exclamation-triangle"
                :heading="__('Tanggal di luar tahun ajaran')">
                <flux:callout.text>
                    {{ __('Tanggal ini tidak berada di tahun ajaran mana pun, sehingga daftar siswa kelas tidak bisa ditentukan. Pilih tanggal lain atau lengkapi data periode akademik.') }}
                </flux:callout.text>
            </flux:callout>
        @elseif ($members->isEmpty())
            <flux:callout icon="user-group" :heading="__('Belum ada siswa')">
                <flux:callout.text>
                    {{ __('Kelas :kelas belum memiliki siswa pada :periode. Atur dulu di menu Pengelompokan.', ['kelas' => $currentClass?->kelas, 'periode' => $period->label()]) }}
                </flux:callout.text>
            </flux:callout>
        @else
            <flux:card class="@container flex flex-col gap-4 p-4 sm:p-6">
                <!-- Rekap -->
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Kelas :kelas', ['kelas' => $currentClass?->kelas]) }}</flux:heading>
                        <flux:text size="sm">{{ __(':total siswa', ['total' => $total]) }}</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($options as $key => $option)
                            @if ($key === 'hadir' || $summary[$key] > 0)
                                <flux:badge size="sm" :color="$option['badge']">{{ $option['label'] }} {{ $summary[$key] }}</flux:badge>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Daftar: tinggi dibatasi, bergulir di dalam kotak; header tetap terlihat -->
                <div wire:key="rows-{{ $classId }}-{{ $date }}" wire:loading.class="opacity-60"
                    wire:target="classId,date,shiftDate,today,resetChanges,markAllPresent,save"
                    class="max-h-[65vh] overflow-auto rounded-lg border border-zinc-200 transition-opacity duration-200 dark:border-white/10">

                    {{-- Header kolom (layar md ke atas) --}}
                    <div class="sticky top-0 z-10 hidden items-center gap-x-1 border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-center text-[11px] font-semibold leading-tight text-zinc-500 md:grid {{ $cols }} dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-400">
                        <span>{{ __('No') }}</span>
                        <span class="text-left">{{ __('Nama Siswa') }}</span>
                        @foreach ($options as $option)
                            <span>{{ $option['label'] }}</span>
                        @endforeach
                        <span class="text-left">{{ __('Keterangan') }}</span>
                    </div>

                    <div class="divide-y divide-zinc-100 dark:divide-white/5">
                        @foreach ($members as $i => $student)
                            @php
                                $row = $rows[$student->id] ?? ['status' => 'hadir', 'keterangan' => ''];
                                $isPresent = $row['status'] === 'hadir';
                                $tint = match ($row['status']) {
                                    'sakit' => 'bg-sky-50/60 dark:bg-sky-950/20',
                                    'izin' => 'bg-purple-50/60 dark:bg-purple-950/20',
                                    'alpha' => 'bg-red-50/60 dark:bg-red-950/20',
                                    default => '',
                                };
                            @endphp

                            <div wire:key="att-{{ $student->id }}"
                                class="grid grid-cols-[1.5rem_minmax(0,1fr)_8.5rem] items-center gap-x-1 gap-y-1 px-3 py-1.5 transition-colors duration-200 {{ $cols }} {{ $tint }}">
                                <span class="text-xs tabular-nums text-zinc-400 md:text-center">{{ $i + 1 }}</span>

                                <div class="min-w-0 pr-2">
                                    <div class="truncate text-sm font-medium">{{ $student->a_namalengkap }}</div>
                                    <div class="font-mono text-[11px] leading-tight text-zinc-500">{{ $student->nis }}</div>
                                </div>

                                {{-- Layar kecil: satu dropdown --}}
                                <div class="md:hidden">
                                    <flux:select size="sm" wire:model.live="rows.{{ $student->id }}.status">
                                        @foreach ($options as $key => $option)
                                            <flux:select.option value="{{ $key }}">{{ $option['label'] }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                {{-- Layar md ke atas: satu radio per status (pilih salah satu) --}}
                                @foreach ($options as $key => $option)
                                    <label class="hidden h-9 cursor-pointer items-center justify-center md:flex"
                                        title="{{ $option['label'] }}">
                                        <input type="radio" name="status-{{ $student->id }}" value="{{ $key }}"
                                            wire:model.live="rows.{{ $student->id }}.status"
                                            class="size-4 cursor-pointer {{ $option['accent'] }}"
                                            aria-label="{{ $student->a_namalengkap }}: {{ $option['label'] }}" />
                                    </label>
                                @endforeach

                                <div class="hidden md:block">
                                    <flux:input size="sm" wire:model="rows.{{ $student->id }}.keterangan" maxlength="50"
                                        :placeholder="__('Opsional')" />
                                </div>

                                {{-- Layar kecil: keterangan hanya muncul jika bukan Hadir --}}
                                @unless ($isPresent)
                                    <div class="col-span-3 md:hidden">
                                        <flux:input size="sm" wire:model="rows.{{ $student->id }}.keterangan" maxlength="50"
                                            :placeholder="__('Keterangan (opsional)')" />
                                    </div>
                                @endunless

                                @error('rows.' . $student->id . '.keterangan')
                                    <p class="col-span-full text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Aksi: layar kecil Simpan di atas dan selebar layar; layar lebar sejajar di kanan -->
                <div class="flex flex-col-reverse gap-2 @md:flex-row @md:items-center">
                    <div class="flex items-center justify-between gap-1 @md:justify-start">
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetChanges">
                            {{ __('Batalkan perubahan') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="check-circle" wire:click="markAllPresent">
                            {{ __('Semua hadir') }}
                        </flux:button>
                    </div>

                    <flux:button variant="primary" icon="check" class="w-full @md:ml-auto @md:w-auto" wire:click="save"
                        wire:loading.attr="disabled" wire:target="save">
                        {{ $savedCount > 0 ? __('Simpan Perubahan') : __('Simpan Absensi') }}
                    </flux:button>
                </div>
            </flux:card>
        @endif
    @endif
</section>