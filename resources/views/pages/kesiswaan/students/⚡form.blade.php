<?php

use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use App\Livewire\Concerns\WithRegionSelects;
use App\Support\Kesiswaan\StudentFields;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

// Satu komponen untuk Tambah dan Ubah.
new #[Title('Form Siswa')] class extends Component {
    use WithRegionSelects;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    #[Locked]
    public ?int $studentId = null;

    public string $tab = 'data_diri';

    public array $form = [];

    public function mount($unit, $student = null): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        $this->unitName = $unit->unit;

        if ($student === null) {
            $this->form = StudentFields::defaults();

            return;
        }

        $model = Student::findOrFail($student instanceof Student ? $student->getKey() : $student);
        $attributes = $model->getAttributes();

        $this->studentId = $model->id;

        foreach (StudentFields::fields() as $name => $spec) {
            $value = $attributes[$name] ?? null;

            if ($value !== null && $spec['type'] === 'date') {
                $value = substr((string) $value, 0, 10);
            }

            $this->form[$name] = $value === null ? '' : (string) $value;
        }
    }

    protected function rules(): array
    {
        return StudentFields::rules($this->studentId);
    }

    protected function validationAttributes(): array
    {
        return StudentFields::attributes();
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar.',
            'digits' => ':attribute harus terdiri dari :digits digit.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'date' => ':attribute bukan tanggal yang valid.',
            'date_format' => ':attribute bukan tanggal yang valid.',
            'after_or_equal' => ':attribute tidak valid (terlalu lampau).',
            'before_or_equal' => ':attribute tidak boleh melebihi hari ini.',
            'numeric' => ':attribute harus berupa angka.',
            'min.numeric' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'max.string' => ':attribute maksimal :max karakter.',
        ];
    }

    // Error merah otomatis hilang saat valid; hanya validasi field aktif mencegah error saat mengetik.
    public function updated(string $name): void
    {
        $this->resetRegionChildren($name);

        if (str_starts_with($name, 'form.') && $this->getErrorBag()->has($name)) {
            $this->validateOnly($name);
        }
    }

    // Pindah tab: maju hanya jika tab sebelumnya valid; mundur selalu diizinkan.
    public function goToTab(string $target): void
    {
        $tabs = StudentFields::tabs();
        $keys = array_keys($tabs);
        $targetIndex = array_search($target, $keys, true);

        if ($targetIndex === false) {
            return;
        }

        $rules = $this->rules();

        foreach (array_slice($keys, 0, $targetIndex) as $key) {
            $tabRules = [];

            foreach (array_keys($tabs[$key]['fields']) as $name) {
                $tabRules["form.{$name}"] = $rules["form.{$name}"];
            }

            try {
                $this->validate($tabRules);
            } catch (ValidationException $e) {
                // Tahan di tab pertama yang belum lengkap, error ditampilkan di sana.
                $this->tab = $key;

                throw $e;
            }
        }

        $this->tab = $target;
    }

    public function save(): void
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            // Pindah ke tab pertama yang berisi error supaya langsung kelihatan.
            $this->tab = $this->tabWithError(array_keys($e->errors())) ?? $this->tab;

            throw $e;
        }

        $data = StudentFields::normalize($this->form);
        $data['aktif'] ??= '1';

        try {
            if ($this->studentId) {
                Student::findOrFail($this->studentId)->update($data);
                $message = __('Data siswa berhasil diperbarui.');
            } else {
                $data['tglentry'] = now()->toDateString();
                Student::create($data);
                $message = __('Siswa baru berhasil ditambahkan.');
            }
        } catch (UniqueConstraintViolationException) {
            // NIS dipakai admin lain di antara validasi dan simpan.
            $this->tab = 'data_diri';
            $this->addError('form.nis', __('NIS sudah terdaftar.'));

            return;
        }

        session()->flash('status', $message);

        $this->redirectRoute('kesiswaan.students.index', ['unit' => $this->unitCode], navigate: true);
    }

    protected function tabWithError(array $errorKeys): ?string
    {
        foreach (StudentFields::tabs() as $key => $tab) {
            foreach (array_keys($tab['fields']) as $name) {
                if (in_array("form.{$name}", $errorKeys, true)) {
                    return $key;
                }
            }
        }

        return null;
    }
}; ?>

@php
    $tabs = \App\Support\Kesiswaan\StudentFields::tabs();
    $tabKeys = array_keys($tabs);
    $isEdit = $studentId !== null;
@endphp

<section class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6">
    <flux:heading size="xl" level="1">
        {{ $isEdit ? __('Ubah Data Siswa') : __('Tambah Siswa') }}
    </flux:heading>

    <form novalidate>
        <flux:card class="space-y-6">
            <div x-data="{ tab: $wire.tab }" x-effect="tab = $wire.tab" class="space-y-6">
                <!-- Navigasi tab -->
                <div class="flex gap-1 overflow-x-auto rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                    @foreach ($tabs as $key => $item)
                        @php
                            $hasError = collect(array_keys($item['fields']))
                                ->contains(fn ($name) => $errors->has("form.{$name}"));
                        @endphp

                        <button type="button" wire:click="goToTab('{{ $key }}')"
                            class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium transition"
                            x-bind:class="tab === '{{ $key }}'
                                ? 'bg-white text-zinc-800 shadow-xs dark:bg-zinc-600 dark:text-white'
                                : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white'">
                            <flux:icon :name="$item['icon']" variant="micro" class="size-4" />
                            {{ $item['label'] }}
                            @if ($hasError)
                                <span class="inline-block size-2 rounded-full bg-red-500"></span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach ($tabs as $key => $item)
                    <div x-show="tab === '{{ $key }}'" x-cloak class="space-y-6">
                        <!-- Judul bagian -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                    <flux:icon :name="$item['icon']" class="size-5 text-zinc-600 dark:text-zinc-300" />
                                </div>
                                <div>
                                    <flux:heading size="lg">{{ $item['label'] }}</flux:heading>
                                    <flux:text size="sm">{{ $item['description'] }}</flux:text>
                                </div>
                            </div>

                            <flux:badge size="sm" color="zinc">
                                {{ __('Langkah') }} {{ $loop->iteration }}/{{ $loop->count }}
                            </flux:badge>
                        </div>

                        <flux:separator />

                        <!-- Field -->
                        <div class="grid gap-x-5 gap-y-5 md:grid-cols-12">
                            @foreach ($item['fields'] as $name => $spec)
                                <x-field-col :span="$spec['span']">
                                    @php
                                        $errorKey = "form.{$name}";
                                        $options = ($spec['region'] ?? false) ? $this->regionOptions($name) : $spec['options'];
                                        $current = $form[$name] ?? '';
                                        // Pertahankan nilai lama yang tidak ada di daftar opsi.
                                        if ($spec['type'] === 'select' && $current !== '' && ! array_key_exists($current, $options)) {
                                            $options[$current] = $current;
                                        }
                                    @endphp

                                    <flux:field>
                                        <flux:label>
                                            {{ $spec['label'] }}
                                            @if ($spec['required'])
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </flux:label>

                                        @if ($spec['region'] ?? false)
                                            <x-searchable-select wire:model.live="form.{{ $name }}" :options="$options"
                                                :invalid="$errors->has($errorKey)" :disabled="! $this->regionReady($name)"
                                                :placeholder="__('-- Pilih --')" :search-placeholder="__('Cari...')"
                                                :empty-text="__('Tidak ditemukan')" />
                                        @elseif ($spec['type'] === 'select')
                                            <flux:select wire:model.live="form.{{ $name }}" :invalid="$errors->has($errorKey)">
                                                <flux:select.option value="">{{ __('-- Pilih --') }}</flux:select.option>
                                                @foreach ($options as $value => $text)
                                                    <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        @elseif ($spec['type'] === 'date')
                                            <x-date-picker wire:model.live="form.{{ $name }}" :invalid="$errors->has($errorKey)" />
                                        @else
                                            <flux:input wire:model.blur="form.{{ $name }}" :type="$spec['type']"
                                                autocomplete="off"
                                                :step="$spec['type'] === 'number' ? 'any' : null"
                                                :min="$spec['type'] === 'number' ? 0 : null"
                                                :maxlength="in_array('digits:16', $spec['rules'], true) ? 16 : null"
                                                :inputmode="in_array('digits:16', $spec['rules'], true) ? 'numeric' : null"
                                                x-on:click="if ($el.type === 'date') { try { $el.showPicker() } catch (e) {} }"
                                                x-on:beforeinput="if ($el.inputMode === 'numeric' && $event.data && /\D/.test($event.data)) $event.preventDefault()" />
                                        @endif

                                        <flux:error :name="$errorKey" class="mt-0! text-xs!" />
                                    </flux:field>
                                </x-field-col>
                            @endforeach
                        </div>

                        <!-- Kembali / Lanjut -->
                        @php
                            $position = array_search($key, $tabKeys, true);
                            $prevKey = $tabKeys[$position - 1] ?? null;
                            $nextKey = $tabKeys[$position + 1] ?? null;
                        @endphp

                        <div class="flex items-center justify-between">
                            @if ($prevKey)
                                <flux:button icon="arrow-left" wire:click="goToTab('{{ $prevKey }}')">
                                    {{ __('Kembali') }}
                                </flux:button>
                            @else
                                <span></span>
                            @endif

                            @if ($nextKey)
                                <flux:button icon-trailing="arrow-right" wire:click="goToTab('{{ $nextKey }}')">
                                    {{ __('Lanjut') }}
                                </flux:button>
                            @else
                                <flux:button variant="primary" icon="check" wire:click="save">
                                    <span wire:loading.remove wire:target="save">{{ $isEdit ? __('Simpan Perubahan') : __('Simpan') }}</span>
                                    <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:separator />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:text size="sm"><span class="text-red-500">*</span> {{ __('wajib diisi') }}</flux:text>

                <div class="flex items-center gap-2">
                    <flux:button variant="danger"
                        :href="route('kesiswaan.students.index', ['unit' => $unitCode])" wire:navigate>
                        {{ __('Batal') }}
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </form>
</section>