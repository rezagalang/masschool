<x-layouts::app.header :title="__('App Settings')">
    <flux:main container>
        <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 py-10">
            @include('pages.admin.app-settings.nav')

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as $key => $module)
                    <flux:card
                        class="group flex h-full flex-col gap-3 !p-5 shadow-xs transition-colors duration-150 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <!-- Ikon + kode URL -->
                        <div class="flex items-start justify-between gap-3">
                            <flux:avatar :icon="$module['icon']" :color="$module['tone'] ?? 'zinc'"
                                class="!rounded-lg transition-transform duration-150 group-hover:scale-105" />

                            <flux:tooltip content="Kode URL modul">
                                <flux:badge size="sm" color="zinc" icon="link" inset="top bottom">
                                    {{ $key }}
                                </flux:badge>
                            </flux:tooltip>
                        </div>

                        <!-- Judul & deskripsi -->
                        <div class="min-w-0 space-y-0.5">
                            <flux:heading class="truncate font-semibold tracking-tight">
                                {{ $module['label'] }}
                            </flux:heading>
                            <flux:text size="sm" class="line-clamp-2">{{ $module['description'] }}</flux:text>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </div>
    </flux:main>
</x-layouts::app.header>