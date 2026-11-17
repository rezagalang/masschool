<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance preferences')] #[Layout('layouts::app.header')] class extends Component {
    //
}; ?>

<flux:main container>
    <section class="mx-auto w-full max-w-5xl py-10">
        @include('partials.preferences-heading')

        <flux:heading level="2" class="sr-only">{{ __('Appearance preferences') }}</flux:heading>

        <x-pages::preferences.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </x-pages::preferences.layout>
    </section>
</flux:main>
