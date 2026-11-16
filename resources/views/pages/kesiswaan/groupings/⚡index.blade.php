<?php

use App\Livewire\Kesiswaan\StudentGrouping;
use Livewire\Attributes\Title;

new #[Title('Pengelompokan')] class extends StudentGrouping {}; ?>

@php
    $students = $this->students;
    $placements = $this->placements;
    $saved = $this->saved;
    $summary = $this->summary;
    $classNames = $this->classes->mapWithKeys(fn ($c) => [$c->id => $c->kelas]);
    $currentClassName = $classNames[(int) $classId] ?? null;
    $ready = $this->periods->isNotEmpty() && $this->classes->isNotEmpty();

    $pageIds = $students->pluck('id')->map(fn ($id) => (string) $id)->all();
    $pageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
    $pagePartial = ! $pageSelected && array_intersect($pageIds, $selected) !== [];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

<section class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6">
    <!-- Animasi masuk saat isi panel berganti (ganti kelas/tahun ajaran/halaman/status). -->
    <style>
        @keyframes grouping-in {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: no-preference) {
            .grouping-pane { animation: grouping-in .28s ease-out backwards; }
        }
    </style>

    <div>
        <flux:heading size="xl" level="1">{{ __('Pengelompokan Siswa') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Masukkan atau keluarkan siswa dari kelas pada satu tahun ajaran. Unit :unit.', ['unit' => $unitName]) }}
        </flux:text>
    </div>

    @if (! $ready)
        <flux:card>
            <flux:heading>{{ __('Data belum lengkap') }}</flux:heading>
            <flux:text class="mt-1">
                {{ $this->periods->isEmpty()
                    ? __('Belum ada tahun ajaran. Isi data periode akademik terlebih dahulu.')
                    : __('Belum ada kelas. Tambahkan kelas di menu Kelas terlebih dahulu.') }}
            </flux:text>
        </flux:card>
    @else
        {{-- items-stretch: kedua kartu sama tinggi di layar lebar; daftar mengisi sisa ruang --}}
        <div class="grid gap-6 xl:grid-cols-2 xl:items-stretch">
            <!-- Kiri: semua siswa -->
            <flux:card class="@container flex flex-col gap-4 p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Daftar Semua Siswa') }}</flux:heading>
                        <flux:text size="sm">{{ __(':total siswa', ['total' => $fmt($students->total())]) }}</flux:text>
                    </div>

                    @if ($students->total() > 0)
                        <flux:button size="xs" variant="subtle" icon="user-plus" wire:click="selectAllResults">
                            {{ __('Masukkan semua :total', ['total' => $fmt(min($students->total(), 1000))]) }}
                        </flux:button>
                    @endif
                </div>

                <div class="grid gap-3 @md:grid-cols-[1fr_auto]">
                    <flux:input size="sm" wire:model.live.debounce.300ms="search" icon="magnifying-glass" clearable
                        :placeholder="__('Cari NIS atau nama...')" />

                    <flux:select size="sm" wire:model.live="status" class="@md:w-44">
                        <flux:select.option value="unassigned">{{ __('Belum ada kelas') }}</flux:select.option>
                        <flux:select.option value="assigned">{{ __('Sudah ada kelas') }}</flux:select.option>
                        <flux:select.option value="all">{{ __('Semua siswa') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="relative xl:min-h-[26rem] xl:flex-1">
                    <div wire:key="students-{{ $period }}-{{ $status }}-{{ $students->currentPage() }}"
                        wire:loading.class="opacity-60"
                        wire:target="period,status,search,previousPage,nextPage,selectAllResults"
                        class="grouping-pane h-80 overflow-auto rounded-lg border border-zinc-200 px-3 transition-opacity duration-200 sm:h-[26rem] xl:absolute xl:inset-0 xl:h-auto dark:border-white/10
                            [&_thead_th]:text-xs [&_thead_th]:font-semibold [&_thead_th]:uppercase [&_thead_th]:tracking-wide">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column class="w-10">
                                    {{-- wire:key: state checked/indeterminate disegarkan saat berubah (tidak nyangkut di DOM) --}}
                                    <span wire:key="page-toggle-{{ (int) $pageSelected }}-{{ (int) $pagePartial }}-{{ $students->currentPage() }}">
                                        <flux:checkbox :checked="$pageSelected" :indeterminate="$pagePartial"
                                            wire:click="togglePage" aria-label="{{ __('Pilih semua di halaman ini') }}" />
                                    </span>
                                </flux:table.column>
                                <flux:table.column>{{ __('NIS') }}</flux:table.column>
                                <flux:table.column>{{ __('Nama Siswa') }}</flux:table.column>
                                <flux:table.column class="hidden @sm:table-cell">{{ __('Kelas') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($students as $student)
                                    @php
                                        $placed = $placements[$student->id] ?? null;
                                        $checked = in_array((string) $student->id, $selected, true);
                                        $badgeColor = $placed === (int) $classId ? 'green' : 'sky';
                                    @endphp

                                    <flux:table.row :key="'s-' . $student->id"
                                        class="transition-colors duration-200 {{ $checked ? 'bg-zinc-50 dark:bg-white/5' : '' }}">
                                        <flux:table.cell>
                                            <flux:checkbox wire:model.live="selected" value="{{ $student->id }}" />
                                        </flux:table.cell>
                                        <flux:table.cell class="whitespace-nowrap font-mono text-xs">{{ $student->nis }}</flux:table.cell>
                                        <flux:table.cell>
                                            {{ $student->a_namalengkap }}

                                            {{-- Layar kecil: kelas dipindah ke bawah nama --}}
                                            @if ($placed)
                                                <div class="mt-1 @sm:hidden">
                                                    <flux:badge size="sm" variant="pill" :color="$badgeColor">
                                                        {{ $classNames[$placed] ?? '#' . $placed }}
                                                    </flux:badge>
                                                </div>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell class="hidden whitespace-nowrap @sm:table-cell">
                                            @if ($placed)
                                                <flux:badge size="sm" variant="pill" inset="top bottom" :color="$badgeColor">
                                                    {{ $classNames[$placed] ?? '#' . $placed }}
                                                </flux:badge>
                                            @else
                                                <span class="text-zinc-400">-</span>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>

                        @if ($students->isEmpty())
                            <p class="py-10 text-center text-sm text-zinc-500">{{ __('Tidak ada siswa yang cocok.') }}</p>
                        @endif
                    </div>
                </div>

                @if ($students->hasPages())
                    <div class="flex items-center justify-between gap-3 text-sm text-zinc-500">
                        <span class="tabular-nums">
                            {{ $fmt($students->firstItem()) }}–{{ $fmt($students->lastItem()) }}
                            {{ __('dari') }} {{ $fmt($students->total()) }}
                        </span>

                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previousPage"
                                :disabled="$students->onFirstPage()" aria-label="{{ __('Sebelumnya') }}" />
                            <span class="px-2 tabular-nums">{{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
                            <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="nextPage"
                                :disabled="! $students->hasMorePages()" aria-label="{{ __('Berikutnya') }}" />
                        </div>
                    </div>
                @endif
            </flux:card>

            <!-- Kanan: anggota kelas terpilih -->
            <flux:card class="@container flex flex-col gap-4 p-4 sm:p-6">
                <div class="grid gap-3 @md:grid-cols-2">
                    <flux:select size="sm" wire:model.live="classId" :label="__('Masuk ke Kelas')">
                        @foreach ($this->classes as $class)
                            <flux:select.option value="{{ $class->id }}">{{ $class->kelas }} ({{ $class->jenjang }})</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" wire:model.live="period" :label="__('Tahun Ajaran')">
                        @foreach ($this->periods as $p)
                            <flux:select.option value="{{ $p->id }}">{{ $p->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Siswa di Kelas :kelas', ['kelas' => $currentClassName]) }}</flux:heading>
                        <flux:text size="sm">{{ __(':total siswa dipilih', ['total' => $fmt(count($selected))]) }}</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        @if ($summary['added'] > 0)
                            <flux:badge size="sm" color="green">+{{ $summary['added'] }} {{ __('masuk') }}</flux:badge>
                        @endif
                        @if ($summary['removed'] > 0)
                            <flux:badge size="sm" color="red">-{{ $summary['removed'] }} {{ __('keluar') }}</flux:badge>
                        @endif
                    </div>
                </div>

                {{-- Peringatan pindah kelas: membuka/menutup dengan halus, tanpa membuat layout melompat --}}
                <div @class([
                    'grid transition-all duration-300 ease-out -my-2',
                    'grid-rows-[1fr] opacity-100' => $summary['moved'] > 0,
                    'grid-rows-[0fr] opacity-0' => $summary['moved'] === 0,
                ])>
                    <div class="overflow-hidden">
                        <flux:text size="sm" class="py-2 text-amber-600 dark:text-amber-400">
                            {{ __(':count siswa akan dipindahkan dari kelas lain pada tahun ajaran ini.', ['count' => $summary['moved']]) }}
                        </flux:text>
                    </div>
                </div>

                <div class="relative xl:min-h-[26rem] xl:flex-1">
                    <div wire:key="members-{{ $period }}-{{ $classId }}"
                        wire:loading.class="opacity-60"
                        wire:target="classId,period,clearSelection,resetSelection,save"
                        class="grouping-pane h-80 overflow-auto rounded-lg border border-zinc-200 px-3 transition-opacity duration-200 sm:h-[26rem] xl:absolute xl:inset-0 xl:h-auto dark:border-white/10
                            [&_thead_th]:text-xs [&_thead_th]:font-semibold [&_thead_th]:uppercase [&_thead_th]:tracking-wide">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column class="w-10"></flux:table.column>
                                <flux:table.column>{{ __('NIS') }}</flux:table.column>
                                <flux:table.column>{{ __('Nama Siswa') }}</flux:table.column>
                                <flux:table.column class="hidden @sm:table-cell"></flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->selectedStudents as $student)
                                    @php
                                        $isNew = ! in_array((string) $student->id, $saved, true);
                                        $from = $placements[$student->id] ?? null;
                                        $statusLabel = $isNew
                                            ? ($from ? __('Pindah dari :kelas', ['kelas' => $classNames[$from] ?? '#' . $from]) : __('Baru'))
                                            : null;
                                        $statusColor = $from ? 'amber' : 'green';
                                    @endphp

                                    <flux:table.row :key="'p-' . $student->id">
                                        <flux:table.cell>
                                            {{-- Hapus centang = keluarkan dari daftar --}}
                                            <flux:checkbox wire:model.live="selected" value="{{ $student->id }}" />
                                        </flux:table.cell>
                                        <flux:table.cell class="whitespace-nowrap font-mono text-xs">{{ $student->nis }}</flux:table.cell>
                                        <flux:table.cell>
                                            {{ $student->a_namalengkap }}

                                            {{-- Layar kecil: status dipindah ke bawah nama --}}
                                            @if ($statusLabel)
                                                <div class="mt-1 @sm:hidden">
                                                    <flux:badge size="sm" variant="pill" :color="$statusColor">{{ $statusLabel }}</flux:badge>
                                                </div>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell class="hidden whitespace-nowrap @sm:table-cell">
                                            @if ($statusLabel)
                                                <flux:badge size="sm" variant="pill" inset="top bottom" :color="$statusColor">{{ $statusLabel }}</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>

                        @if ($selected === [])
                            <p class="py-10 text-center text-sm text-zinc-500">
                                {{ __('Belum ada siswa. Centang siswa di daftar kiri untuk memasukkannya ke kelas ini.') }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Layar kecil: Simpan di atas dan selebar layar; layar lebar: sejajar di kanan -->
                <div class="flex flex-col-reverse gap-2 @md:flex-row @md:items-center">
                    <div class="flex items-center justify-between gap-1 @md:justify-start">
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetSelection" :disabled="! $summary['changed']">
                            {{ __('Batalkan perubahan') }}
                        </flux:button>
                        <flux:button size="sm" variant="ghost" icon="minus-circle" wire:click="clearSelection" :disabled="$selected === []">
                            {{ __('Kosongkan') }}
                        </flux:button>
                    </div>

                    <flux:button variant="primary" icon="check" class="w-full @md:ml-auto @md:w-auto" wire:click="save"
                        wire:loading.attr="disabled" wire:target="save" :disabled="! $summary['changed']">
                        {{ __('Simpan') }}
                    </flux:button>
                </div>
            </flux:card>
        </div>
    @endif
</section>