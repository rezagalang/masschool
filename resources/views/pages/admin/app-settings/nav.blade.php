<div class="flex flex-col gap-6">
    <div class="space-y-4">
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="$backUrl" wire:navigate class="-ms-2">
            Kembali
        </flux:button>

        <div class="flex items-center gap-4">
            <flux:avatar icon="cog-6-tooth" color="zinc" class="!rounded-lg" />
            <div class="space-y-0.5">
                <flux:heading size="xl" level="1" class="tracking-tight">App Settings</flux:heading>
                <flux:text>Pengaturan global yang berlaku untuk semua unit.</flux:text>
            </div>
        </div>
    </div>

    <flux:navbar class="-mb-px border-b border-zinc-200 dark:border-white/10">
        <flux:navbar.item icon="building-office-2" :href="route('app-settings.units')"
            :current="request()->routeIs('app-settings.units*')" wire:navigate>
            Unit
        </flux:navbar.item>
        <flux:navbar.item icon="squares-2x2" :href="route('app-settings.modules')"
            :current="request()->routeIs('app-settings.modules')" wire:navigate>
            Modul
        </flux:navbar.item>
    </flux:navbar>
</div>