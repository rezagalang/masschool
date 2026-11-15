<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Kesiswaan\Registration;
use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use App\Support\UnitDatabase;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Datatable Calon Siswa (PPDB) dengan modal tinjau & persetujuan. */
class RegistrationTable extends Component
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

    // Calon siswa yang sedang ditinjau di modal.
    #[Locked]
    public ?int $reviewId = null;

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        return Registration::query()
            ->when($this->tahunajaran !== '', fn ($q) => $q->where('tahunajaran', $this->tahunajaran))
            ->when($this->status !== '', fn ($q) => $q->where('status_daftar', $this->status === '1'));
    }

    protected function dataTableSearch(Builder $query, string $term): void
    {
        $query->search($term); // Registration::scopeSearch
    }

    protected function dataTableDefaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Daftar Calon Siswa'),
            'actions' => [],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'calon siswa';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari no. pendaftaran, nama, NIK, email...');
    }

    protected function dataTableFilters(): array
    {
        return [
            'tahunajaran' => [
                'label' => __('Semua tahun ajaran'),
                'options' => Registration::query()
                    ->whereNotNull('tahunajaran')
                    ->distinct()
                    ->orderByDesc('tahunajaran')
                    ->pluck('tahunajaran')
                    ->mapWithKeys(fn ($t) => [$t => $t])
                    ->all(),
            ],
            'status' => [
                'label' => __('Semua status'),
                'options' => ['0' => __('Menunggu'), '1' => __('Disetujui')],
            ],
        ];
    }

    protected function dataTableColumns(): array
    {
        return [
            'no_pendaftaran' => ['label' => 'No. Pendaftaran', 'sortable' => true, 'type' => 'mono'],

            'a_namalengkap' => [
                'label' => 'Nama Calon Siswa',
                'sortable' => true,
                'type' => 'avatar',
                'subtitle' => 'email',
            ],

            'a_jk' => [
                'label' => 'L/P',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($r) => match ($r->a_jk) {
                    'LAKI-LAKI' => ['label' => 'L', 'color' => 'sky'],
                    'PEREMPUAN' => ['label' => 'P', 'color' => 'pink'],
                    default => null,
                },
            ],

            'tahunajaran' => ['label' => 'Thn. Ajaran', 'sortable' => true, 'type' => 'muted'],

            'created_at' => [
                'label' => 'Tgl. Daftar',
                'sortable' => true,
                'type' => 'muted',
                'value' => fn ($r) => $r->created_at?->format('d/m/Y H:i'),
            ],

            'status_daftar' => [
                'label' => 'Status',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($r) => $r->status_daftar
                    ? ['label' => __('Disetujui'), 'color' => 'green', 'dot' => true]
                    : ['label' => __('Menunggu'), 'color' => 'amber', 'dot' => true],
                'value' => fn ($r) => $r->status_daftar ? 'Disetujui' : 'Menunggu',
            ],

            // Hanya ikut di export (tidak tampil di tabel).
            'nik' => ['label' => 'NIK', 'show' => false],
            'email' => ['label' => 'Email', 'show' => false],
            'b_notelp' => ['label' => 'No. Telp/HP', 'show' => false],

            // Menu aksi per baris (tidak diexport).
            'actions' => [
                'label' => 'Aksi',
                'type' => 'actions',
                'align' => 'end',
                'export' => false,
                'actions' => fn ($r) => $r->isPending()
                    ? [
                        ['label' => __('Tinjau'), 'icon' => 'eye', 'click' => "open({$r->id})"],
                        ['separator' => true],
                        ['delete' => true],
                    ]
                    : [
                        ['label' => __('Detail'), 'icon' => 'eye', 'click' => "open({$r->id})"],
                    ],
            ],
        ];
    }

    /* Export & hapus */
    protected function dataTableTitle(): string
    {
        return 'Daftar Calon Siswa - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'calon-siswa-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }

    protected function dataTableRowLabel($row): string
    {
        return $row->a_namalengkap ?: (string) $row->no_pendaftaran;
    }

    // Hanya calon siswa belum disetujui yang dapat dihapus beserta file bukti transfernya.
    protected function dataTableDelete(Builder $query): int
    {
        $rows = $query->where('status_daftar', false)->get();

        foreach ($rows as $row) {
            if ($row->bukti_transfer) {
                Storage::disk('local')->delete($row->bukti_transfer);
            }
        }

        return Registration::query()->whereKey($rows->modelKeys())->delete();
    }

    /* Tinjau & setujui */
    #[Computed]
    public function review(): ?Registration
    {
        return $this->reviewId ? Registration::find($this->reviewId) : null;
    }

    public function open(int $id): void
    {
        $this->reviewId = $id;
        $this->resetValidation();

        Flux::modal('review')->show();
    }

    public function approve(): void
    {
        $registration = $this->review;

        abort_unless($registration, 404);

        $approved = DB::connection(UnitDatabase::CONNECTION)->transaction(function () use ($registration) {
            // Kunci baris untuk mencegah persetujuan ganda oleh admin berbeda.
            $fresh = Registration::lockForUpdate()->findOrFail($registration->id);

            if (! $fresh->isPending()) {
                return false;
            }

            // Salin semua kolom pendaftaran ke siswa, kecuali kolom khusus pendaftaran.
            $data = Arr::except($fresh->getAttributes(), [
                'id', 'bukti_transfer', 'status_daftar', 'created_at', 'updated_at',
            ]);
            // NIS sementara (wajib & unik), nanti diganti "C-{id}".
            $data['nis'] = 'TMP-' . $fresh->id . '-' . uniqid();
            $data['aktif'] = '1';
            $data['registrasi_id'] = $fresh->id;

            $student = Student::create($data);
            $student->update(['nis' => 'C-' . $student->id]);

            $fresh->update(['status_daftar' => true]);

            return true;
        });

        if ($approved) {
            Flux::toast(text: __('Calon siswa disetujui dan ditambahkan sebagai siswa.'), variant: 'success');
        } else {
            Flux::toast(text: __('Calon siswa ini sudah diproses oleh admin lain.'), variant: 'warning');
        }

        Flux::modal('review')->close();

        $this->reviewId = null;

        unset($this->review);
        $this->refreshDataTable();
    }
}