<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Kesiswaan\Classroom;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Datatable Kelas + modal. */
class ClassroomTable extends Component
{
    use WithDataTable;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // Filter jenjang.
    #[Url(except: '')]
    public string $jenjang = '';

    // Kelas yang sedang diubah di modal (null = tambah baru).
    #[Locked]
    public ?int $editingId = null;

    /** @var array{kelas: string, jenjang: string, nourut: string|int|null} */
    public array $form = ['kelas' => '', 'jenjang' => '', 'nourut' => ''];

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;
    }

    /* Form */
    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['kelas' => '', 'jenjang' => '', 'nourut' => ''];

        Flux::modal('class-form')->show();
    }

    public function openEdit(int $id): void
    {
        $class = Classroom::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $class->id;
        $this->form = [
            'kelas' => $class->kelas,
            'jenjang' => $class->jenjang,
            'nourut' => $class->nourut === null ? '' : (string) $class->nourut,
        ];

        Flux::modal('class-form')->show();
    }

    public function save(): void
    {
        $this->form['kelas'] = trim((string) ($this->form['kelas'] ?? ''));
        $this->form['jenjang'] = trim((string) ($this->form['jenjang'] ?? ''));

        $data = $this->validate()['form'];

        $payload = [
            'kelas' => $data['kelas'],
            'jenjang' => $data['jenjang'],
            'nourut' => ($data['nourut'] ?? '') === '' ? null : (int) $data['nourut'],
        ];

        if ($this->editingId !== null) {
            Classroom::query()->findOrFail($this->editingId)->update($payload);
            $message = __('Kelas berhasil diperbarui.');
        } else {
            Classroom::query()->create($payload);
            $message = __('Kelas berhasil ditambahkan.');
        }

        $this->editingId = null;
        $this->form = ['kelas' => '', 'jenjang' => '', 'nourut' => ''];

        Flux::modal('class-form')->close();
        Flux::toast(variant: 'success', text: $message);

        $this->refreshDataTable();
    }

    // Error validasi hilang otomatis saat diperbaiki. $key null jika seluruh array `form` diperbarui.
    public function updatedForm($value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        $field = 'form.' . $key;

        if ($this->getErrorBag()->has($field)) {
            $this->validateOnly($field);
        }

        // Jenjang ikut menentukan keunikan nama kelas.
        if ($key === 'jenjang' && $this->getErrorBag()->has('form.kelas')) {
            $this->validateOnly('form.kelas');
        }
    }

    protected function rules(): array
    {
        return [
            'form.kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Classroom::class, 'kelas')
                    ->where('jenjang', $this->form['jenjang'] ?? '')
                    ->ignore($this->editingId),
            ],
            'form.jenjang' => ['required', 'string', 'max:30'],
            'form.nourut' => [
                'nullable',
                'integer',
                'min:0',
                'max:255',
                Rule::unique(Classroom::class, 'nourut')->ignore($this->editingId),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.kelas.required' => __('Nama kelas wajib diisi.'),
            'form.kelas.max' => __('Nama kelas maksimal 50 karakter.'),
            'form.kelas.unique' => __('Kelas ini sudah ada pada jenjang yang sama.'),
            'form.jenjang.required' => __('Jenjang wajib diisi.'),
            'form.jenjang.max' => __('Jenjang maksimal 30 karakter.'),
            'form.nourut.integer' => __('No. urut harus berupa angka.'),
            'form.nourut.min' => __('No. urut minimal 0.'),
            'form.nourut.max' => __('No. urut maksimal 255.'),
            'form.nourut.unique' => __('No. urut sudah dipakai kelas lain.'),
        ];
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        return Classroom::query()
            ->when($this->jenjang !== '', fn ($q) => $q->where('jenjang', $this->jenjang));
    }

    protected function dataTableSearch(Builder $query, string $term): void
    {
        $query->search($term); // Classroom::scopeSearch
    }

    protected function dataTableDefaultSort(): array
    {
        return ['nourut', 'asc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Daftar Kelas'),
            'subtitle' => null,
            'actions' => [[
                'label' => __('Tambah'),
                'icon' => 'plus',
                'variant' => 'primary',
                'click' => 'openCreate',
            ]],
        ];
    }

    protected function dataTableEntity(): string
    {
        return 'kelas';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari kelas atau jenjang...');
    }

    protected function dataTableFilters(): array
    {
        return [
            'jenjang' => [
                'label' => __('Semua jenjang'),
                'options' => Classroom::query()
                    ->whereNotNull('jenjang')
                    ->distinct()
                    ->orderBy('jenjang')
                    ->pluck('jenjang')
                    ->mapWithKeys(fn ($j) => [$j => $j])
                    ->all(),
            ],
        ];
    }

    protected function dataTableColumns(): array
    {
        return [
            'nourut' => ['label' => 'No. Urut', 'sortable' => true, 'type' => 'muted'],

            'kelas' => ['label' => 'Kelas', 'sortable' => true],

            'jenjang' => [
                'label' => 'Jenjang',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($c) => ['label' => $c->jenjang, 'color' => 'sky'],
            ],

            // Menu aksi per baris (tidak diexport).
            'actions' => [
                'label' => 'Aksi',
                'type' => 'actions',
                'align' => 'end',
                'export' => false,
                'actions' => fn ($c) => [
                    [
                        'label' => __('Ubah'),
                        'icon' => 'pencil-square',
                        'click' => 'openEdit(' . $c->id . ')',
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
        return 'Daftar Kelas - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'kelas-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }

    protected function dataTableRowLabel($row): string
    {
        return $row->kelas . ' (' . $row->jenjang . ')';
    }
}