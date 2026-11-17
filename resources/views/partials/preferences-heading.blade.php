@php($backUrl = app(\App\Http\Controllers\SettingsController::class)->backUrl(request()))

<flux:button variant="ghost" size="sm" icon="arrow-left" :href="$backUrl" wire:navigate class="-ms-2 mb-4">
    {{ __('Kembali') }}
</flux:button>

<div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('Preferences') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">{{ __('Manage your profile and account preferences') }}</flux:subheading>
    <flux:separator variant="subtle" />
</div>
