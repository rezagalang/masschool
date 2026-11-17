<?php

use App\Http\Controllers\SettingsController;
use App\Models\AcademicPeriod;
use App\Support\AcademicYear;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

// Manajemen tahun ajaran global (khusus Kepala Sekolah). Data periode diinput manual via DB.
// Halaman ini untuk menampilkan, mengaktifkan periode (otomatis by tanggal/manual), & buka/tutup PPDB.
new #[Title('Tahun Ajaran')] #[Layout('layouts::app.header')] class extends Component {
    #[Locked]
    public string $backUrl = '';

    public function mount(): void
    {
        $this->guard();

        $this->backUrl = app(SettingsController::class)->backUrl(request());
    }

    // Data halaman: aktifkan periode hari ini via active() sebelum baca daftar.
    #[Computed]
    public function overview(): array
    {
        $active = AcademicYear::active();

        return [
            'active' => $active,
            'admissionNames' => AcademicYear::admissionNames(),
            'periods' => AcademicPeriod::query()->latestFirst()->get()->groupBy('start_year'),
        ];
    }

    // Aktifkan periode secara manual.
    public function activate(int $id): void
    {
        $this->guard();

        $period = AcademicPeriod::findOrFail($id);
        AcademicYear::setActive($period);

        $this->notify("Periode {$period->label()} diaktifkan.");
    }

    public function toggleAdmission(int $id): void
    {
        $this->guard();

        $period = AcademicPeriod::findOrFail($id);

        if (!$period->canAdmit()) {
            $this->notify('PPDB hanya diatur pada Semester 1 tiap tahun ajaran.', 'danger');

            return;
        }

        $open = AcademicYear::toggleAdmission($period);

        $this->notify($open ? "PPDB dibuka untuk tahun ajaran {$period->name}." : "PPDB tahun ajaran {$period->name} ditutup.");
    }

    // Validasi akses dicek di tiap aksi karena request Livewire bypass middleware route.
    protected function guard(): void
    {
        abort_unless(auth()->user()?->hasRole('Kepala Sekolah'), 403);
    }

    protected function notify(string $message, string $variant = 'success'): void
    {
        unset($this->overview);

        Flux::toast(text: $message, variant: $variant);
    }
}; ?>


@php
    ['active' => $active, 'admissionNames' => $admissionNames, 'periods' => $periods] = $this->overview;
    $total = $periods->flatten(1)->count();
@endphp

<flux:main container>
    <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 py-10">
        @include('pages.admin.settings.nav', ['tab' => 'academic-periods'])

        <!-- Ringkasan -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Periode aktif</flux:text>
                <flux:heading size="xl" class="tracking-tight text-green-600 dark:text-green-400">
                    {{ $active ? $active->name : '-' }}
                </flux:heading>
                <flux:text size="sm">{{ $active ? 'Semester ' . $active->semester : 'Belum ada' }}</flux:text>
            </flux:card>
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Periode PPDB</flux:text>
                <flux:heading size="xl" @class([
                    'tracking-tight',
                    'text-sky-600 dark:text-sky-400' => $admissionNames,
                    'text-zinc-400' => !$admissionNames,
                ])>
                    {{ $admissionNames ? implode(', ', $admissionNames) : 'Ditutup' }}
                </flux:heading>
                <flux:text size="sm">
                    {{ $admissionNames ? 'Bisa dipilih di form pendaftaran' : 'Formulir pendaftaran tidak bisa diakses' }}
                </flux:text>
            </flux:card>
            <flux:card class="flex flex-col gap-1 !p-4 shadow-xs">
                <flux:text size="sm">Total periode</flux:text>
                <flux:heading size="xl" class="tracking-tight">{{ $total }}</flux:heading>
                <flux:text size="sm">{{ $periods->count() }} tahun ajaran</flux:text>
            </flux:card>
        </div>

        <!-- Penjelasan -->
        <flux:callout icon="information-circle" heading="Periode aktif otomatis">
            <flux:callout.text>
                Data tahun ajaran dan tanggalnya diisi lewat database. Periode yang rentang tanggalnya mencakup
                hari ini otomatis menjadi periode aktif (dicek tiap hari), dan periode aktif juga bisa diganti manual
                lewat tombol Aktifkan. PPDB dibuka/ditutup manual per tahun
                ajaran lewat Semester 1, dan bisa dibuka untuk beberapa tahun ajaran sekaligus.
            </flux:callout.text>
        </flux:callout>

        <!-- Daftar per tahun ajaran -->
        @forelse ($periods as $yearKey => $items)
            <flux:card wire:key="year-{{ $yearKey }}"
                class="divide-y divide-zinc-200 overflow-hidden !p-0 shadow-xs dark:divide-white/10">
                @php
                    // PPDB berlaku per tahun ajaran dan disimpan di baris Semester 1.
                    $admissionPeriod = $items->first(fn($item) => $item->canAdmit());
                    $ppdbOpen = $admissionPeriod?->is_admission ?? false;
                @endphp

                <div
                    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 bg-zinc-50 px-4 py-3 dark:bg-zinc-800/60">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading class="font-semibold tracking-tight">
                            Tahun Ajaran {{ $items->first()->name }}
                        </flux:heading>

                        @if ($ppdbOpen)
                            <flux:badge size="sm" color="sky" icon="user-plus" inset="top bottom">
                                PPDB dibuka</flux:badge>
                        @endif
                    </div>

                    @if ($admissionPeriod)
                        <flux:button type="button" size="sm" icon="user-plus"
                            :variant="$ppdbOpen ? 'danger' : 'primary'"
                            wire:click="toggleAdmission({{ $admissionPeriod->id }})">
                            {{ $ppdbOpen ? 'Tutup PPDB' : 'Buka PPDB' }}
                        </flux:button>
                    @endif
                </div>

                @foreach ($items as $period)
                    <div wire:key="period-{{ $period->id }}"
                        class="flex flex-wrap items-center gap-x-4 gap-y-3 p-4 transition-colors duration-150 hover:bg-zinc-50 sm:flex-nowrap dark:hover:bg-zinc-800/60">
                        <flux:avatar icon="calendar-days" :color="$period->is_active ? 'green' : 'zinc'"
                            class="shrink-0 !rounded-lg {{ $period->is_active ? '' : 'opacity-60' }}" />

                        <div class="min-w-0 flex-1 space-y-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:heading class="font-semibold tracking-tight">
                                    Semester {{ $period->semester }}
                                </flux:heading>

                                @if ($period->is_active)
                                    <flux:badge size="sm" color="green" icon="check-circle" inset="top bottom">
                                        Aktif</flux:badge>
                                @endif
                            </div>

                            <flux:text size="sm">
                                @if ($period->start_date && $period->end_date)
                                    {{ $period->start_date->format('d/m/Y') }} &ndash;
                                    {{ $period->end_date->format('d/m/Y') }}
                                @else
                                    Tanggal belum diatur
                                @endif
                            </flux:text>
                        </div>

                        @unless ($period->is_active)
                            <div class="ms-auto shrink-0">
                                <flux:tooltip
                                    content="Tanggal periode tetap jadi acuan: jika di luar tanggalnya, jadwal harian bisa mengembalikan periode aktif.">
                                    <flux:button type="button" size="sm" icon="power" variant="ghost"
                                        wire:click="activate({{ $period->id }})">
                                        Aktifkan
                                    </flux:button>
                                </flux:tooltip>
                            </div>
                        @endunless
                    </div>
                @endforeach
            </flux:card>
        @empty
            <flux:callout icon="calendar-days" heading="Belum ada periode">
                <flux:callout.text>
                    Isi data tahun ajaran di database (tabel academic_periods) terlebih dahulu.
                </flux:callout.text>
            </flux:callout>
        @endforelse
    </div>
</flux:main>
