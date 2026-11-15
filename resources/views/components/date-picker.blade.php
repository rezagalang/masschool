@props([
    'min' => '1900-01-01',
    'max' => null,
    'placeholder' => 'hh/bb/tttt',
    'invalid' => false,
    'disabled' => false,
])

@php
    // Batas atas = hari ini (zona waktu aplikasi), sesuai aturan before_or_equal:today server.
    $max = $max ?: now()->toDateString();
@endphp

<div {{ $attributes->whereStartsWith('wire:model') }} x-modelable="value" x-data="{
    open: false,
    value: '',
    text: '',
    bad: false,
    viewY: 0,
    viewM: 0,
    min: @js($min),
    max: @js($max),
    months: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    weekdays: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    init() {
        this.$nextTick(() => { this.text = this.fmt(this.value); });
        // Nilai berubah dari luar (mis. form di-reset): samakan teksnya.
        this.$watch('value', (v) => {
            if (v !== this.parse(this.text)) this.text = this.fmt(v);
        });
    },
    pad(n) {
        return String(n).padStart(2, '0');
    },
    fmt(iso) {
        return iso && iso.length >= 10 ? iso.slice(8, 10) + '/' + iso.slice(5, 7) + '/' + iso.slice(0, 4) : '';
    },
    // 'hh/bb/tttt' -> 'tttt-bb-hh'; string kosong jika tidak valid / di luar rentang.
    parse(text) {
        const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(text || '');
        if (!m) return '';
        const d = +m[1],
            mo = +m[2],
            y = +m[3];
        const date = new Date(y, mo - 1, d);
        if (date.getFullYear() !== y || date.getMonth() !== mo - 1 || date.getDate() !== d) return '';
        const iso = y + '-' + this.pad(mo) + '-' + this.pad(d);
        return this.off(iso) ? '' : iso;
    },
    off(iso) {
        return iso < this.min || iso > this.max;
    },
    get ymMin() {
        return +this.min.slice(0, 4) * 12 + (+this.min.slice(5, 7) - 1);
    },
    get ymMax() {
        return +this.max.slice(0, 4) * 12 + (+this.max.slice(5, 7) - 1);
    },
    get years() {
        const list = [];
        for (let y = +this.max.slice(0, 4); y >= +this.min.slice(0, 4); y--) list.push(y);
        return list;
    },
    get cells() {
        const offset = (new Date(this.viewY, this.viewM, 1).getDay() + 6) % 7;
        const total = new Date(this.viewY, this.viewM + 1, 0).getDate();
        const list = Array(offset).fill(null);
        for (let d = 1; d <= total; d++) {
            list.push({ d: d, iso: this.viewY + '-' + this.pad(this.viewM + 1) + '-' + this.pad(d) });
        }
        return list;
    },
    cls(cell) {
        if (!cell) return '';
        if (this.off(cell.iso)) return 'cursor-not-allowed text-zinc-300 dark:text-zinc-600';
        if (cell.iso === this.value) return 'bg-zinc-800 font-semibold text-white dark:bg-white dark:text-zinc-800';
        if (cell.iso === this.max) return 'font-semibold ring-1 ring-inset ring-zinc-300 hover:bg-zinc-100 dark:ring-zinc-500 dark:hover:bg-zinc-700';
        return 'hover:bg-zinc-100 dark:hover:bg-zinc-700';
    },
    monthOff(i) {
        const ym = this.viewY * 12 + i;
        return ym < this.ymMin || ym > this.ymMax;
    },
    // Pastikan bulan yang tampil masih di dalam rentang (mis. setelah ganti tahun).
    clamp() {
        const ym = Math.min(Math.max(this.viewY * 12 + this.viewM, this.ymMin), this.ymMax);
        this.viewY = Math.floor(ym / 12);
        this.viewM = ym % 12;
    },
    shift(n) {
        const ym = this.viewY * 12 + this.viewM + n;
        if (ym < this.ymMin || ym > this.ymMax) return;
        this.viewY = Math.floor(ym / 12);
        this.viewM = ym % 12;
    },
    goTo(iso) {
        this.viewY = +iso.slice(0, 4);
        this.viewM = +iso.slice(5, 7) - 1;
    },
    toggle() {
        if (this.$refs.input.disabled) return;
        this.open ? this.close() : this.show();
    },
    show() {
        this.goTo(this.value || this.parse(this.text) || this.max);
        this.open = true;
    },
    close() {
        this.open = false;
    },
    pick(iso) {
        if (this.off(iso)) return;
        this.value = iso;
        this.text = this.fmt(iso);
        this.bad = false;
        this.close();
    },
    clear() {
        this.value = '';
        this.text = '';
        this.bad = false;
        this.close();
    },
    // Ketikan: sisakan angka, sisipkan '/' otomatis, simpan saat sudah 8 digit.
    typed() {
        const d = this.text.replace(/\D/g, '').slice(0, 8);
        this.text = d.slice(0, 2) + (d.length > 2 ? '/' + d.slice(2, 4) : '') + (d.length > 4 ? '/' + d.slice(4) : '');

        if (this.text === '') {
            this.value = '';
            this.bad = false;
        } else if (this.text.length === 10) {
            const iso = this.parse(this.text);
            this.bad = iso === '';
            this.value = iso;
            if (iso) this.goTo(iso);
        } else {
            this.bad = false;
        }
    },
    // Keluar dari kolom dengan isi belum lengkap / tidak valid: kosongkan nilai
    // dan tandai merah, supaya tidak ada tanggal setengah jadi yang tersimpan.
    settle() {
        if (this.text === '') return;
        const iso = this.parse(this.text);
        this.bad = iso === '';
        this.value = iso;
    },
}"
    x-on:keydown.escape="close()" x-on:click.outside="close()"
    {{ $attributes->whereDoesntStartWith('wire:model')->class('relative w-full') }}>
    <div class="flex w-full items-center gap-2 rounded-lg border bg-white px-3 shadow-xs transition focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-(--color-accent) dark:bg-white/10
            {{ $disabled ? 'cursor-not-allowed opacity-50' : '' }}"
        x-bind:class="(bad || @js((bool) $invalid)) ?
        'border-red-500' :
        'border-zinc-200 border-b-zinc-300/80 dark:border-white/10'">
        <input type="text" x-ref="input" x-model="text" inputmode="numeric" autocomplete="off" maxlength="10"
            placeholder="{{ $placeholder }}" @disabled($disabled) x-on:input="typed()" x-on:blur="settle()"
            x-on:keydown.arrow-down.prevent="show()" x-on:keydown.enter="settle()"
            class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm outline-hidden placeholder:text-zinc-400 disabled:cursor-not-allowed dark:placeholder:text-zinc-500" />

        <button type="button" x-on:click="toggle()" @disabled($disabled) aria-label="{{ __('Buka kalender') }}"
            class="shrink-0 rounded p-0.5 text-zinc-400 transition hover:text-zinc-700 disabled:cursor-not-allowed dark:hover:text-zinc-200">
            <flux:icon.calendar-days variant="micro" class="size-4" />
        </button>
    </div>

    <!-- Kalender -->
    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute start-0 z-30 mt-1 w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-600 dark:bg-zinc-800">
        <!-- Navigasi bulan & tahun -->
        <div class="mb-3 flex items-center gap-1.5">
            <button type="button" x-on:click="shift(-1)" x-bind:disabled="viewY * 12 + viewM <= ymMin"
                aria-label="{{ __('Bulan sebelumnya') }}"
                class="rounded-md p-1.5 text-zinc-500 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-30 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <flux:icon.chevron-left variant="micro" class="size-4" />
            </button>

            <select x-model.number="viewM"
                class="min-w-0 flex-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-sm outline-hidden focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-700">
                <template x-for="(name, i) in months" x-bind:key="i">
                    <option x-bind:value="i" x-text="name" x-bind:disabled="monthOff(i)"
                        x-bind:selected="i === viewM"></option>
                </template>
            </select>

            <select x-model.number="viewY" x-on:change="clamp()"
                class="w-20 rounded-md border border-zinc-200 bg-white px-2 py-1 text-sm outline-hidden focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-700">
                <template x-for="y in years" x-bind:key="y">
                    <option x-bind:value="y" x-text="y" x-bind:selected="y === viewY"></option>
                </template>
            </select>

            <button type="button" x-on:click="shift(1)" x-bind:disabled="viewY * 12 + viewM >= ymMax"
                aria-label="{{ __('Bulan berikutnya') }}"
                class="rounded-md p-1.5 text-zinc-500 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-30 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <flux:icon.chevron-right variant="micro" class="size-4" />
            </button>
        </div>

        <!-- Nama hari -->
        <div class="mb-1 grid grid-cols-7 text-center text-xs font-medium text-zinc-400 dark:text-zinc-500">
            <template x-for="day in weekdays" x-bind:key="day">
                <div class="py-1" x-text="day"></div>
            </template>
        </div>

        <!-- Tanggal -->
        <div class="grid grid-cols-7 gap-y-0.5">
            <template x-for="(cell, i) in cells" x-bind:key="viewY + '-' + viewM + '-' + i">
                <div class="flex justify-center">
                    <button type="button" x-show="cell" x-text="cell ? cell.d : ''"
                        x-bind:disabled="cell && off(cell.iso)" x-on:click="pick(cell.iso)"
                        x-bind:class="cls(cell)" class="size-9 rounded-md text-sm transition"></button>
                    <span x-show="! cell" class="size-9"></span>
                </div>
            </template>
        </div>

        <div class="mt-2 flex items-center justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
            <button type="button" x-on:click="clear()" x-show="value"
                class="rounded-md px-2 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                {{ __('Hapus') }}
            </button>
            <span x-show="! value"></span>

            <button type="button" x-on:click="close()"
                class="rounded-md px-2 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                {{ __('Tutup') }}
            </button>
        </div>
    </div>
</div>