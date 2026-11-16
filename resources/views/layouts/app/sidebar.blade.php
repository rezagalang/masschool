<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @php
        // hanya Kepala Sekolah yang boleh ganti unit
        $canSwitchUnit = auth()->user()->hasRole('Kepala Sekolah');

        $route = request()->route();
        $routeParams = $route?->parameters() ?? [];

        $currentUnit =
            ($routeParams['unit'] ?? null) instanceof \App\Models\Unit
                ? $routeParams['unit']
                : (session('unit_id')
                    ? \App\Models\Unit::find(session('unit_id'))
                    : null);

        $accessibleUnits = $canSwitchUnit
            ? \App\Models\Unit::query()->where('is_active', true)->orderBy('id')->get()
            : collect();

        $unitUrl = function ($unit) use ($route, $routeParams) {
            // Route dengan parameter lain (mis. {student}) tidak dibawa ke unit lain karena ID-nya spesifik unit asal.
            $extraParams = array_diff(array_keys($routeParams), ['unit', 'module']);

            if ($route?->getName() && array_key_exists('unit', $routeParams) && $extraParams === []) {
                return route($route->getName(), array_merge($routeParams, ['unit' => $unit]));
            }

            return route('modules.select', $unit);
        };

        // Modul aktif: prioritas dari parameter {module}, prefix route, lalu session.
        $routeModule = \Illuminate\Support\Str::before((string) $route?->getName(), '.');
        $moduleKey =
            $routeParams['module'] ??
            (array_key_exists($routeModule, config('modules')) ? $routeModule : session('module'));
        $currentModule = $moduleKey ? config('modules.' . $moduleKey) : null;
        $homeUrl = $currentUnit ? route('modules.select', $currentUnit) : route('dashboard');
    @endphp

    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ $homeUrl }}" wire:navigate />
            <flux:sidebar.collapse />
        </flux:sidebar.header>

        <!-- Ganti unit: khusus Kepala Sekolah (user lain hanya melihat nama unit). -->
        @if ($canSwitchUnit && $accessibleUnits->isNotEmpty())
            <!-- Sidebar terbuka: tombol dengan teks -->
            <flux:dropdown position="bottom" align="start" class="px-2 [[data-flux-sidebar-collapsed-desktop]_&]:hidden">
                <flux:button variant="filled" size="sm" icon="building-office" icon:trailing="chevrons-up-down"
                    class="w-full justify-between">
                    {{ $currentUnit?->unit ?? __('Pilih Unit') }}
                </flux:button>

                @include('partials.unit-switcher-menu')
            </flux:dropdown>

            <!-- Sidebar ciut: tombol ikon saja -->
            <flux:dropdown position="right" align="start"
                class="hidden w-full [[data-flux-sidebar-collapsed-desktop]_&]:block">
                <flux:button variant="filled" size="sm" icon="building-office" class="w-full" />

                @include('partials.unit-switcher-menu')
            </flux:dropdown>
        @elseif ($currentUnit)
            <div
                class="mx-2 flex items-center justify-center gap-2 rounded-md bg-zinc-100 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 [[data-flux-sidebar-collapsed-desktop]_&]:px-0">
                <flux:icon.building-office class="size-4 shrink-0" />
                <span class="truncate [[data-flux-sidebar-collapsed-desktop]_&]:hidden">{{ $currentUnit->unit }}</span>
            </div>
        @endif

        <flux:sidebar.nav>
            <flux:sidebar.item icon="squares-2x2" :href="$homeUrl" :current="request()->routeIs('modules.select')"
                wire:navigate>
                {{ __('Semua Modul') }}
            </flux:sidebar.item>

            <!-- Menu modul yang sedang dibuka (config/modules.php) -->
            @if ($currentUnit && $currentModule)
                <!-- Judul modul: disembunyikan saat sidebar ciut -->
                <div
                    class="mt-2 px-3 py-2 text-xs leading-none text-zinc-400 [[data-flux-sidebar-collapsed-desktop]_&]:hidden">
                    {{ $currentModule['label'] }}
                </div>

                <flux:sidebar.item icon="home"
                    :href="route('unit.dashboard', ['unit' => $currentUnit, 'module' => $moduleKey])"
                    :current="request()->routeIs('unit.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <!-- Menu khusus modul: isi kunci 'menu' di config/modules.php. -->
                @php $lastGroup = null; @endphp

                @foreach ($currentModule['menu'] ?? [] as $item)
                    <!-- Judul grup (mis. Laporan) muncul sekali sebelum item pertama grup tsb -->
                    @if (! empty($item['group']) && $item['group'] !== $lastGroup)
                        @php $lastGroup = $item['group']; @endphp

                        <div
                            class="mt-2 px-3 py-2 text-xs leading-none text-zinc-400 [[data-flux-sidebar-collapsed-desktop]_&]:hidden">
                            {{ $item['group'] }}
                        </div>
                    @endif

                    <flux:sidebar.item :icon="$item['icon'] ?? 'minus'"
                        :href="route($item['route'], ['unit' => $currentUnit])"
                        :current="request()->routeIs($item['active'] ?? $item['route'])" wire:navigate>
                        {{ $item['label'] }}
                    </flux:sidebar.item>
                @endforeach
            @endif
        </flux:sidebar.nav>

        <flux:spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>