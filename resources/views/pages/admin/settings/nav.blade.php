<div class="flex flex-col gap-6">
    <div class="space-y-4">
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="$backUrl" wire:navigate class="-ms-2">
            Kembali
        </flux:button>

        <div class="flex items-center gap-4">
            <flux:avatar icon="cog" color="zinc" class="!rounded-lg" />
            <div class="space-y-0.5">
                <flux:heading size="xl" level="1" class="tracking-tight">Settings</flux:heading>
                <flux:text>Pengaturan global yang berlaku untuk semua unit.</flux:text>
            </div>
        </div>
    </div>

    <flux:navbar class="-mb-px border-b border-zinc-200 dark:border-white/10">
        <flux:navbar.item icon="building-office-2" :href="route('settings.units')"
            :current="($tab ?? null) === 'units'" wire:navigate>
            Unit
        </flux:navbar.item>
        <flux:navbar.item icon="calendar-days" :href="route('settings.academic-periods')"
            :current="($tab ?? null) === 'academic-periods'" wire:navigate>
            Tahun Ajaran
        </flux:navbar.item>
        <flux:navbar.item icon="squares-2x2" :href="route('settings.modules')"
            :current="($tab ?? null) === 'modules'" wire:navigate>
            Modul
        </flux:navbar.item>
    </flux:navbar>
</div>