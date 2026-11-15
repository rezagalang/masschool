@props(['span' => null])

@php
    // Grid form 12 kolom (md+): span null = setengah, 'full' = penuh
    // Atau angka (2/3/4/6/12) tertulis utuh untuk Tailwind.
    $class = match (true) {
        $span === 'full', (int) $span === 12 => 'md:col-span-12',
        (int) $span === 2 => 'md:col-span-2',
        (int) $span === 3 => 'md:col-span-3',
        (int) $span === 4 => 'md:col-span-4',
        default => 'md:col-span-6',
    };
@endphp

<div {{ $attributes->class([$class, 'min-w-0']) }}>
    {{ $slot }}
</div>