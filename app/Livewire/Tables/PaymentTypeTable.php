<?php

namespace App\Livewire\Tables;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Keuangan\PaymentType;
use App\Models\Unit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Datatable Jenis Pembayaran + modal. */
class PaymentTypeTable extends Component
{
    use WithDataTable;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    // Jenis pembayaran yang sedang diubah di modal.
    #[Locked]
    public ?int $editingId = null;

    /** @var array{nama: string, keterangan: string, alias: string} */
    public array $form = ['nama' => '', 'keterangan' => '', 'alias' => ''];

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
        $this->form = ['nama' => '', 'keterangan' => '', 'alias' => ''];

        Flux::modal('payment-type-form')->show();
    }

    public function openEdit(int $id): void
    {
        $type = PaymentType::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $type->id;
        $this->form = [
            'nama' => $type->nama,
            'keterangan' => (string) $type->keterangan,
            'alias' => (string) $type->alias,
        ];

        Flux::modal('payment-type-form')->show();
    }

    public function save(): void
    {
        foreach (['nama', 'keterangan', 'alias'] as $field) {
            $this->form[$field] = trim((string) ($this->form[$field] ?? ''));
        }

        $data = $this->validate()['form'];

        $payload = [
            'nama' => $data['nama'],
            'keterangan' => $data['keterangan'],
            'alias' => ($data['alias'] ?? '') === '' ? null : $data['alias'],
        ];

        if ($this->editingId !== null) {
            PaymentType::query()->findOrFail($this->editingId)->update($payload);
            $message = __('Jenis pembayaran berhasil diperbarui.');
        } else {
            PaymentType::query()->create($payload);
            $message = __('Jenis pembayaran berhasil ditambahkan.');
        }

        $this->editingId = null;
        $this->form = ['nama' => '', 'keterangan' => '', 'alias' => ''];

        Flux::modal('payment-type-form')->close();
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
    }

    protected function rules(): array
    {
        return [
            'form.nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique(PaymentType::class, 'nama')->ignore($this->editingId),
            ],
            'form.keterangan' => ['required', 'string', 'max:50'],
            'form.alias' => ['nullable', 'string', 'max:10'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.nama.required' => __('Nama jenis pembayaran wajib diisi.'),
            'form.nama.max' => __('Nama maksimal 50 karakter.'),
            'form.nama.unique' => __('Jenis pembayaran ini sudah ada.'),
            'form.keterangan.required' => __('Keterangan wajib diisi.'),
            'form.keterangan.max' => __('Keterangan maksimal 50 karakter.'),
            'form.alias.max' => __('Alias maksimal 10 karakter.'),
        ];
    }

    /* Data */
    protected function dataTableQuery(): Builder
    {
        return PaymentType::query();
    }

    protected function dataTableSearch(Builder $query, string $term): void
    {
        $query->search($term); // PaymentType::scopeSearch
    }

    protected function dataTableDefaultSort(): array
    {
        return ['nama', 'asc'];
    }

    /* Tampilan */
    protected function dataTableHeader(): array
    {
        return [
            'title' => __('Jenis Pembayaran'),
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
        return 'jenis pembayaran';
    }

    protected function dataTablePlaceholder(): string
    {
        return __('Cari nama, keterangan, atau alias...');
    }

    protected function dataTableColumns(): array
    {
        return [
            'nama' => ['label' => 'Nama', 'sortable' => true],

            'keterangan' => ['label' => 'Keterangan', 'sortable' => true, 'type' => 'muted'],

            'alias' => [
                'label' => 'Alias',
                'sortable' => true,
                'type' => 'badge',
                'badge' => fn ($t) => $t->alias ? ['label' => $t->alias, 'color' => 'sky'] : null,
            ],

            // Menu aksi per baris (tidak diexport).
            'actions' => [
                'label' => 'Aksi',
                'type' => 'actions',
                'align' => 'end',
                'export' => false,
                'actions' => fn ($t) => [
                    [
                        'label' => __('Ubah'),
                        'icon' => 'pencil-square',
                        'click' => 'openEdit(' . $t->id . ')',
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
        return 'Jenis Pembayaran - ' . $this->unitName;
    }

    protected function dataTableFilename(): string
    {
        return 'jenis-pembayaran-' . $this->unitCode . '-' . now()->format('Ymd-His');
    }

    protected function dataTableRowLabel($row): string
    {
        return $row->nama . ($row->alias ? ' (' . $row->alias . ')' : '');
    }
}