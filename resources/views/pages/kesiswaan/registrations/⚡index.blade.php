<?php

use App\Livewire\Tables\RegistrationTable;
use Livewire\Attributes\Title;

new #[Title('Calon Siswa')] class extends RegistrationTable {}; ?>

<div>
    <x-datatable :table="$this" />

    <!-- Tinjau calon siswa: data + bukti transfer, lalu setujui -->
    <flux:modal name="review" class="w-full max-w-3xl !p-0 overflow-hidden">
        @if ($reg = $this->review)
            @php
                $proofUrl = route('kesiswaan.registrations.proof', ['unit' => $unitCode, 'registration' => $reg->id]);
                $isPdf = str_ends_with(strtolower((string) $reg->bukti_transfer), '.pdf');
                $sections = \App\Support\Kesiswaan\RegistrationFields::display($reg->getAttributes());
            @endphp

            <div wire:key="review-{{ $reg->id }}" class="flex max-h-[85vh] flex-col">
                <!-- Header -->
                <div class="flex items-center gap-4 px-6 pb-4 pe-14 pt-6">
                    <flux:avatar :name="$reg->a_namalengkap" color="auto" size="lg" circle />

                    <div class="min-w-0 flex-1">
                        <flux:heading size="lg" class="truncate">{{ $reg->a_namalengkap }}</flux:heading>
                        <flux:text size="sm" class="truncate">
                            {{ $reg->no_pendaftaran }} · {{ __('TA') }} {{ $reg->tahunajaran }}
                        </flux:text>
                    </div>

                    <flux:badge :color="$reg->status_daftar ? 'green' : 'amber'" size="sm" inset="top bottom">
                        {{ $reg->status_daftar ? __('Disetujui') : __('Menunggu') }}
                    </flux:badge>
                </div>

                <flux:separator />

                <!-- Isi -->
                <div class="flex-1 space-y-4 overflow-y-auto bg-zinc-50/60 p-6 dark:bg-zinc-900/40">
                    <!-- Bukti transfer -->
                    <x-section-card :label="__('Bukti Transfer')" icon="banknotes" :grid="false">
                        <x-slot:actions>
                            <flux:button :href="$proofUrl" target="_blank" size="sm" variant="ghost"
                                icon-trailing="arrow-top-right-on-square">
                                {{ __('Buka di tab baru') }}
                            </flux:button>
                        </x-slot:actions>

                        @if ($isPdf)
                            <iframe src="{{ $proofUrl }}"
                                class="h-80 w-full rounded-lg border border-zinc-200 dark:border-zinc-700"></iframe>
                        @else
                            <div class="flex justify-center rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900/40">
                                <img src="{{ $proofUrl }}" alt="{{ __('Bukti Transfer') }}"
                                    class="max-h-80 rounded-md object-contain" />
                            </div>
                        @endif
                    </x-section-card>

                    <!-- Data calon siswa -->
                    @foreach ($sections as $section)
                        <x-section-card :label="$section['label']" :icon="$section['icon'] ?? 'document-text'"
                            :count="count($section['rows'])">
                            @foreach ($section['rows'] as $label => $value)
                                <div class="min-w-0">
                                    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                    <dd class="mt-0.5 break-words font-medium">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </x-section-card>
                    @endforeach
                </div>

                <flux:separator />

                <!-- Aksi -->
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    @if ($reg->isPending())
                        <flux:text size="sm" class="hidden sm:block">
                            {{ __('NIS dibuat otomatis saat disetujui.') }}
                        </flux:text>

                        <div class="ms-auto flex gap-2">
                            <flux:modal.close>
                                <flux:button variant="danger">{{ __('Tutup') }}</flux:button>
                            </flux:modal.close>

                            <flux:button variant="primary" icon="check" wire:click="approve"
                                wire:loading.attr="disabled" wire:target="approve">
                                {{ __('Setujui') }}
                            </flux:button>
                        </div>
                    @else
                        @php
                            $student = \App\Models\Kesiswaan\Student::where('registrasi_id', $reg->id)->first(['id', 'nis']);
                        @endphp

                        <flux:text size="sm">
                            @if ($student)
                                {{ __('NIS') }}: <span class="font-mono font-medium">{{ $student->nis }}</span>
                            @endif
                        </flux:text>

                        <div class="ms-auto flex gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost">{{ __('Tutup') }}</flux:button>
                            </flux:modal.close>

                            @if ($student)
                                <flux:button variant="primary" icon-trailing="arrow-right"
                                    :href="route('kesiswaan.students.show', ['unit' => $unitCode, 'student' => $student->id])"
                                    wire:navigate>
                                    {{ __('Lihat data siswa') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>