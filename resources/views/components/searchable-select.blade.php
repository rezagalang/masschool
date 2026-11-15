@props([
    'options' => [],
    'placeholder' => '-- Pilih --',
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Tidak ditemukan',
    'invalid' => false,
    'disabled' => false,
])

<div {{ $attributes->whereStartsWith('wire:model') }} x-modelable="value" x-data="{
    open: false,
    search: '',
    value: '',
    label: '',
    init() {
        this.$nextTick(() => this.sync());
        this.$watch('value', () => this.$nextTick(() => this.sync()));
    },
    items() {
        return [...(this.$refs.list?.children ?? [])];
    },
    sync() {
        const item = this.items().find(el => el.dataset.value === String(this.value ?? ''));
        this.label = item && item.dataset.value !== '' ? item.dataset.label : '';
    },
    matches(el) {
        return !this.search || el.dataset.text.includes(this.search.trim().toLowerCase());
    },
    get empty() {
        // Daftar dibaca dari DOM (non-reaktif); diikat ke open & search agar dihitung ulang saat dropdown dibuka atau mengetik.
        void this.open;
        void this.search;

        return !this.items().some(el => el.dataset.value !== '' && this.matches(el));
    },
    toggle() {
        if (this.$refs.button.disabled) return;
        this.open ? this.close() : this.show();
    },
    show() {
        this.search = '';
        this.open = true;
        this.$nextTick(() => this.$refs.search.focus());
    },
    close() {
        this.open = false;
    },
    pick(value, label) {
        this.value = value;
        this.label = value === '' ? '' : label;
        this.close();
        this.$refs.button.focus();
    },
    pickFirst() {
        const item = this.items().find(el => el.dataset.value !== '' && this.matches(el));
        if (item) this.pick(item.dataset.value, item.dataset.label);
    },
}"
    x-on:keydown.escape="close()" x-on:click.outside="close()"
    {{ $attributes->whereDoesntStartWith('wire:model')->class('relative w-full') }}>
    <button type="button" x-ref="button" x-on:click="toggle()" @disabled($disabled)
        class="flex w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 py-2 text-start text-sm shadow-xs transition disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/10
            {{ $invalid ? 'border-red-500' : 'border-zinc-200 border-b-zinc-300/80 dark:border-white/10' }}">
        <span class="min-w-0 flex-1 truncate" x-bind:class="label ? '' : 'text-zinc-400 dark:text-zinc-500'"
            x-text="label || @js($placeholder)">{{ $placeholder }}</span>

        <flux:icon.chevron-up-down variant="micro" class="size-4 shrink-0 text-zinc-400" />
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
        class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-2 dark:border-zinc-700">
            <input type="text" x-ref="search" x-model="search" autocomplete="off"
                x-on:keydown.enter.prevent="pickFirst()" placeholder="{{ $searchPlaceholder }}"
                class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-sm outline-hidden focus:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-700 dark:placeholder-zinc-400" />
        </div>

        <ul x-ref="list" class="max-h-60 overflow-y-auto py-1 text-sm">
            <li data-value="" data-label="" data-text="" x-show="! search" x-on:click="pick('', '')"
                class="cursor-pointer px-3 py-1.5 text-zinc-400 hover:bg-zinc-100 dark:text-zinc-500 dark:hover:bg-zinc-700">
                {{ $placeholder }}
            </li>

            @foreach ($options as $value => $text)
                <li data-value="{{ $value }}" data-label="{{ $text }}"
                    data-text="{{ mb_strtolower((string) $text) }}" x-show="matches($el)"
                    x-on:click="pick($el.dataset.value, $el.dataset.label)"
                    x-bind:class="value === $el.dataset.value ? 'bg-zinc-100 font-medium dark:bg-zinc-700' : ''"
                    class="cursor-pointer px-3 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    {{ $text }}
                </li>
            @endforeach
        </ul>

        <div x-show="empty" x-cloak class="px-3 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ $emptyText }}
        </div>
    </div>
</div>