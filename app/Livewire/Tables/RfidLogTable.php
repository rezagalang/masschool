<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\AcademicPeriod;
use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\RfidLog;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Laporan: log perubahan RFID (aktivasi, ganti, nonaktif). Hanya baca. */
class RfidLogTable extends Component
{
    use WithDataTable;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // Filter: id periode akademik (database pusat), id kelas, dan jenis aksi.
    #[Url(as: 'periode', except: '')]
    public string $period = '';

    #[Url(as: 'kelas', except: '')]
    public string $classId = '';

    #[Url(except: '')]
    public string $aksi = '';

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        return RfidLog::query()
            ->when($this->period !== '', fn (Builder $q) => $q->where('academic_period_id', (int) $this->period))
            ->when($this->classId !== '', fn (Builder $q) => $q->where('class_id', (int) $this->classId))
            ->when(
                in_array($this->aksi, [RfidLog::AKSI_AKTIVASI, RfidLog::AKSI_GANTI, RfidLog::AKSI_NONAKTIF], true),
                fn (Builder $q) => $q->where('aksi', $this->aksi),
            );
    }

    // Cari NIS, nama, nomor RFID, atau petugas.
    protected function dataTableSearchable(): array
    {
        return ['nis', 'nama', 'rfid', 'petugas'];
    }

    // Terbaru di atas.
    protected function dataTableDefaultSort(): array
    {
        return ['waktu', 'desc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Log Perubahan RFID'),
            'subtitle' => __('Riwayat aktivasi, penggantian, dan penonaktifan kartu RFID unit :unit.', ['unit' => $this->unitName]),
            'actions' => [],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'log';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari NIS, nama, RFID, petugas...');
    }

    // Log tidak boleh dihapus dari menu ini.
    protected function dataTableCanDelete(): bool
    {
        return false;
    }

    protected function dataTableFilters(): array
    {
        return [
            'period' => [
                'label' => __('Semua periode'),
                'options' => AcademicPeriod::query()->latestFirst()->get()
                    ->mapWithKeys(fn ($p) => [(string) $p->id => $p->label()])
                    ->all(),
            ],
            'classId' => [
                'label' => __('Semua kelas'),
                'options' => Classroom::query()->orderBy('nourut')->orderBy('kelas')->get()
                    ->mapWithKeys(fn ($c) => [(string) $c->id => $c->kelas . ($c->jenjang ? ' (' . $c->jenjang . ')' : '')])
                    ->all(),
            ],
            'aksi' => [
                'label' => __('Semua aksi'),
                'options' => [
                    RfidLog::AKSI_AKTIVASI => __('Aktivasi'),
                    RfidLog::AKSI_GANTI => __('Ganti'),
                    RfidLog::AKSI_NONAKTIF => __('Nonaktif'),
                ],
            ],
        ];
    }

    protected function dataTableColumns(): array
    {
        return [
            'waktu' => [
                'label' => 'Waktu',
                'sortable' => true,
                'sort_by' => 'created_at',
                'type' => 'muted',
                'value' => fn ($l) => $l->created_at?->format('d/m/Y H:i') ?? '-',
            ],

            'nis' => ['label' => 'NIS', 'sortable' => true, 'type' => 'mono'],

            'nama' => ['label' => 'Nama Siswa', 'sortable' => true],

            'kelas' => ['label' => 'Kelas', 'type' => 'muted'],

            'rfid' => ['label' => 'No. RFID', 'sortable' => true, 'type' => 'mono'],

            'aksi' => [
                'label' => 'Aksi',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($l) => match ($l->aksi) {
                    RfidLog::AKSI_AKTIVASI => ['label' => __('Aktivasi'), 'color' => 'green', 'dot' => true],
                    RfidLog::AKSI_GANTI => ['label' => __('Ganti'), 'color' => 'sky', 'dot' => true],
                    RfidLog::AKSI_NONAKTIF => ['label' => __('Nonaktif'), 'color' => 'red', 'dot' => true],
                    default => ['label' => (string) $l->aksi, 'color' => 'zinc'],
                },
            ],

            // Saldo kartu lama saat aksi dilakukan (ikut dipindahkan saat ganti, dihapus saat nonaktif).
            'saldo' => [
                'label' => 'Saldo',
                'align' => 'end',
                'sortable' => true,
                'value' => fn ($l) => 'Rp ' . number_format((float) $l->saldo, 0, ',', '.'),
            ],

            'petugas' => ['label' => 'Petugas', 'sortable' => true, 'type' => 'muted'],
        ];
    }

    /* Export */
    protected function dataTableTitle(): string
    {
        return 'Log Perubahan RFID - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'log-rfid-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }
}