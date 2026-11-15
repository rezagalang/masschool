<x-layouts::app :title="$module['label']">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center gap-4">
            <flux:avatar :icon="$module['icon']" :color="$module['tone'] ?? 'zinc'" size="lg" />
            <div>
                <flux:heading size="xl" level="1">{{ $module['label'] }}</flux:heading>
                <flux:text>{{ $module['description'] }}</flux:text>
            </div>
        </div>

        <flux:callout icon="information-circle" heading="Modul {{ $module['label'] }} belum dibuat">
            <flux:callout.text>Halaman ini akan diisi setelah modul selesai dikembangkan.</flux:callout.text>
        </flux:callout>
    </div>
</x-layouts::app>