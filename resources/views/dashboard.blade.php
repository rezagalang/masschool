<x-layouts::app :title="$module['label']">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center gap-4">
            <flux:avatar :icon="$module['icon']" :color="$module['tone'] ?? 'zinc'" size="lg" />
            <div>
                <flux:heading size="xl" level="1">Dashboard {{ $module['label'] }}</flux:heading>
                <flux:text>{{ $module['description'] }}</flux:text>
            </div>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            @foreach (range(1, 3) as $i)
                <flux:card>
                    <flux:heading size="lg">Ringkasan {{ $i }}</flux:heading>
                    <flux:text class="mt-1">Belum ada data untuk ditampilkan.</flux:text>
                </flux:card>
            @endforeach
        </div>
    </div>
</x-layouts::app>