<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Datatable Student. */
class StudentTable extends Component
{
    use WithDataTable;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    #[Url(as: 'tahun', except: '')]
    public string $tahunajaran = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        return Student::query()
            ->when($this->tahunajaran !== '', fn ($q) => $q->where('tahunajaran', $this->tahunajaran))
            ->when($this->status !== '', fn ($q) => $q->where('aktif', $this->status));
    }

    protected function dataTableSearch(Builder $query, string $term): void
    {
        $query->search($term); // Student::scopeSearch
    }

    protected function dataTableDefaultSort(): array
    {
        return ['a_namalengkap', 'asc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Daftar Siswa'),
            'subtitle' => null,
            'actions' => [[
                'label' => __('Tambah'),
                'icon' => 'plus',
                'variant' => 'primary',
                'href' => route('kesiswaan.students.create', ['unit' => $this->unitCode]),
            ]],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'siswa';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari NIS, nama, email...');
    }

    protected function dataTableFilters(): array
    {
        return [
            'tahunajaran' => [
                'label' => __('Semua tahun ajaran'),
                'options' => Student::query()
                    ->whereNotNull('tahunajaran')
                    ->distinct()
                    ->orderByDesc('tahunajaran')
                    ->pluck('tahunajaran')
                    ->mapWithKeys(fn ($t) => [$t => $t])
                    ->all(),
            ],
            'status' => [
                'label' => __('Semua status'),
                'options' => ['1' => __('Aktif'), '0' => __('Nonaktif')],
            ],
        ];
    }

    protected function dataTableColumns(): array
    {
        return [
            'nis' => ['label' => 'NIS', 'sortable' => true, 'type' => 'mono'],

            'a_namalengkap' => [
                'label' => 'Nama Siswa',
                'sortable' => true,
                'type' => 'avatar',
                'subtitle' => 'email',
            ],

            'a_jk' => [
                'label' => 'L/P',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($s) => match ($s->a_jk) {
                    'LAKI-LAKI' => ['label' => 'L', 'color' => 'sky'],
                    'PEREMPUAN' => ['label' => 'P', 'color' => 'pink'],
                    default => null,
                },
            ],

            'tahunajaran' => ['label' => 'Thn. Ajaran', 'sortable' => true, 'type' => 'muted'],

            'aktif' => [
                'label' => 'Status',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($s) => $s->isActive()
                    ? ['label' => __('Aktif'), 'color' => 'green', 'dot' => true]
                    : ['label' => __('Nonaktif'), 'color' => 'zinc', 'dot' => true],
            ],

            // Hanya ikut di export (tidak tampil di tabel).
            'nisn' => ['label' => 'NISN', 'show' => false],
            'email' => ['label' => 'Email', 'show' => false],
            'b_notelp' => ['label' => 'No. Telp/HP', 'show' => false],

            // Menu aksi per baris (tidak diexport).
            'actions' => [
                'label' => 'Aksi',
                'type' => 'actions',
                'align' => 'end',
                'export' => false,
                'actions' => fn ($s) => [
                    [
                        'label' => __('Detail'),
                        'icon' => 'eye',
                        'href' => route('kesiswaan.students.show', ['unit' => $this->unitCode, 'student' => $s->id]),
                    ],
                    [
                        'label' => __('Ubah'),
                        'icon' => 'pencil-square',
                        'href' => route('kesiswaan.students.edit', ['unit' => $this->unitCode, 'student' => $s->id]),
                    ],
                    ['separator' => true],
                    ['delete' => true],
                ],
            ],
        ];
    }

    /* Export & hapus */
    protected function dataTableTitle(): string
    {
        return 'Daftar Siswa - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'siswa-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }

    protected function dataTableRowLabel($row): string
    {
        return $row->a_namalengkap ?: $row->nis;
    }
}