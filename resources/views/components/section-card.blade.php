@props([
    'label',
    'icon' => 'document-text',
    'count' => null,
    'tone' => null,
    'grid' => true,
])

@php
    // Warna per section, kunci = label. Tambah section baru cukup di sini
    $tones = [
        'Data Diri' => ['bar' => 'bg-sky-50 dark:bg-sky-950/30', 'chip' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-300'],
        'Tempat Tinggal' => ['bar' => 'bg-emerald-50 dark:bg-emerald-950/30', 'chip' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300'],
        'Kesehatan' => ['bar' => 'bg-rose-50 dark:bg-rose-950/30', 'chip' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300'],
        'Pendidikan' => ['bar' => 'bg-indigo-50 dark:bg-indigo-950/30', 'chip' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300'],
        'Ayah' => ['bar' => 'bg-blue-50 dark:bg-blue-950/30', 'chip' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300'],
        'Ibu' => ['bar' => 'bg-pink-50 dark:bg-pink-950/30', 'chip' => 'bg-pink-100 text-pink-600 dark:bg-pink-900/50 dark:text-pink-300'],
        'Wali' => ['bar' => 'bg-amber-50 dark:bg-amber-950/30', 'chip' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300'],
        'Hobi' => ['bar' => 'bg-violet-50 dark:bg-violet-950/30', 'chip' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-300'],
        'Bukti Transfer' => ['bar' => 'bg-green-50 dark:bg-green-950/30', 'chip' => 'bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-300'],
    ];

    $default = ['bar' => 'bg-zinc-50 dark:bg-zinc-900/40', 'chip' => 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'];

    $colors = $tones[$tone ?? $label] ?? $default;
@endphp

<section {{ $attributes->class(['ss-section overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/40']) }}>
    <header class="ss-head flex items-center gap-3 border-b border-zinc-200 px-5 py-3 dark:border-zinc-700 {{ $colors['bar'] }}">
        <span class="ss-chip flex size-8 items-center justify-center rounded-lg {{ $colors['chip'] }}">
            <flux:icon :name="$icon" class="size-4" />
        </span>

        <flux:heading class="flex-1">{{ $label }}</flux:heading>

        @isset($actions)
            <div class="ss-noprint print:hidden">{{ $actions }}</div>
        @endisset

        @if ($count !== null)
            <flux:badge size="sm" inset="top bottom" class="print:hidden">{{ $count }} {{ __('data') }}</flux:badge>
        @endif
    </header>

    @if ($grid)
        <dl class="ss-grid grid gap-x-8 gap-y-3 p-5 text-sm sm:grid-cols-2">
            {{ $slot }}
        </dl>
    @else
        <div class="p-5">
            {{ $slot }}
        </div>
    @endif
</section>