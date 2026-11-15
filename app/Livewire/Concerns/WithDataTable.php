<?php

namespace App\Livewire\Concerns;

use App\Support\DataTable\DataTableExporter;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DataTable Livewire + Flux UI: pencarian, sorting, paginasi, seleksi baris, bulk delete & export.
 *
 * @property LengthAwarePaginator|Collection $rows
 * @property int $total
 * @property array $pageIds
 * @property int $selectedCount
 * @property array $columns
 * @property array $ui
 * @property array $displayColumns
 * @property array $hideableColumns
 * @property array $sortState
 * @property array $perPageOptions
 * @property bool $canDelete
 */
trait WithDataTable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    // '10' | '25' | '50' | '100' | 'all'
    #[Url(as: 'show', except: '10')]
    public string $perPage = '10';

    #[Url(except: '')]
    public string $sortBy = '';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    /** @var array<int, string> ID baris yang dipilih (lintas halaman). */
    public array $selected = [];

    // Kolom yang ditampilkan (diatur lewat menu "Kolom"); null = semua. Disimpan di session.
    #[Session]
    public ?array $visibleColumns = null;

    // true = seluruh hasil filter dipilih (tidak hanya yang ada di $selected).
    public bool $selectAll = false;

    // Baris yang akan dihapus lewat modal konfirmasi (hapus satu data).
    #[Locked]
    public string $deleteId = '';

    #[Locked]
    public string $deleteLabel = '';

    // Batas baris saat "Semua" dipilih (0 = tanpa batas).
    protected int $dataTableMaxAll = 0;

    // Batas baris export PDF (dompdf berat di memori).
    protected int $dataTableMaxPdfRows = 2000;

    /* Hook yang diimplementasi / dioverride oleh komponen */

    abstract protected function dataTableQuery(): Builder;

    abstract protected function dataTableColumns(): array;

    /** Header halaman: judul, subjudul, & tombol aksi (kosongkan untuk menyembunyikan). */
    protected function dataTableHeader(): array
    {
        return ['title' => $this->dataTableTitle(), 'subtitle' => null, 'actions' => []];
    }

    // Filter dropdown: ['property' => ['label' => 'Label', 'options' => [value => label]]]
    protected function dataTableFilters(): array
    {
        return [];
    }

    // Nama entitas untuk teks UI.
    protected function dataTableEntity(): string
    {
        return 'data';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari...');
    }

    // Label baris pada dialog konfirmasi hapus.
    protected function dataTableRowLabel($row): string
    {
        return (string) $row->getKey();
    }

    // Kolom yang dicari bila dataTableSearch() tidak dioverride.
    protected function dataTableSearchable(): array
    {
        return [];
    }

    protected function dataTableSearch(Builder $query, string $term): void
    {
        $columns = $this->dataTableSearchable();

        if ($columns === []) {
            return;
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function (Builder $q) use ($columns, $like) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', $like);
            }
        });
    }

    // [key kolom, 'asc'|'desc'] saat belum ada sorting dari pengguna.
    protected function dataTableDefaultSort(): array
    {
        return [];
    }

    // Properti filter (kecuali $search) yang direset tombol "Reset".
    protected function dataTableFilterProperties(): array
    {
        return array_keys($this->dataTableFilters());
    }

    protected function dataTablePerPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function dataTableFilename(): string
    {
        return 'export-' . now()->format('Ymd-His');
    }

    protected function dataTableTitle(): string
    {
        return 'Data';
    }

    protected function dataTableCsvDelimiter(): string
    {
        return ',';
    }

    protected function dataTablePdfOrientation(): string
    {
        return 'landscape';
    }

    // Otorisasi bulk delete.
    protected function dataTableCanDelete(): bool
    {
        return true;
    }

    // Eksekusi penghapusan; override untuk soft delete/event model.
    protected function dataTableDelete(Builder $query): int
    {
        return $query->delete();
    }

    /* Query */
    protected function dataTableFilteredQuery(): Builder
    {
        $query = $this->dataTableQuery();
        $term = trim($this->search);

        if ($term !== '') {
            $this->dataTableSearch($query, $term);
        }

        return $query;
    }

    protected function dataTableOrderedQuery(): Builder
    {
        $query = $this->dataTableFilteredQuery();
        [$key, $direction] = $this->resolveSort();

        if ($key !== null) {
            $column = $this->dataTableColumns()[$key]['sort_by'] ?? $key;
            $query->orderBy($column, $direction);
        }

        // Urutan sekunder supaya paginasi stabil.
        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    private function resolveSort(): array
    {
        $columns = $this->dataTableColumns();

        if ($this->sortBy !== '' && ($columns[$this->sortBy]['sortable'] ?? false)) {
            return [$this->sortBy, $this->sortDirection === 'desc' ? 'desc' : 'asc'];
        }

        $default = $this->dataTableDefaultSort();
        $key = $default[0] ?? null;

        if ($key !== null && ! isset($columns[$key])) {
            $key = null;
        }

        return [$key, ($default[1] ?? 'asc') === 'desc' ? 'desc' : 'asc'];
    }

    private function perPageSize(): ?int
    {
        if ($this->perPage === 'all') {
            return null;
        }

        $options = $this->dataTablePerPageOptions();

        return in_array((int) $this->perPage, $options, true) ? (int) $this->perPage : $options[0];
    }

    /* Data untuk view */
    #[Computed]
    public function rows(): LengthAwarePaginator|Collection
    {
        $query = $this->dataTableOrderedQuery();
        $size = $this->perPageSize();

        if ($size !== null) {
            return $query->paginate($size);
        }

        return $this->dataTableMaxAll > 0
            ? $query->limit($this->dataTableMaxAll)->get()
            : $query->get();
    }

    #[Computed]
    public function total(): int
    {
        if ($this->rows instanceof LengthAwarePaginator) {
            return $this->rows->total();
        }

        return $this->dataTableMaxAll > 0
            ? $this->dataTableFilteredQuery()->count()
            : $this->rows->count();
    }

    // ID (string) baris yang tampil di halaman ini.
    #[Computed]
    public function pageIds(): array
    {
        $items = $this->rows instanceof LengthAwarePaginator
            ? $this->rows->items()
            : $this->rows;

        return collect($items)->map(fn ($row) => (string) $row->getKey())->all();
    }

    #[Computed]
    public function selectedCount(): int
    {
        return $this->selectAll ? $this->total : count($this->selected);
    }

    // Key kolom yang boleh disembunyikan lewat menu "Kolom".
    private function hideableColumnKeys(): array
    {
        return array_keys(array_filter(
            $this->dataTableColumns(),
            fn ($c) => ($c['show'] ?? true) !== false
                && ($c['type'] ?? '') !== 'actions'
                && ($c['hideable'] ?? true) !== false,
        ));
    }

    public function bootedWithDataTable(): void
    {
        $this->visibleColumns ??= $this->hideableColumnKeys();
    }

    // Kolom yang benar-benar tampil di tabel.
    #[Computed]
    public function displayColumns(): array
    {
        $hideable = $this->hideableColumnKeys();
        $visible = $this->visibleColumns ?? $hideable;

        return array_filter(
            $this->dataTableColumns(),
            fn ($c, $key) => ($c['show'] ?? true) !== false
                && (! in_array($key, $hideable, true) || in_array($key, $visible, true)),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    // [key => label] untuk menu "Kolom".
    #[Computed]
    public function hideableColumns(): array
    {
        $columns = $this->dataTableColumns();

        return collect($this->hideableColumnKeys())
            ->mapWithKeys(fn ($key) => [$key => $columns[$key]['label']])
            ->all();
    }

    // Konfigurasi UI untuk <x-datatable>.
    #[Computed]
    public function ui(): array
    {
        return [
            'header' => $this->dataTableHeader(),
            'filters' => $this->dataTableFilters(),
            'entity' => $this->dataTableEntity(),
            'placeholder' => $this->dataTablePlaceholder(),
        ];
    }

    #[Computed]
    public function columns(): array
    {
        return $this->dataTableColumns();
    }

    // [key aktif, arah] untuk indikator panah di header.
    #[Computed]
    public function sortState(): array
    {
        return $this->resolveSort();
    }

    #[Computed]
    public function perPageOptions(): array
    {
        return $this->dataTablePerPageOptions();
    }

    #[Computed]
    public function canDelete(): bool
    {
        return $this->dataTableCanDelete();
    }

    /* Aksi */
    public function sort(string $column): void
    {
        if (! ($this->dataTableColumns()[$column]['sortable'] ?? false)) {
            return;
        }

        [$active, $direction] = $this->resolveSort();

        if ($active === $column) {
            $this->sortDirection = $direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortBy = $column;
        $this->resetPage();
    }

    // Reset halaman + pilihan setiap kali search/filter berubah.
    public function updatedWithDataTable(string $name): void
    {
        if ($name === 'selected' || $name === 'selectAll'
            || str_starts_with($name, 'selected.') || str_starts_with($name, 'paginators')
            || str_starts_with($name, 'visibleColumns')
            || $name === 'form' || str_starts_with($name, 'form.')) {
            return;
        }

        if ($name === 'perPage') {
            $valid = array_map('strval', $this->dataTablePerPageOptions());
            $valid[] = 'all';

            if (! in_array($this->perPage, $valid, true)) {
                $this->perPage = $valid[0];
            }

            $this->resetPage();

            return;
        }

        $this->resetPage();
        $this->clearSelection();
    }

    // Kosongkan pencarian + semua filter.
    public function resetFilters(): void
    {
        $this->reset(['search', ...$this->dataTableFilterProperties()]);
        $this->resetPage();
        $this->clearSelection();
    }

    // Dipanggil saat user mencentang/menghapus centang baris manual.
    public function updatedSelected(): void
    {
        $this->selectAll = false;
    }

    // Centang / hapus centang semua baris di halaman ini.
    public function togglePage(): void
    {
        if ($this->selectAll) {
            $this->clearSelection();

            return;
        }

        $ids = $this->pageIds;

        if ($ids !== [] && array_diff($ids, $this->selected) === []) {
            $this->selected = array_values(array_diff($this->selected, $ids));
        } else {
            $this->selected = array_values(array_unique([...$this->selected, ...$ids]));
        }
    }

    // Pilih seluruh hasil filter (semua halaman).
    public function selectAllResults(): void
    {
        $this->selectAll = true;
        $this->selected = [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function confirmBulkDelete(): void
    {
        if (! $this->dataTableCanDelete() || $this->selectedCount === 0) {
            return;
        }

        Flux::modal('datatable-bulk-delete')->show();
    }

    public function bulkDelete(): void
    {
        abort_unless($this->dataTableCanDelete(), 403);

        if (! $this->selectAll && $this->selected === []) {
            return;
        }

        // Validasi query dasar untuk memastikan ID luar scope/filter aman dari penghapusan.
        $query = $this->selectAll
            ? $this->dataTableFilteredQuery()
            : $this->dataTableQuery()->whereKey($this->selected);

        $deleted = $this->dataTableDelete($query);

        $this->clearSelection();

        Flux::modal('datatable-bulk-delete')->close();
        Flux::toast(variant: 'success', text: __(':count data berhasil dihapus.', ['count' => $deleted]));

        $this->afterDelete();
    }

    // Hapus satu data: buka dialog konfirmasi.
    public function confirmDelete(string $id): void
    {
        abort_unless($this->dataTableCanDelete(), 403);

        $row = $this->dataTableQuery()->findOrFail($id);

        $this->deleteId = (string) $row->getKey();
        $this->deleteLabel = $this->dataTableRowLabel($row);

        Flux::modal('datatable-delete')->show();
    }

    public function deleteRow(): void
    {
        abort_unless($this->dataTableCanDelete(), 403);

        if ($this->deleteId !== '') {
            $this->dataTableDelete($this->dataTableQuery()->whereKey($this->deleteId));
            $this->selected = array_values(array_diff($this->selected, [$this->deleteId]));

            Flux::toast(variant: 'success', text: __('Data berhasil dihapus.'));
        }

        $this->reset('deleteId', 'deleteLabel');

        Flux::modal('datatable-delete')->close();

        $this->afterDelete();
    }

    // Segarkan data; bila halaman terakhir jadi kosong, mundur satu halaman.
    protected function afterDelete(): void
    {
        $this->refreshDataTable();

        if ($this->rows instanceof LengthAwarePaginator && $this->rows->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
            $this->refreshDataTable();
        }
    }

    // Buang cache computed setelah data berubah (hapus/ubah dari aksi lain).
    protected function refreshDataTable(): void
    {
        unset($this->rows, $this->total, $this->pageIds, $this->selectedCount);
    }

    // Export baris terpilih; bila tidak ada pilihan, export semua hasil filter.
    public function export(string $format): ?StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $query = $this->dataTableOrderedQuery();

        if (! $this->selectAll && $this->selected !== []) {
            $query->whereKey($this->selected);
        }

        if ($format === 'pdf' && (clone $query)->count() > $this->dataTableMaxPdfRows) {
            Flux::toast(
                variant: 'danger',
                text: __('PDF maksimal :max baris. Persempit filter atau gunakan Excel/CSV.', [
   'max' => number_format($this->dataTableMaxPdfRows, 0, ',', '.'),
                ]),
            );

            return null;
        }

        return DataTableExporter::download(
            format: $format,
            query: $query,
            columns: $this->dataTableColumns(),
            filename: $this->dataTableFilename(),
            title: $this->dataTableTitle(),
            options: [
                'delimiter' => $this->dataTableCsvDelimiter(),
                'orientation' => $this->dataTablePdfOrientation(),
            ],
        );
    }
}