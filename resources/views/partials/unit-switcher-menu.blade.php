<flux:menu class="min-w-56">
    <flux:menu.heading>{{ __('Ganti unit') }}</flux:menu.heading>

    @foreach ($accessibleUnits as $unit)
        <flux:menu.item :href="$unitUrl($unit)" :icon="$currentUnit?->id === $unit->id ? 'check' : 'building-office'"
            wire:navigate>
            {{ $unit->unit }}
        </flux:menu.item>
    @endforeach

    <flux:menu.separator />

    <flux:menu.item :href="route('units.select')" icon="squares-2x2" wire:navigate>
        {{ __('Lihat semua unit') }}
    </flux:menu.item>
</flux:menu>
