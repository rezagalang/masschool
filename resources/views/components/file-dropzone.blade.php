@props([
    'accept' => null,
    'maxSize' => null,
    'hint' => null,
    'disabled' => false,
])

@php
    $property = $attributes->wire('model')->value();

    // Teks bantuan otomatis, mis. "JPG, PNG, PDF. Maksimal 3 MB."
    if ($hint === null && ($accept || $maxSize)) {
        $labels = [
            'image/jpeg' => 'JPG',
            'image/png' => 'PNG',
            'image/webp' => 'WEBP',
            'image/gif' => 'GIF',
            'image/*' => 'Gambar',
            'application/pdf' => 'PDF',
        ];

        $types = collect(explode(',', (string) $accept))
            ->map(fn($a) => strtolower(trim($a)))
            ->filter()
            ->map(fn($a) => $labels[$a] ?? (str_starts_with($a, '.') ? strtoupper(ltrim($a, '.')) : null))
            ->filter()
            ->unique()
            ->implode(', ');

        $size = $maxSize
            ? ($maxSize >= 1024
                ? rtrim(rtrim(number_format($maxSize / 1024, 1), '0'), '.') . ' MB'
                : $maxSize . ' KB')
            : null;

        $hint = trim(($types ?: '') . ($types && $size ? '. ' : '') . ($size ? 'Maksimal ' . $size . '.' : ''));
    }
@endphp

<div {{ $attributes->whereDoesntStartWith('wire:model')->class('space-y-2') }} x-data="{
    property: @js($property),
    accept: @js($accept),
    maxKb: @js($maxSize),
    disabled: @js($disabled),
    dragging: false,
    uploading: false,
    progress: 0,
    file: null,
    preview: null,
    clientError: null,

    init() {
        // Beri tahu komponen induk hanya saat status upload berubah.
        this.$watch('uploading', (uploading) => this.$dispatch('dropzone-uploading', { uploading }))
    },

    pick() {
        if (this.disabled) return

        this.$refs.input.click()
    },

    choose(files) {
        const f = files && files[0]

        if (this.disabled || !f) return

        this.clientError = null

        if (this.maxKb && f.size > this.maxKb * 1024) {
            this.clientError = 'Ukuran file maksimal ' + this.format(this.maxKb * 1024) + '.'
            return
        }

        if (!this.accepts(f)) {
            this.clientError = 'Format file tidak didukung.'
            return
        }

        this.revoke()
        this.file = f
        this.preview = f.type.startsWith('image/') ? URL.createObjectURL(f) : null
        this.upload(f)
    },

    upload(f) {
        this.uploading = true
        this.progress = 0

        this.$wire.$upload(
            this.property,
            f,
            () => this.$nextTick(() => { this.uploading = false }),
            () => {
                this.uploading = false
                this.clear()
                this.clientError = 'Unggahan gagal. Coba lagi.'
            },
            (e) => { this.progress = e.detail.progress },
            () => { this.uploading = false },
        )
    },

    remove() {
        if (this.uploading) this.$wire.$cancelUpload(this.property)

        this.clear()
        this.clientError = null
        this.$wire.$set(this.property, null)
    },

    clear() {
        this.revoke()
        this.file = null
        this.preview = null
    },

    revoke() {
        if (this.preview) URL.revokeObjectURL(this.preview)
    },

    accepts(f) {
        if (!this.accept) return true

        const name = f.name.toLowerCase()
        const type = f.type.toLowerCase()

        return this.accept.split(',').map(a => a.trim().toLowerCase()).filter(Boolean).some(a => {
            if (a.startsWith('.')) return name.endsWith(a)
            if (a.endsWith('/*')) return type.startsWith(a.slice(0, -1))
            return type === a
        })
    },

    format(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace(/\.0$/, '') + ' MB'
        return Math.max(1, Math.round(bytes / 1024)) + ' KB'
    },
}" x-effect="if (file && ! uploading && ! $wire[property]) clear()">
    <input type="file" x-ref="input" class="hidden" tabindex="-1" x-bind:accept="accept"
        x-on:change="choose($event.target.files); $event.target.value = ''" />

    <div x-on:dragover.prevent="if (! disabled) dragging = true"
        x-on:dragleave.prevent="if (! $el.contains($event.relatedTarget)) dragging = false"
        x-on:drop.prevent="dragging = false; choose($event.dataTransfer.files)"
        class="rounded-lg border p-4 transition-colors"
        x-bind:class="{
            'border-dashed border-accent bg-accent/5': dragging,
            'border-dashed border-zinc-300 dark:border-white/20': !dragging && !file,
            'border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-white/5': !dragging && file,
            'opacity-50': disabled,
            '!border-red-500 dark:!border-red-400': clientError || {{ $errors->has($property) ? 'true' : 'false' }},
        }">
        <!-- Belum ada file -->
        <div x-show="! file" role="button" x-bind:tabindex="disabled ? -1 : 0" x-bind:aria-disabled="disabled"
            x-on:click="pick()" x-on:keydown.enter.prevent="pick()" x-on:keydown.space.prevent="pick()"
            class="flex flex-col items-center gap-3 rounded-md px-2 py-4 text-center outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-900"
            x-bind:class="disabled ? 'cursor-not-allowed' : 'cursor-pointer'">
            <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-white/10">
                <flux:icon name="cloud-arrow-up" class="size-5 text-zinc-500 dark:text-zinc-300" />
            </div>

            <div class="space-y-0.5">
                <flux:text class="!text-zinc-800 dark:!text-white font-medium">
                    {{ __('Seret & lepas file di sini') }}
                </flux:text>
                <flux:text size="sm">
                    {{ __('atau') }}
                    <span
                        class="font-medium text-zinc-800 underline dark:text-white">{{ __('pilih dari perangkat') }}</span>
                </flux:text>
            </div>

            @if ($hint)
                <flux:text size="sm" class="!text-xs">{{ $hint }}</flux:text>
            @endif
        </div>

        <!-- Sudah ada file -->
        <div x-show="file" x-cloak class="space-y-3">
            <div class="flex items-center gap-3">
                <template x-if="preview">
                    <img x-bind:src="preview" alt=""
                        class="size-14 shrink-0 rounded-md border border-zinc-200 object-cover dark:border-white/10" />
                </template>
                <template x-if="! preview">
                    <div
                        class="flex size-14 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/10">
                        <flux:icon name="document-text" class="size-6 text-zinc-500 dark:text-zinc-300" />
                    </div>
                </template>

                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium text-zinc-800 dark:text-white" x-text="file?.name"></div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400" x-text="file ? format(file.size) : ''"></div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <flux:button type="button" size="sm" variant="ghost" x-show="! uploading"
                        x-on:click.stop="pick()">
                        {{ __('Ganti') }}
                    </flux:button>

                    <flux:button type="button" size="sm" variant="ghost" icon="x-mark" x-on:click.stop="remove()"
                        :aria-label="__('Hapus file')" />
                </div>
            </div>

            <!-- Progress upload -->
            <div x-show="uploading" x-cloak class="space-y-1.5">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10" role="progressbar"
                    aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="progress">
                    <div class="h-full rounded-full bg-accent transition-all duration-150"
                        x-bind:style="`width: ${progress}%`"></div>
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400" aria-live="polite">
                    {{ __('Mengunggah...') }} <span x-text="progress + '%'"></span>
                </div>
            </div>
        </div>
    </div>

    <p x-show="clientError" x-cloak role="alert" class="text-sm font-medium text-red-500 dark:text-red-400"
        x-text="clientError"></p>

    <flux:error :name="$property" />
</div>
