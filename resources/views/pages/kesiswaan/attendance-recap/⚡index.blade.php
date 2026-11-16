<?php

use App\Livewire\Tables\AttendanceRecapTable;
use Livewire\Attributes\Title;

new #[Title('Rekap Absensi Siswa')] class extends AttendanceRecapTable {}; ?>

@php
    $period = $this->period;
    $summary = $this->summary;
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');

    $stats = [
        ['key' => 'hadir', 'label' => __('Hadir'), 'color' => 'green'],
        ['key' => 'terlambat', 'label' => __('Terlambat'), 'color' => 'amber'],
        ['key' => 'sakit', 'label' => __('Sakit'), 'color' => 'sky'],
        ['key' => 'ijin_tidakmasuk', 'label' => __('Izin'), 'color' => 'purple'],
        ['key' => 'ijin_pulang', 'label' => __('Izin Pulang'), 'color' => 'zinc'],
        ['key' => 'alpha', 'label' => __('Alpha'), 'color' => 'red'],
    ];
@endphp

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Rekap Absensi Siswa') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Rekap kehadiran siswa unit :unit per rentang tanggal.', ['unit' => $unitName]) }}
        </flux:text>
    </div>

    <!-- Rentang tanggal -->
    <flux:card class="@container flex flex-col gap-4 p-4 sm:p-6">
        <div class="grid gap-4 @md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Dari tanggal') }}</flux:label>
                <x-date-picker wire:model.live="from" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Sampai tanggal') }}</flux:label>
                <x-date-picker wire:model.live="to" />
            </flux:field>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5">
                <flux:button size="sm" variant="subtle" wire:click="thisMonth">{{ __('Bulan ini') }}</flux:button>
                <flux:button size="sm" variant="subtle" wire:click="lastMonth">{{ __('Bulan lalu') }}</flux:button>
                <flux:button size="sm" variant="subtle" wire:click="thisSemester">{{ __('Semester ini') }}</flux:button>
            </div>

            @if ($period)
                <flux:text size="sm">{{ __('Kelas mengikuti :periode', ['periode' => $period->label()]) }}</flux:text>
            @endif
        </div>
    </flux:card>

    @if (! $period)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('Rentang di luar tahun ajaran')">
            <flux:callout.text>
                {{ __('Rentang tanggal ini tidak berada di tahun ajaran mana pun, sehingga daftar siswa tidak bisa ditentukan. Ubah tanggal atau lengkapi data periode akademik.') }}
            </flux:callout.text>
        </flux:callout>
    @else
        <!-- Ringkasan seluruh siswa pada filter ini -->
        <flux:card class="flex flex-col gap-3 p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <flux:heading size="lg">{{ __('Ringkasan') }}</flux:heading>
                <flux:text size="sm">
                    {{ __(':siswa siswa · :total catatan absensi', ['siswa' => $fmt($summary['students']), 'total' => $fmt($summary['total'])]) }}
                </flux:text>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($stats as $stat)
                    <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                        <flux:text size="sm">{{ $stat['label'] }}</flux:text>
                        <div class="text-lg font-semibold tabular-nums">{{ $fmt($summary[$stat['key']]) }}</div>
                    </div>
                @endforeach

                <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-white/10">
                    <flux:text size="sm">{{ __('% Hadir') }}</flux:text>
                    <div class="text-lg font-semibold tabular-nums">{{ $summary['persen'] }}</div>
                </div>
            </div>

            <flux:text size="sm" class="text-zinc-500">
                {{ __('% Hadir = (Hadir + Terlambat + Izin Pulang) dibagi total catatan absensi.') }}
            </flux:text>
        </flux:card>
    @endif

    <x-datatable :table="$this" :header="false"
        loading-targets="search,perPage,sort,gotoPage,nextPage,previousPage,resetFilters,from,to,thisMonth,lastMonth,thisSemester" />
</div>