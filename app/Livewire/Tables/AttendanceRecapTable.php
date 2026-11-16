<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\AcademicPeriod;
use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\Student;
use App\Models\Kesiswaan\StudentAttendance;
use App\Models\Kesiswaan\StudentHistory;
use App\Models\Unit;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Laporan: rekap absensi siswa per rentang tanggal. Satu baris = satu siswa, kolom = jumlah
 * hari per status. Daftar siswa mengikuti pengelompokan kelas pada tahun ajaran yang
 * mencakup tanggal akhir (atau tanggal awal bila tanggal akhir di luar periode).
 */
class AttendanceRecapTable extends Component
{
    use WithDataTable;

    // Kolom status di tabel student_attendances (urutan tampilan).
    private const STATUS_COLUMNS = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'sakit' => 'Sakit',
        'ijin_tidakmasuk' => 'Izin',
        'ijin_pulang' => 'Izin Pulang',
        'alpha' => 'Alpha',
    ];

    // Dihitung sebagai "masuk sekolah" untuk persentase kehadiran.
    private const PRESENT_COLUMNS = ['hadir', 'terlambat', 'ijin_pulang'];

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // Rentang tanggal (Y-m-d) dan filter kelas.
    #[Url(as: 'dari', except: '')]
    public string $from = '';

    #[Url(as: 'sampai', except: '')]
    public string $to = '';

    #[Url(as: 'kelas', except: '')]
    public string $classId = '';

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;

        $this->normalizeDates();
    }

    /* Tanggal */
    public function updatedFrom(): void
    {
        $this->normalizeDates();
    }

    public function updatedTo(): void
    {
        $this->normalizeDates();
    }

    public function thisMonth(): void
    {
        $this->setRange(now()->startOfMonth(), now());
    }

    public function lastMonth(): void
    {
        $last = now()->startOfMonth()->subMonthNoOverflow();

        $this->setRange($last, $last->copy()->endOfMonth());
    }

    // Dari awal semester aktif sampai hari ini.
    public function thisSemester(): void
    {
        $period = AcademicYear::active();

        if (! $period || ! $period->start_date) {
            return;
        }

        $this->setRange($period->start_date, $period->end_date && $period->end_date->lt(now()) ? $period->end_date : now());
    }

    protected function setRange(CarbonInterface $from, CarbonInterface $to): void
    {
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();

        $this->normalizeDates();
        $this->resetPage();
        $this->clearSelection();
    }

    // Pastikan tanggal valid, tidak melewati hari ini, dan awal <= akhir.
    protected function normalizeDates(): void
    {
        $today = now()->startOfDay();

        $to = $this->parseDate($this->to) ?? $today;

        if ($to->gt($today)) {
            $to = $today->copy();
        }

        $from = $this->parseDate($this->from) ?? $to->copy()->startOfMonth();

        if ($from->gt($to)) {
            $from = $to->copy();
        }

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    protected function parseDate(string $value): ?CarbonInterface
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /* Data pendukung */
    // Periode akademik (database pusat) yang menentukan keanggotaan kelas.
    #[Computed]
    public function period(): ?AcademicPeriod
    {
        $covering = fn (string $date) => AcademicPeriod::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('start_date')
            ->first();

        return $covering($this->to) ?? $covering($this->from);
    }

    // Subquery id siswa anggota kelas (atau semua kelas) pada periode tsb.
    protected function memberIds(): Builder
    {
        return StudentHistory::query()
            ->forPeriod($this->period?->id ?? 0)
            ->when($this->classId !== '', fn (Builder $q) => $q->where('class_id', (int) $this->classId))
            ->select('student_id');
    }

    // Total seluruh siswa pada filter saat ini.
    #[Computed]
    public function summary(): array
    {
        $sums = collect(self::STATUS_COLUMNS)
            ->keys()
            ->map(fn (string $column) => "coalesce(sum({$column}), 0) as {$column}")
            ->implode(', ');

        // toBase(): hasil mentah tanpa cast model.
        $row = StudentAttendance::query()
            ->whereIn('student_id', $this->memberIds())
            ->whereBetween('tgl_absen', [$this->from, $this->to])
            ->toBase()
            ->selectRaw("count(*) as total, {$sums}")
            ->first();

        $result = ['total' => (int) ($row->total ?? 0)];

        foreach (array_keys(self::STATUS_COLUMNS) as $column) {
            $result[$column] = (int) ($row->{$column} ?? 0);
        }

        $result['students'] = Student::query()->whereIn('id', $this->memberIds())->count();
        $result['persen'] = self::percent(
            array_sum(array_map(fn (string $c) => $result[$c], self::PRESENT_COLUMNS)),
            $result['total'],
        );

        return $result;
    }

    protected static function percent(int $present, int $total): string
    {
        return $total === 0 ? '-' : number_format($present / $total * 100, 1, ',', '.') . '%';
    }

    /* Data tabel */
    protected function dataTableQuery(): Builder
    {
        $period = $this->period?->id ?? 0;

        $query = Student::query()
            ->select(['students.id', 'students.nis', 'students.a_namalengkap'])
            ->with(['histories' => fn ($h) => $h->where('academic_period_id', $period)->with('classroom')])
            ->whereIn('students.id', $this->memberIds());

        // Hitung per status lewat subquery (satu query, bisa di-sort dan di-export).
        $count = fn (?string $column) => StudentAttendance::query()
            ->selectRaw('count(*)')
            ->whereColumn('student_attendances.student_id', 'students.id')
            ->whereBetween('student_attendances.tgl_absen', [$this->from, $this->to])
            ->when($column, fn (Builder $q) => $q->where('student_attendances.' . $column, true));

        $query->selectSub($count(null), 'total_n');

        foreach (array_keys(self::STATUS_COLUMNS) as $column) {
            $query->selectSub($count($column), $column . '_n');
        }

        return $query;
    }

    // Cari NIS atau nama.
    protected function dataTableSearchable(): array
    {
        return ['students.nis', 'students.a_namalengkap'];
    }

    protected function dataTableDefaultSort(): array
    {
        return ['a_namalengkap', 'asc'];
    }

    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Rekap Absensi Siswa'),
            'subtitle' => __('Rekap kehadiran siswa unit :unit per rentang tanggal.', ['unit' => $this->unitName]),
            'actions' => [],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'siswa';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari NIS atau nama...');
    }

    // Laporan: tidak ada hapus.
    protected function dataTableCanDelete(): bool
    {
        return false;
    }

    protected function dataTableFilters(): array
    {
        return [
            'classId' => [
                'label' => __('Semua kelas'),
                'options' => Classroom::query()->orderBy('nourut')->orderBy('kelas')->get()
                    ->mapWithKeys(fn ($c) => [(string) $c->id => $c->kelas . ($c->jenjang ? ' (' . $c->jenjang . ')' : '')])
                    ->all(),
            ],
        ];
    }

    protected function dataTableColumns(): array
    {
        $columns = [
            'nis' => ['label' => 'NIS', 'sortable' => true, 'sort_by' => 'students.nis', 'type' => 'mono'],

            'a_namalengkap' => [
                'label' => 'Nama Siswa',
                'sortable' => true,
                'sort_by' => 'students.a_namalengkap',
            ],

            'kelas' => [
                'label' => 'Kelas',
                'type' => 'muted',
                'value' => fn ($s) => $s->histories->first()?->classroom?->kelas ?? '-',
            ],
        ];

        foreach (self::STATUS_COLUMNS as $column => $label) {
            $alias = $column . '_n';

            $columns[$alias] = [
                'label' => $label,
                'align' => 'end',
                'sortable' => true,
                'sort_by' => $alias,
                'value' => fn ($s) => (int) $s->{$alias},
            ];
        }

        $columns['total_n'] = [
            'label' => 'Total Hari',
            'align' => 'end',
            'sortable' => true,
            'sort_by' => 'total_n',
            'value' => fn ($s) => (int) $s->total_n,
        ];

        // (Hadir + Terlambat + Izin Pulang) / Total Hari.
        $columns['persen'] = [
            'label' => '% Hadir',
            'align' => 'end',
            'value' => fn ($s) => self::percent(
                array_sum(array_map(fn (string $c) => (int) $s->{$c . '_n'}, self::PRESENT_COLUMNS)),
                (int) $s->total_n,
            ),
        ];

        return $columns;
    }

    /* Export */
    protected function dataTableTitle(): string
    {
        return 'Rekap Absensi Siswa - ' . $this->unitName
            . ' (' . Carbon::parse($this->from)->format('d/m/Y') . ' - ' . Carbon::parse($this->to)->format('d/m/Y') . ')';
    }

    protected function dataTableFilename(): string
    {
        return 'rekap-absensi-' . $this->unitCode . '-' . $this->from . '-sd-' . $this->to;
    }
}