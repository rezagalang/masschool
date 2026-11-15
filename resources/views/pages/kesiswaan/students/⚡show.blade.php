<?php

use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Siswa')] class extends Component {
    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    #[Locked]
    public int $studentId = 0;

    public function mount($unit, $student): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;
        $this->studentId = (int) ($student instanceof Student ? $student->getKey() : $student);

        // 404 bila siswa tidak ada di database.
        $this->student;
    }

    #[Computed]
    public function student(): Student
    {
        return Student::findOrFail($this->studentId);
    }

    // Nilai field siap tampil: opsi select jadi label, tanggal jadi d/m/Y.
    public function display(string $name, array $spec): string
    {
        $value = $this->student->getAttributes()[$name] ?? null;

        if ($value === null || $value === '') {
            return '-';
        }

        return match ($spec['type']) {
            'date' => Carbon::parse($value)->format('d/m/Y'),
            'select' => (string) ($spec['options'][$value] ?? $value),
            default => (string) $value,
        };
    }
}; ?>

@php
    $tabs = \App\Support\Kesiswaan\StudentFields::tabs();
    $student = $this->student;
    $name = $student->a_namalengkap ?? $student->nis;

@endphp

<section class="ss-sheet mx-auto flex w-full max-w-5xl flex-1 flex-col gap-4">
    <!-- Aturan cetak: sembunyikan sidebar/tombol, paksa terang, cegah section terpotong -->
    <style>
        @media print {
            @page { size: A4; margin: 12mm; }

            html, body {
                background: #fff !important;
                color: #18181b !important;
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
            }

            [data-flux-sidebar],
            [data-flux-sidebar-backdrop],
            [data-flux-header],
            [data-flux-toast],
            ui-toast,
            .ss-noprint { display: none !important; }

            [data-flux-main] {
                display: block !important;
                width: auto !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .ss-sheet {
                display: block !important;
                width: 100% !important;
                max-width: none !important;
                font-size: 11px;
                line-height: 1.35;
            }

            .ss-sheet * { color: #18181b !important; box-shadow: none !important; }
            .ss-sheet .ss-label { color: #52525b !important; }

            .ss-top {
                display: flex !important;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 2px solid #18181b;
            }

            .ss-section {
                display: block !important;
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 10px;
                background: #fff !important;
                border: 1px solid #a1a1aa !important;
                border-radius: 6px !important;
                overflow: visible !important;
            }

            .ss-head {
                background: #f4f4f5 !important;
                border-bottom: 1px solid #a1a1aa !important;
                padding: 6px 12px !important;
            }

            .ss-chip { background: #e4e4e7 !important; }

            .ss-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 6px 20px !important;
                padding: 10px 12px !important;
            }

            .ss-field { break-inside: avoid; page-break-inside: avoid; overflow-wrap: anywhere; }
            .ss-full { grid-column: 1 / -1 !important; }
        }
    </style>

    <!-- Header -->
    <div class="ss-top flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <flux:avatar :name="$name" color="auto" size="lg" circle />

            <div class="min-w-0">
                <flux:heading size="xl" level="1" class="break-words">{{ $name }}</flux:heading>

                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <flux:text size="sm">NIS {{ $student->nis }} &middot; {{ $unitName }}</flux:text>

                    <flux:badge size="sm" :color="$student->isActive() ? 'green' : 'zinc'">
                        {{ $student->isActive() ? __('Aktif') : __('Nonaktif') }}
                    </flux:badge>
                </div>

                <p class="mt-1 hidden text-xs print:block">
                    {{ __('Dicetak') }}: {{ now()->format('d/m/Y H:i') }}
                </p>
            </div>
        </div>

        <div class="ss-noprint flex gap-2 print:hidden">
            <flux:button variant="danger" icon="arrow-left"
                :href="route('kesiswaan.students.index', ['unit' => $unitCode])" wire:navigate>
                {{ __('Kembali') }}
            </flux:button>
            <flux:button icon="printer" x-on:click="window.print()">{{ __('Cetak') }}</flux:button>
            <flux:button variant="primary" icon="pencil-square"
                :href="route('kesiswaan.students.edit', ['unit' => $unitCode, 'student' => $student->id])"
                wire:navigate>
                {{ __('Ubah') }}
            </flux:button>
        </div>
    </div>

    <!-- Data siswa per section -->
    @foreach ($tabs as $item)
        <x-section-card :label="$item['label']" :icon="$item['icon'] ?? 'document-text'" :count="count($item['fields'])">
            @foreach ($item['fields'] as $field => $spec)
                <div @class(['ss-field min-w-0', 'ss-full sm:col-span-2' => $spec['span'] === 'full'])>
                    <dt class="ss-label text-xs text-zinc-500 dark:text-zinc-400">{{ $spec['label'] }}</dt>
                    <dd class="mt-0.5 break-words font-medium">{{ $this->display($field, $spec) }}</dd>
                </div>
            @endforeach
        </x-section-card>
    @endforeach
</section>