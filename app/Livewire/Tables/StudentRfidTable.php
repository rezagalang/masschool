<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\AcademicPeriod;
use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\RfidBalance;
use App\Models\Kesiswaan\RfidLog;
use App\Models\Kesiswaan\Student;
use App\Models\Kesiswaan\StudentHistory;
use App\Models\Unit;
use App\Support\AcademicYear;
use App\Support\UnitDatabase;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Datatable Aktivasi RFID siswa + modal aktivasi/ganti/nonaktif (satuan & massal). */
class StudentRfidTable extends Component
{
    use WithDataTable;

    // Batas siswa per proses scan massal (aktivasi/ganti) dan nonaktifkan massal.
    private const MAX_BULK_SCAN = 100;

    private const MAX_BULK_DEACTIVATE = 1000;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // Filter: id periode akademik (database pusat), id kelas, status RFID ('' | '1' | '0').
    #[Url(as: 'periode', except: '')]
    public string $period = '';

    #[Url(as: 'kelas', except: '')]
    public string $classId = '';

    #[Url(as: 'rfid', except: '')]
    public string $rfidStatus = '';

    // Siswa yang sedang diproses di modal satuan.
    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public string $studentLabel = '';

    #[Locked]
    public string $currentRfid = '';

    #[Locked]
    public int $currentSaldo = 0;

    // Modal massal: daftar siswa (scan berurutan) dan id siswa yang akan dinonaktifkan.
    /** @var array<int, array{id:int, nis:string, nama:string, rfid:string}> */
    #[Locked]
    public array $bulkRows = [];

    /** @var array<int, int> */
    #[Locked]
    public array $bulkDeactivateIds = [];

    // form.rfid = modal satuan; form.bulk[id] = nomor RFID per siswa di modal massal.
    // (Nama diawali "form." supaya tidak mereset halaman/pilihan oleh WithDataTable.)
    public array $form = ['rfid' => '', 'bulk' => []];

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;

        // Default: periode aktif (kosong = semua periode, lewat tombol Reset).
        if ($this->period === '' && ! request()->has('periode')) {
            $this->period = (string) (AcademicYear::active()?->id ?? '');
        }
    }

    // Periode yang dipakai untuk kolom kelas & log: pilihan filter, atau periode aktif.
    #[Computed]
    public function contextPeriodId(): ?int
    {
        return $this->period !== '' ? (int) $this->period : AcademicYear::active()?->id;
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        $contextPeriod = $this->contextPeriodId ?? 0;

        return Student::query()
            ->with([
                'rfidBalance',
                'histories' => fn ($h) => $h->where('academic_period_id', $contextPeriod)->with('classroom'),
            ])
            ->when(
                $this->period !== '' || $this->classId !== '',
                fn (Builder $q) => $q->whereHas('histories', function ($h) {
                    $h->when($this->period !== '', fn ($x) => $x->where('academic_period_id', (int) $this->period))
                        ->when($this->classId !== '', fn ($x) => $x->where('class_id', (int) $this->classId));
                }),
            )
            ->when($this->rfidStatus === '1', fn (Builder $q) => $q->whereNotNull('rfid')->where('rfid', '!=', ''))
            ->when($this->rfidStatus === '0', fn (Builder $q) => $q->where(
                fn ($w) => $w->whereNull('rfid')->orWhere('rfid', ''),
            ));
    }

    // Cari NIS, nama, atau nomor RFID (tanpa NISN).
    protected function dataTableSearch(Builder $query, string $term): void
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('nis', 'like', $like)
                ->orWhere('a_namalengkap', 'like', $like)
                ->orWhere('rfid', 'like', $like);
        });
    }

    protected function dataTableDefaultSort(): array
    {
        return ['a_namalengkap', 'asc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Aktivasi RFID'),
            'subtitle' => __('Kelola kartu RFID siswa unit :unit.', ['unit' => $this->unitName]),
            'actions' => [],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'siswa';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari NIS, nama, RFID...');
    }

    // Menu ini tidak menghapus siswa.
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
            'rfidStatus' => [
                'label' => __('Semua status RFID'),
                'options' => ['1' => __('Sudah aktif'), '0' => __('Belum aktif')],
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
            ],

            'kelas' => [
                'label' => 'Kelas',
                'type' => 'muted',
                'value' => fn ($s) => $s->histories->first()?->classroom?->kelas ?? '-',
            ],

            'rfid' => [
                'label' => 'No. RFID',
                'sortable' => true,
                'type' => 'mono',
                'value' => fn ($s) => $s->rfid ?: '-',
            ],

            'saldo' => [
                'label' => 'Saldo',
                'align' => 'end',
                'value' => fn ($s) => $s->rfid
                    ? 'Rp ' . number_format((int) ($s->rfidBalance?->saldo ?? 0), 0, ',', '.')
                    : '-',
            ],

            'status_rfid' => [
                'label' => 'Status RFID',
                'type' => 'badge',
                'badge' => fn ($s) => filled($s->rfid)
                    ? ['label' => __('Aktif'), 'color' => 'green', 'dot' => true]
                    : ['label' => __('Belum aktif'), 'color' => 'zinc', 'dot' => true],
            ],

            'actions' => [
                'label' => 'Aksi',
                'type' => 'actions',
                'align' => 'end',
                'export' => false,
                'actions' => fn ($s) => filled($s->rfid)
                    ? [
                        ['label' => __('Ganti RFID'), 'icon' => 'arrow-path', 'click' => "openForm({$s->id})"],
                        ['separator' => true],
                        ['label' => __('Nonaktifkan RFID'), 'icon' => 'x-circle', 'click' => "confirmDeactivate({$s->id})"],
                    ]
                    : [
                        ['label' => __('Aktivasi RFID'), 'icon' => 'credit-card', 'click' => "openForm({$s->id})"],
                    ],
            ],
        ];
    }

    /* Export */
    protected function dataTableTitle(): string
    {
        return 'Aktivasi RFID - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'rfid-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }

    /* Aksi satuan */
    public function openForm(int $id): void
    {
        $this->loadStudent($id);
        $this->form = ['rfid' => '', 'bulk' => []];
        $this->resetErrorBag();

        Flux::modal('rfid-form')->show();
    }

    public function confirmDeactivate(int $id): void
    {
        $this->loadStudent($id);

        Flux::modal('rfid-deactivate')->show();
    }

    // Aktivasi / ganti RFID satu siswa.
    public function save(): void
    {
        $this->form['rfid'] = trim((string) ($this->form['rfid'] ?? ''));

        $this->validate();

        $student = Student::query()->findOrFail($this->editingId);
        $new = $this->form['rfid'];

        if ($new === (string) ($student->rfid ?? '')) {
            $this->addError('form.rfid', __('RFID ini sudah aktif pada siswa tersebut.'));

            return;
        }

        $owner = Student::query()->where('rfid', $new)->whereKeyNot($student->id)->first();

        if ($owner) {
            $this->addError('form.rfid', __('RFID sudah dipakai oleh :nama dengan NIS :nis.', [
                'nama' => $owner->a_namalengkap,
                'nis' => $owner->nis,
            ]));

            return;
        }

        $this->applyChanges([$student->id => $new]);

        Flux::modal('rfid-form')->close();
        Flux::toast(variant: 'success', text: __('RFID :nama berhasil diperbarui.', ['nama' => $student->a_namalengkap]));

        $this->finish();
    }

    public function deactivate(): void
    {
        $student = Student::query()->findOrFail($this->editingId);

        $this->applyChanges([$student->id => null]);

        Flux::modal('rfid-deactivate')->close();
        Flux::toast(variant: 'success', text: __('RFID :nama berhasil dinonaktifkan.', ['nama' => $student->a_namalengkap]));

        $this->finish();
    }

    /* Aksi massal */

    // Buka modal scan berurutan untuk siswa yang dicentang.
    public function openBulkRfid(): void
    {
        $ids = $this->selectedIds(self::MAX_BULK_SCAN);

        if ($ids === null) {
            Flux::toast(variant: 'danger', text: __('Maksimal :max siswa per proses. Persempit pilihan Anda.', ['max' => self::MAX_BULK_SCAN]));

            return;
        }

        if ($ids === []) {
            return;
        }

        $students = Student::query()
            ->whereIn('id', $ids)
            ->orderBy('a_namalengkap')
            ->get(['id', 'nis', 'a_namalengkap', 'rfid']);

        $this->bulkRows = $students->map(fn ($s) => [
            'id' => (int) $s->id,
            'nis' => (string) $s->nis,
            'nama' => (string) $s->a_namalengkap,
            'rfid' => (string) ($s->rfid ?? ''),
        ])->all();

        // Semua id diinisialisasi agar state di browser berbentuk objek (bukan array jarang).
        $this->form = [
            'rfid' => '',
            'bulk' => $students->mapWithKeys(fn ($s) => [(int) $s->id => ''])->all(),
        ];

        $this->resetErrorBag();

        Flux::modal('rfid-bulk')->show();
    }

    // Simpan semua nomor yang terisi; baris kosong tidak diubah. Semua atau tidak sama sekali.
    public function saveBulk(): void
    {
        $rows = collect($this->bulkRows)->keyBy('id');

        if ($rows->isEmpty()) {
            return;
        }

        $this->resetErrorBag();

        $errors = [];
        $changes = [];   // id siswa => nomor RFID baru
        $unchanged = 0;

        foreach ($rows as $id => $row) {
            $value = trim((string) ($this->form['bulk'][$id] ?? ''));

            if ($value === '') {
                continue;
            }

            if (mb_strlen($value) > 30) {
                $errors["form.bulk.{$id}"] = __('Nomor RFID maksimal 30 karakter.');

                continue;
            }

            if ($value === $row['rfid']) {
                $unchanged++; // kartu yang sama dengan yang sudah aktif: tidak perlu diubah

                continue;
            }

            $changes[(int) $id] = $value;
        }

        // Nomor ganda di dalam daftar.
        $seen = [];

        foreach ($changes as $id => $value) {
            $key = mb_strtolower($value);

            if (isset($seen[$key])) {
                $errors["form.bulk.{$id}"] = __('Nomor ini sama dengan siswa lain di daftar.');
            } else {
                $seen[$key] = $id;
            }
        }

        // Nomor yang dipakai siswa lain di luar daftar yang diubah (yang diubah akan melepas kartunya).
        if ($changes !== []) {
            $owners = Student::query()
                ->whereIn('rfid', array_values($changes))
                ->whereNotIn('id', array_keys($changes))
                ->get(['id', 'nis', 'a_namalengkap', 'rfid'])
                ->keyBy(fn ($s) => mb_strtolower((string) $s->rfid));

            foreach ($changes as $id => $value) {
                $owner = $owners->get(mb_strtolower($value));

                if ($owner && ! isset($errors["form.bulk.{$id}"])) {
                    $errors["form.bulk.{$id}"] = __('RFID sudah dipakai oleh :nama dengan NIS :nis.', [
                        'nama' => $owner->a_namalengkap,
                        'nis' => $owner->nis,
                    ]);
                }
            }
        }

        if ($errors !== []) {
            foreach ($errors as $key => $message) {
                $this->addError($key, $message);
            }

            Flux::toast(variant: 'danger', text: __('Periksa nomor RFID yang bertanda merah. Belum ada yang disimpan.'));

            return;
        }

        if ($changes === []) {
            Flux::toast(
                variant: 'warning',
                text: $unchanged > 0
                    ? __('Tidak ada perubahan: nomor yang diisi sama dengan RFID yang sudah aktif.')
                    : __('Belum ada nomor RFID yang diisi.'),
            );

            return;
        }

        $count = $this->applyChanges($changes);

        Flux::modal('rfid-bulk')->close();
        Flux::toast(variant: 'success', text: __(':count siswa berhasil diperbarui.', ['count' => $count]));

        $this->finish(clearSelection: true);
    }

    // Konfirmasi nonaktifkan massal: hanya siswa terpilih yang sudah punya RFID.
    public function confirmBulkDeactivate(): void
    {
        $ids = $this->selectedIds(self::MAX_BULK_DEACTIVATE);

        if ($ids === null) {
            Flux::toast(variant: 'danger', text: __('Maksimal :max siswa per proses. Persempit pilihan Anda.', ['max' => number_format(self::MAX_BULK_DEACTIVATE, 0, ',', '.')]));

            return;
        }

        $withRfid = $ids === [] ? [] : Student::query()
            ->whereIn('id', $ids)
            ->whereNotNull('rfid')
            ->where('rfid', '!=', '')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($withRfid === []) {
            Flux::toast(variant: 'warning', text: __('Tidak ada siswa terpilih yang memiliki RFID aktif.'));

            return;
        }

        $this->bulkDeactivateIds = $withRfid;

        Flux::modal('rfid-bulk-deactivate')->show();
    }

    public function deactivateBulk(): void
    {
        if ($this->bulkDeactivateIds === []) {
            return;
        }

        $count = $this->applyChanges(array_fill_keys($this->bulkDeactivateIds, null));

        Flux::modal('rfid-bulk-deactivate')->close();
        Flux::toast(variant: 'success', text: __(':count siswa berhasil dinonaktifkan RFID-nya.', ['count' => $count]));

        $this->finish(clearSelection: true);
    }

    // Error validasi hilang otomatis saat diperbaiki.
    public function updatedForm($value, ?string $key = null): void
    {
        if ($key === 'rfid') {
            $this->resetErrorBag('form.rfid');
        } elseif ($key !== null && str_starts_with($key, 'bulk.')) {
            $this->resetErrorBag('form.' . $key);
        }
    }

    protected function rules(): array
    {
        return [
            'form.rfid' => ['required', 'string', 'max:30'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.rfid.required' => __('Nomor RFID wajib diisi.'),
            'form.rfid.max' => __('Nomor RFID maksimal 30 karakter.'),
        ];
    }

    /* Internal */
    private function loadStudent(int $id): void
    {
        $student = Student::query()->with('rfidBalance')->findOrFail($id);

        $this->editingId = $student->id;
        $this->studentLabel = trim(($student->a_namalengkap ?? '') . ' (' . ($student->nis ?? '-') . ')');
        $this->currentRfid = (string) ($student->rfid ?? '');
        $this->currentSaldo = (int) ($student->rfidBalance?->saldo ?? 0);
    }

    /**
     * ID siswa yang dicentang (atau seluruh hasil filter bila "Pilih semua"), selalu dalam
     * lingkup query tabel. null = jumlahnya melebihi $max.
     *
     * @return array<int, int>|null
     */
    private function selectedIds(int $max): ?array
    {
        if (! $this->selectAll && $this->selected === []) {
            return [];
        }

        $query = $this->selectAll
            ? $this->dataTableFilteredQuery()
            : $this->dataTableQuery()->whereKey($this->selected);

        $ids = $query->limit($max + 1)
            ->pluck($query->getModel()->getQualifiedKeyName())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->count() > $max ? null : $ids->all();
    }

    /**
     * Terapkan perubahan RFID dalam satu transaksi (database unit).
     *
     * @param  array<int, string|null>  $changes  id siswa => nomor RFID baru (null = nonaktifkan)
     * @return int jumlah siswa yang benar-benar berubah
     *
     * Saldo dipindahkan dari kartu lama ke kartu baru. Dikerjakan dua tahap (hapus semua saldo
     * lama, lalu tulis saldo baru) agar tukar kartu antar siswa dalam satu proses tidak menimpa saldo.
     */
    private function applyChanges(array $changes): int
    {
        if ($changes === []) {
            return 0;
        }

        return DB::connection(UnitDatabase::CONNECTION)->transaction(function () use ($changes) {
            $students = Student::query()
                ->whereIn('id', array_keys($changes))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $todo = [];

            foreach ($changes as $id => $new) {
                $student = $students->get($id);

                if (! $student) {
                    continue;
                }

                $old = filled($student->rfid) ? (string) $student->rfid : null;

                if ($old === $new) {
                    continue;
                }

                $todo[$id] = ['student' => $student, 'old' => $old, 'new' => $new];
            }

            if ($todo === []) {
                return 0;
            }

            // 1) Baca & hapus saldo kartu lama.
            $oldRfids = collect($todo)->pluck('old')->filter()->values()->all();

            $balances = $oldRfids === []
                ? collect()
                : RfidBalance::query()->whereIn('rfid', $oldRfids)->get()
                    ->keyBy(fn ($b) => mb_strtolower((string) $b->rfid));

            $saldoOf = fn (?string $rfid): int => $rfid === null
                ? 0
                : (int) ($balances->get(mb_strtolower($rfid))?->saldo ?? 0);

            if ($oldRfids !== []) {
                RfidBalance::query()->whereIn('rfid', $oldRfids)->delete();
            }

            // 2) Kosongkan dulu, lalu isi nomor baru (aman untuk tukar kartu).
            Student::query()->whereIn('id', array_keys($todo))->update(['rfid' => null]);

            foreach ($todo as $id => $t) {
                if ($t['new'] !== null) {
                    Student::query()->whereKey($id)->update(['rfid' => $t['new']]);
                }
            }

            // 3) Tulis saldo untuk kartu baru.
            $newBalances = [];

            foreach ($todo as $t) {
                if ($t['new'] !== null) {
                    $newBalances[] = ['rfid' => $t['new'], 'saldo' => $saldoOf($t['old'])];
                }
            }

            if ($newBalances !== []) {
                RfidBalance::query()->upsert($newBalances, ['rfid'], ['saldo']);
            }

            // 4) Catat log per siswa.
            $periodId = $this->contextPeriodId;

            $histories = $periodId
                ? StudentHistory::query()->with('classroom')
                    ->where('academic_period_id', $periodId)
                    ->whereIn('student_id', array_keys($todo))
                    ->get()
                    ->keyBy('student_id')
                : collect();

            $petugas = Str::limit((string) auth()->user()->name, 100, '');
            $now = now();
            $logs = [];

            foreach ($todo as $id => $t) {
                $history = $histories->get($id);

                $logs[] = [
                    'student_id' => $id,
                    'class_id' => $history?->class_id,
                    'rfid' => $t['new'] ?? $t['old'],
                    'saldo' => $saldoOf($t['old']),
                    'nis' => (string) $t['student']->nis,
                    'nama' => Str::limit((string) $t['student']->a_namalengkap, 100, ''),
                    'kelas' => $history?->classroom?->kelas,
                    'academic_period_id' => $periodId,
                    'petugas' => $petugas,
                    'aksi' => match (true) {
                        $t['new'] === null => RfidLog::AKSI_NONAKTIF,
                        $t['old'] === null => RfidLog::AKSI_AKTIVASI,
                        default => RfidLog::AKSI_GANTI,
                    },
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($logs, 200) as $chunk) {
                RfidLog::query()->insert($chunk);
            }

            return count($todo);
        });
    }

    private function finish(bool $clearSelection = false): void
    {
        $this->reset('editingId', 'studentLabel', 'currentRfid', 'currentSaldo', 'bulkRows', 'bulkDeactivateIds');
        $this->form = ['rfid' => '', 'bulk' => []];

        if ($clearSelection) {
            $this->clearSelection();
        }

        $this->refreshDataTable();
    }
}