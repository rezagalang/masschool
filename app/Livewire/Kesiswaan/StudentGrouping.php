<?php

namespace App\Livewire\Kesiswaan;

use App\Models\AcademicPeriod;
use App\Models\Kesiswaan\Classroom;
use App\Models\Kesiswaan\Student;
use App\Models\Kesiswaan\StudentHistory;
use App\Models\Unit;
use App\Support\AcademicYear;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengelompokan siswa: daftar semua siswa (kiri) dan anggota kelas terpilih pada satu
 * tahun ajaran (kanan). Centang = masukkan, hapus centang = keluarkan; "Simpan" menyamakan
 * anggota kelas di database dengan daftar kanan.
 */
class StudentGrouping extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    // Batas "Pilih semua hasil" agar request tetap ringan.
    private const MAX_SELECT_ALL = 1000;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // id periode akademik (database pusat) dan id kelas (database unit).
    public string $period = '';

    public string $classId = '';

    // Panel kiri: pencarian + status kelas pada periode terpilih (unassigned | assigned | all).
    public string $search = '';

    public string $status = 'unassigned';

    /** @var array<int, string> ID siswa yang akan menjadi anggota kelas (panel kanan). */
    public array $selected = [];

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;

        $this->period = (string) (AcademicYear::active()?->id ?? $this->periods->keys()->first() ?? '');
        $this->classId = (string) ($this->classes->first()?->id ?? '');

        $this->loadSelected();
    }

    /* Data pendukung */
    #[Computed]
    public function periods(): Collection
    {
        return AcademicPeriod::query()->latestFirst()->get()->keyBy('id'); // database pusat
    }

    #[Computed]
    public function classes(): Collection
    {
        return Classroom::query()->orderBy('nourut')->orderBy('kelas')->get();
    }

    // Panel kiri: semua siswa (cari NIS / nama saja), terfilter status kelas pada periode.
    #[Computed]
    public function students(): LengthAwarePaginator
    {
        return $this->studentQuery()->paginate(self::PER_PAGE, ['id', 'nis', 'a_namalengkap']);
    }

    // Panel kanan: siswa yang dipilih.
    #[Computed]
    public function selectedStudents(): Collection
    {
        if ($this->selected === []) {
            return collect();
        }

        return Student::query()
            ->whereIn('id', array_map('intval', $this->selected))
            ->orderBy('a_namalengkap')
            ->orderBy('id')
            ->get(['id', 'nis', 'a_namalengkap']);
    }

    // [student_id => class_id] pada periode terpilih, untuk siswa yang sedang tampil di kedua panel.
    #[Computed]
    public function placements(): array
    {
        if ($this->period === '') {
            return [];
        }

        $ids = array_unique([
            ...$this->students->pluck('id')->all(),
            ...array_map('intval', $this->selected),
        ]);

        if ($ids === []) {
            return [];
        }

        return StudentHistory::query()
            ->forPeriod((int) $this->period)
            ->whereIn('student_id', $ids)
            ->pluck('class_id', 'student_id')
            ->all();
    }

    // ID anggota kelas yang tersimpan di database (string, agar sebanding dengan $selected).
    #[Computed]
    public function saved(): array
    {
        return $this->fetchSaved();
    }

    // Ringkasan perubahan yang belum disimpan.
    #[Computed]
    public function summary(): array
    {
        $added = array_values(array_diff($this->selected, $this->saved));
        $removed = array_values(array_diff($this->saved, $this->selected));

        $moved = 0;

        if ($added !== [] && $this->period !== '') {
            $moved = StudentHistory::query()
                ->forPeriod((int) $this->period)
                ->where('class_id', '!=', (int) $this->classId)
                ->whereIn('student_id', array_map('intval', $added))
                ->count();
        }

        return [
            'added' => count($added),
            'removed' => count($removed),
            'moved' => $moved,
            'changed' => $added !== [] || $removed !== [],
        ];
    }

    /* Query */
    protected function studentQuery(): Builder
    {
        $term = trim($this->search);
        $inPeriod = fn () => StudentHistory::query()->forPeriod((int) $this->period)->select('student_id');

        return Student::query()
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                // Sengaja hanya NIS & nama (tanpa NISN).
                $q->where(fn (Builder $w) => $w->where('nis', 'like', $like)->orWhere('a_namalengkap', 'like', $like));
            })
            ->when($this->status === 'unassigned', fn (Builder $q) => $q->whereNotIn('id', $inPeriod()))
            ->when($this->status === 'assigned', fn (Builder $q) => $q->whereIn('id', $inPeriod()))
            ->orderBy('a_namalengkap')
            ->orderBy('id');
    }

    protected function fetchSaved(): array
    {
        if ($this->period === '' || $this->classId === '') {
            return [];
        }

        return StudentHistory::query()
            ->forPeriod((int) $this->period)
            ->where('class_id', (int) $this->classId)
            ->pluck('student_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    // Muat anggota kelas tersimpan ke panel kanan (membuang perubahan yang belum disimpan).
    protected function loadSelected(): void
    {
        $this->selected = $this->fetchSaved();
    }

    /* Hook perubahan input */
    public function updatedPeriod(): void
    {
        $this->resetPage();
        $this->loadSelected();
    }

    public function updatedClassId(): void
    {
        $this->loadSelected();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (! in_array($this->status, ['unassigned', 'assigned', 'all'], true)) {
            $this->status = 'unassigned';
        }

        $this->resetPage();
    }

    /* Aksi panel */
    // Centang / hapus centang semua siswa di halaman kiri ini.
    public function togglePage(): void
    {
        $ids = $this->students->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($ids !== [] && array_diff($ids, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique([...$this->selected, ...$ids]));
        }
    }

    // Masukkan semua siswa hasil filter (semua halaman) ke daftar kanan.
    public function selectAllResults(): void
    {
        $ids = $this->studentQuery()
            ->limit(self::MAX_SELECT_ALL)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->selected = array_values(array_unique([...$this->selected, ...$ids]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function resetSelection(): void
    {
        $this->loadSelected();
    }

    public function save(): void
    {
        $this->validate([
            'period' => ['required', Rule::exists(AcademicPeriod::class, 'id')], // database pusat
            'classId' => ['required', Rule::exists(Classroom::class, 'id')], // database unit
        ], [
            'period.required' => __('Tahun ajaran wajib dipilih.'),
            'period.exists' => __('Tahun ajaran tidak valid.'),
            'classId.required' => __('Kelas wajib dipilih.'),
            'classId.exists' => __('Kelas tidak valid.'),
        ]);

        $periodId = (int) $this->period;
        $classId = (int) $this->classId;

        // Hanya siswa yang benar-benar ada.
        $wanted = Student::query()
            ->whereIn('id', array_map('intval', $this->selected))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $saved = array_map('intval', $this->fetchSaved());
        $remove = array_values(array_diff($saved, $wanted));
        $add = array_values(array_diff($wanted, $saved));

        $moved = 0;
        $inserted = 0;

        StudentHistory::query()->getConnection()->transaction(
            function () use ($periodId, $classId, $remove, $add, &$moved, &$inserted) {
                // Keluarkan dari kelas ini.
                foreach (array_chunk($remove, 500) as $chunk) {
                    StudentHistory::query()
                        ->forPeriod($periodId)
                        ->where('class_id', $classId)
                        ->whereIn('student_id', $chunk)
                        ->delete();
                }

                // Masukkan ke kelas ini; siswa yang sudah di kelas lain pada periode yang sama dipindahkan.
                $now = now();

                foreach (array_chunk($add, 500) as $chunk) {
                    $existing = StudentHistory::query()
                        ->forPeriod($periodId)
                        ->whereIn('student_id', $chunk)
                        ->pluck('id', 'student_id');

                    if ($existing->isNotEmpty()) {
                        $moved += StudentHistory::query()
                            ->whereKey($existing->values()->all())
                            ->update(['class_id' => $classId, 'updated_at' => $now]);
                    }

                    $rows = collect($chunk)
                        ->reject(fn (int $studentId) => $existing->has($studentId))
                        ->map(fn (int $studentId) => [
                            'academic_period_id' => $periodId,
                            'student_id' => $studentId,
                            'class_id' => $classId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->values()
                        ->all();

                    if ($rows !== []) {
                        StudentHistory::insert($rows);
                        $inserted += count($rows);
                    }
                }
            }
        );

        unset($this->saved, $this->summary, $this->students, $this->selectedStudents, $this->placements);

        $this->loadSelected();

        $parts = array_filter([
            $inserted > 0 ? __(':count siswa masuk', ['count' => $inserted]) : null,
            $moved > 0 ? __(':count siswa dipindahkan dari kelas lain', ['count' => $moved]) : null,
            count($remove) > 0 ? __(':count siswa keluar', ['count' => count($remove)]) : null,
        ]);

        Flux::toast(
            variant: 'success',
            text: $parts === []
                ? __('Tidak ada perubahan.')
                : __('Pengelompokan disimpan: ') . implode(', ', $parts) . '.',
        );
    }
}