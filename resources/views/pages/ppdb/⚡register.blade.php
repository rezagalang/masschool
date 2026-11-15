<?php

use App\Livewire\Concerns\WithRegionSelects;
use App\Models\Kesiswaan\Registration;
use App\Models\Kesiswaan\Student;
use App\Models\Unit;
use App\Support\Kesiswaan\RegistrationFields;
use App\Support\UnitDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

// Halaman PPDB publik per unit (tanpa login); data masuk ke tabel registrations database unit untuk verifikasi admin.
new #[Title('Pendaftaran Siswa Baru')] #[Layout('layouts::public')] class extends Component {
    use WithFileUploads;
    use WithRegionSelects;

    // Batas pendaftaran per perangkat (IP) per unit per jam.
    private const MAX_ATTEMPTS = 10;

    #[Locked]
    public string $unitCode = '';

    #[Locked]
    public string $unitName = '';

    #[Locked]
    public bool $submitted = false;

    #[Locked]
    public string $noPendaftaran = '';

    public string $tab = 'data_diri';

    public array $form = [];

    // Bukti Transfer.
    public $proof = null;

    public function mount($unit): void
    {
        $unit = $unit instanceof Unit ? $unit : Unit::where('code', $unit)->firstOrFail();

        $this->unitCode = $unit->code;
        // Nama lengkap unit (kolom name); kalau kosong pakai nama singkat (kolom unit).
        $this->unitName = $unit->name ?: $unit->unit;
        $this->form = RegistrationFields::defaults();
    }

    protected function rules(): array
    {
        return RegistrationFields::rules();
    }

    protected function validationAttributes(): array
    {
        return RegistrationFields::attributes();
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
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
            'proof.required' => 'Bukti Transfer wajib diunggah.',
            'proof.file' => 'Bukti Transfer harus berupa file.',
            'proof.mimes' => 'Bukti Transfer harus berupa file JPG, PNG, WEBP, atau PDF.',
            'proof.max' => 'Ukuran Bukti Transfer maksimal 3 MB.',
            'proof.uploaded' => 'Bukti Transfer gagal diunggah. Pastikan ukuran file maksimal 3 MB.',
        ];
    }

    // Error merah otomatis hilang saat valid; file bukti transfer divalidasi saat dipilih dan dibuang jika tidak valid.
    public function updated(string $name): void
    {
        $this->resetRegionChildren($name);

        if ($name === 'proof') {
            if ($this->proof === null) {
                return;
            }

            try {
                $this->validateOnly('proof');
            } catch (ValidationException $e) {
                $this->reset('proof');

                throw $e;
            }

            return;
        }

        if (str_starts_with($name, 'form.') && $this->getErrorBag()->has($name)) {
            $this->validateOnly($name);
        }
    }

    // Maju ke tab berikutnya jika semua tab sebelumnya sudah valid.
    public function goToTab(string $target): void
    {
        $keys = array_keys(RegistrationFields::tabs());
        $targetIndex = array_search($target, $keys, true);

        if ($targetIndex === false) {
            return;
        }

        foreach (array_slice($keys, 0, $targetIndex) as $key) {
            try {
                $this->validate($this->tabRules($key));
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
        if ($this->submitted) {
            return;
        }

        $limitKey = 'ppdb:' . $this->unitCode . ':' . request()->ip();

        if (RateLimiter::tooManyAttempts($limitKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limitKey) / 60);

            $this->tab = 'lampiran';
            $this->addError('proof', __('Terlalu banyak pendaftaran dari perangkat ini. Coba lagi dalam :minutes menit.', ['minutes' => $minutes]));

            return;
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            // Pindah ke tab pertama yang berisi error.
            $this->tab = $this->tabWithError(array_keys($e->errors())) ?? $this->tab;

            throw $e;
        }

        $data = RegistrationFields::normalize($this->form);

        // Satu NIK hanya boleh mendaftar sekali per tahun ajaran.
        $duplicate = Registration::query()
            ->where('nik', $data['nik'])
            ->where('tahunajaran', $data['tahunajaran'])
            ->exists()
            || Student::query()
                ->where('nik', $data['nik'])
                ->where('tahunajaran', $data['tahunajaran'])
                ->exists();

        if ($duplicate) {
            $this->tab = 'data_diri';
            $this->addError('form.nik', __('NIK ini sudah terdaftar pada tahun ajaran tersebut. Silakan hubungi pihak sekolah.'));

            return;
        }

        // Bukti transfer disimpan di disk privat, dibuka admin lewat route khusus.
        $path = $this->proof->store("ppdb/{$this->unitCode}/bukti-transfer", 'local');

        try {
            $registration = DB::connection(UnitDatabase::CONNECTION)->transaction(function () use ($data, $path) {
                $registration = Registration::create($data + [
                    'bukti_transfer' => $path,
                    'status_daftar' => false,
                    'tglentry' => now()->toDateString(),
                ]);

                // Nomor dari ID supaya pasti unik.
                $registration->update([
                    'no_pendaftaran' => 'PPDB' . date('y') . str_pad((string) $registration->id, 5, '0', STR_PAD_LEFT),
                ]);

                return $registration;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }

        RateLimiter::hit($limitKey, 3600);

        $this->noPendaftaran = $registration->no_pendaftaran;
        $this->submitted = true;

        // Bersihkan data pribadi dari state komponen.
        $this->form = RegistrationFields::defaults();
        $this->proof = null;
    }

    protected function tabRules(string $key): array
    {
        $rules = $this->rules();

        if ($key === 'lampiran') {
            return ['proof' => $rules['proof']];
        }

        $tabRules = [];

        foreach (array_keys(RegistrationFields::tabs()[$key]['fields']) as $name) {
            $tabRules["form.{$name}"] = $rules["form.{$name}"];
        }

        return $tabRules;
    }

    protected function tabWithError(array $errorKeys): ?string
    {
        foreach (RegistrationFields::tabs() as $key => $tab) {
            foreach (array_keys($tab['fields']) as $name) {
                if (in_array("form.{$name}", $errorKeys, true)) {
                    return $key;
                }
            }
        }

        return in_array('proof', $errorKeys, true) ? 'lampiran' : null;
    }
}; ?>

@php
    $tabs = \App\Support\Kesiswaan\RegistrationFields::tabs();
    $tabKeys = array_keys($tabs);
@endphp

<section class="flex w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Pendaftaran Siswa Baru') }}</flux:heading>
            <flux:text>{{ $unitName }}</flux:text>
        </div>

        <div class="flex shrink-0 items-center gap-2 font-medium">
            <x-app-logo-icon class="size-8 fill-current text-black dark:text-white" />
            <span class="hidden sm:inline">{{ config('app.name', 'Laravel') }}</span>
        </div>
    </div>

    @if ($submitted)
        <flux:card class="mx-auto w-full max-w-xl space-y-4 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                <flux:icon name="check-circle" class="size-8 text-green-600 dark:text-green-400" />
            </div>

            <flux:heading size="lg">{{ __('Pendaftaran Berhasil') }}</flux:heading>

            <flux:text>
                {{ __('Data pendaftaran Anda sudah diterima oleh') }} {{ $unitName }}
                {{ __('dan akan diverifikasi oleh pihak sekolah.') }}
            </flux:text>

            <div class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                <flux:text size="sm">{{ __('Nomor Pendaftaran') }}</flux:text>
                <div class="text-2xl font-semibold tracking-wide">{{ $noPendaftaran }}</div>
            </div>

            <flux:text size="sm">{{ __('Simpan nomor pendaftaran ini untuk keperluan konfirmasi ke sekolah.') }}</flux:text>

            <flux:button :href="route('ppdb.register', ['unit' => $unitCode])">
                {{ __('Daftarkan Siswa Lain') }}
            </flux:button>
        </flux:card>
    @else
        <form novalidate>
            <flux:card class="space-y-6">
                <div x-data="{ tab: $wire.tab, uploading: false }" x-effect="tab = $wire.tab"
                    x-on:dropzone-uploading="uploading = $event.detail.uploading" class="space-y-6">
                    <!-- Navigasi tab: tiap tombol (justify), baris terakhir ikut memenuhi lebar -->
                    <div class="flex flex-wrap gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                        @foreach ($tabs as $key => $item)
                            @php
                                $hasError = $key === 'lampiran'
                                    ? $errors->has('proof')
                                    : collect(array_keys($item['fields']))->contains(fn ($name) => $errors->has("form.{$name}"));
                            @endphp

                            <button type="button" wire:click="goToTab('{{ $key }}')"
                                class="flex grow basis-auto items-center justify-center gap-1.5 whitespace-nowrap rounded-md px-2.5 py-1.5 text-xs font-medium transition sm:text-sm"
                                x-bind:class="tab === '{{ $key }}'
                                    ? 'bg-white text-zinc-800 shadow-xs dark:bg-zinc-600 dark:text-white'
                                    : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white'">
                                <flux:icon :name="$item['icon']" variant="micro" class="size-3.5 shrink-0 sm:size-4" />
                                {{ $item['label'] }}
                                @if ($hasError)
                                    <span class="inline-block size-2 shrink-0 rounded-full bg-red-500"></span>
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

                            @if ($key === 'lampiran')
                                <!-- Lampiran: Bukti Transfer (drag & drop) -->
                                <div class="grid gap-x-5 gap-y-5 md:grid-cols-12">
                                    <x-field-col :span="12">
                                        <flux:field>
                                            <flux:label>
                                                {{ __('Bukti Transfer') }} <span class="text-red-500">*</span>
                                            </flux:label>

                                            <x-file-dropzone wire:model="proof"
                                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                                :max-size="\App\Support\Kesiswaan\RegistrationFields::PROOF_MAX_KB" />

                                            <flux:text size="sm">
                                                {{ __('Format JPG, PNG, WEBP, atau PDF. Maksimal 3 MB.') }}
                                            </flux:text>
                                        </flux:field>
                                    </x-field-col>
                                </div>
                            @else
                                <!-- Field -->
                                <div class="grid gap-x-5 gap-y-5 md:grid-cols-12">
                                    @foreach ($item['fields'] as $name => $spec)
                                        <x-field-col :span="$spec['span']">
                                            @php
                                                $errorKey = "form.{$name}";
                                                $options = ($spec['region'] ?? false) ? $this->regionOptions($name) : $spec['options'];
                                                $current = $form[$name] ?? '';
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
                            @endif

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
                                    <flux:button variant="primary" icon="paper-airplane" wire:click="save"
                                        wire:loading.attr="disabled" wire:target="save"
                                        x-bind:disabled="uploading">
                                        <span wire:loading.remove wire:target="save">{{ __('Kirim Pendaftaran') }}</span>
                                        <span wire:loading wire:target="save">{{ __('Mengirim...') }}</span>
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:separator />

                <flux:text size="sm"><span class="text-red-500">*</span> {{ __('wajib diisi') }}</flux:text>
            </flux:card>
        </form>
    @endif
</section>