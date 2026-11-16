<?php

namespace App\Livewire\Kesiswaan;

use App\Models\AcademicPeriod;
use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\Student;
use App\Models\Kesiswaan\StudentAttendance;
use App\Models\Kesiswaan\StudentHistory;
use App\Models\Unit;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Absensi manual harian: guru memilih kelas + tanggal, daftar siswa kelas itu (sesuai
 * pengelompokan pada tahun ajaran yang mencakup tanggal tsb) tampil dengan status default
 * "Hadir". Jika absensi tanggal itu sudah ada, datanya dimuat untuk diubah. "Simpan"
 * membuat/memperbarui baris (upsert per siswa + tanggal).
 */
class StudentAttendanceForm extends Component
{
    // Satu pilihan per siswa; tiap status menyalakan tepat satu kolom boolean di tabel.
    public const STATUS_COLUMNS = [
        'hadir' => 'hadir',
        'terlambat' => 'terlambat',
        'sakit' => 'sakit',
        'izin' => 'ijin_tidakmasuk',
        'izin_pulang' => 'ijin_pulang',
        'alpha' => 'alpha',
    ];

    public const STATUSES = ['hadir', 'terlambat', 'sakit', 'izin', 'izin_pulang', 'alpha'];

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    public string $classId = '';

    // Format Y-m-d.
    public string $date = '';

    /**
     * Isian per siswa, kunci = student_id.
     *
     * @var array<int, array{status: string, keterangan: string}>
     */
    public array $rows = [];

    /** @var array<int, int> student_id yang sudah punya catatan absensi pada tanggal ini. */
    #[Locked]
    public array $saved = [];

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;

        $this->date = now()->toDateString();
        $this->classId = (string) ($this->classes->first()?->id ?? '');

        $this->load();
    }

    /* Data pendukung */
    #[Computed]
    public function classes(): Collection
    {
        return Classroom::query()->orderBy('nourut')->orderBy('kelas')->get();
    }

    // Periode akademik (database pusat) yang mencakup tanggal terpilih; null jika di luar semua periode.
    #[Computed]
    public function period(): ?AcademicPeriod
    {
        return AcademicPeriod::query()
            ->whereDate('start_date', '<=', $this->date)
            ->whereDate('end_date', '>=', $this->date)
            ->orderByDesc('start_date')
            ->first();
    }

    // Siswa di kelas terpilih pada periode tanggal tsb.
    #[Computed]
    public function members(): Collection
    {
        if (! $this->period || $this->classId === '') {
            return collect();
        }

        return Student::query()
            ->whereIn('id', StudentHistory::query()
                ->forPeriod($this->period->id)
                ->where('class_id', (int) $this->classId)
                ->select('student_id'))
            ->orderBy('a_namalengkap')
            ->orderBy('id')
            ->get(['id', 'nis', 'a_namalengkap']);
    }

    // Rekap dari isian saat ini (belum tentu sudah disimpan).
    #[Computed]
    public function summary(): array
    {
        $counts = collect($this->rows)->countBy('status');

        return collect(self::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    /* Muat data */
    protected function blankRow(): array
    {
        return ['status' => 'hadir', 'keterangan' => ''];
    }

    // Isi $rows dari database (membuang perubahan yang belum disimpan).
    protected function load(): void
    {
        unset($this->period, $this->members, $this->summary);

        $records = $this->members->isEmpty()
            ? collect()
            : StudentAttendance::query()
                ->onDate($this->date)
                ->whereIn('student_id', $this->members->pluck('id'))
                ->get()
                ->keyBy('student_id');

        $this->rows = $this->members->mapWithKeys(function (Student $student) use ($records) {
            $record = $records->get($student->id);

            return [$student->id => $record ? [
                'status' => $record->status(),
                'keterangan' => (string) $record->keterangan,
            ] : $this->blankRow()];
        })->all();

        $this->saved = $records->keys()->map(fn ($id) => (int) $id)->values()->all();

        $this->resetValidation();
    }

    /* Hook perubahan input */
    public function updatedClassId(): void
    {
        $this->load();
    }

    public function updatedDate(): void
    {
        $date = $this->parseDate($this->date);

        // Tanggal tidak valid atau di masa depan: kembali ke hari ini.
        $this->date = ($date && $date->lte(now()->endOfDay()) ? $date : now())->toDateString();

        $this->load();
    }

    // Dipanggil untuk perubahan "rows.{id}.field".
    public function updatedRows($value, string $key): void
    {
        [$studentId, $field] = array_pad(explode('.', $key, 2), 2, null);

        if ($field === 'status' && isset($this->rows[$studentId]) && ! in_array($value, self::STATUSES, true)) {
            $this->rows[$studentId]['status'] = 'hadir';
        }

        unset($this->summary);
    }

    /* Aksi */
    // Geser tanggal (mis. -1 = kemarin); tidak boleh melewati hari ini.
    public function shiftDate(int $days): void
    {
        $target = Carbon::parse($this->date)->addDays($days);

        if ($target->gt(now()->endOfDay())) {
            return;
        }

        $this->date = $target->toDateString();
        $this->load();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
        $this->load();
    }

    // Semua siswa Hadir (keterangan dikosongkan).
    public function markAllPresent(): void
    {
        $this->rows = collect($this->rows)->map(fn () => $this->blankRow())->all();

        unset($this->summary);
    }

    public function resetChanges(): void
    {
        $this->load();
    }

    public function save(): void
    {
        $this->validate([
            'classId' => ['required', Rule::exists(Classroom::class, 'id')], // database unit
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'rows.*.status' => ['required', Rule::in(self::STATUSES)],
            'rows.*.keterangan' => ['nullable', 'string', 'max:50'],
        ], [
            'classId.required' => __('Kelas wajib dipilih.'),
            'classId.exists' => __('Kelas tidak valid.'),
            'date.required' => __('Tanggal wajib diisi.'),
            'date.date_format' => __('Tanggal tidak valid.'),
            'date.before_or_equal' => __('Tanggal tidak boleh melewati hari ini.'),
            'rows.*.status.required' => __('Status wajib dipilih.'),
            'rows.*.status.in' => __('Status tidak valid.'),
            'rows.*.keterangan.max' => __('Keterangan maksimal :max karakter.'),
        ]);

        $period = $this->period;

        if (! $period) {
            Flux::toast(variant: 'danger', text: __('Tanggal ini tidak berada di tahun ajaran mana pun.'));

            return;
        }

        // Hanya siswa yang memang anggota kelas ini (abaikan kunci $rows yang dimanipulasi).
        $now = now();
        $classId = (int) $this->classId;

        $payload = $this->members->map(function (Student $student) use ($period, $classId, $now) {
            $row = $this->rows[$student->id] ?? $this->blankRow();
            $status = $row['status'];
            $note = trim((string) ($row['keterangan'] ?? ''));

            // Tepat satu kolom status bernilai true.
            $flags = collect(self::STATUS_COLUMNS)
                ->mapWithKeys(fn (string $column, string $key) => [$column => $status === $key])
                ->all();

            return [
                'academic_period_id' => $period->id,
                'semester' => (string) $period->semester,
                'student_id' => $student->id,
                'class_id' => $classId,
                'tgl_absen' => $this->date,
                ...$flags,
                'keterangan' => $note === '' ? null : $note,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        if ($payload === []) {
            Flux::toast(variant: 'warning', text: __('Belum ada siswa di kelas ini pada tahun ajaran tersebut.'));

            return;
        }

        $updateColumns = [
            'academic_period_id', 'semester', 'class_id',
            'hadir', 'terlambat', 'ijin_pulang', 'sakit', 'ijin_tidakmasuk', 'alpha',
            'keterangan', 'updated_at',
        ];

        StudentAttendance::query()->getConnection()->transaction(function () use ($payload, $updateColumns) {
            foreach (array_chunk($payload, 200) as $chunk) {
                StudentAttendance::upsert($chunk, ['student_id', 'tgl_absen'], $updateColumns);
            }
        });

        $this->load();

        Flux::toast(
            variant: 'success',
            text: __('Absensi :tanggal disimpan (:count siswa).', [
                'tanggal' => Carbon::parse($this->date)->translatedFormat('d F Y'),
                'count' => count($payload),
            ]),
        );
    }

    protected function parseDate(string $value): ?CarbonInterface
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}