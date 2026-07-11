{{-- Tab lingua it/en per i testi liberi del partner (spatie/laravel-translatable).
     Slot: $it / $en con i campi bindati a wire:model="...campo.it|.en".
     Pattern ripreso da matsuri (flux:tabs pill), adattato alla grafica AnimalAmo. --}}
@php
    $tabClass = '!h-auto !rounded-full !border !px-4 !py-1 !text-sm !font-semibold !border-[#C8C8C8] !text-[#959595] !bg-white data-[selected]:!border-brand-cyan data-[selected]:!text-brand-cyan data-[selected]:!bg-brand-cyan/10';
@endphp

<flux:tab.group>
    <flux:tabs class="!h-auto !gap-2 !border-b-0">
        <flux:tab name="it" class="{{ $tabClass }}">{{ __('partner.locale_it') }}</flux:tab>
        <flux:tab name="en" class="{{ $tabClass }}">{{ __('partner.locale_en') }}</flux:tab>
    </flux:tabs>

    <flux:tab.panel name="it" class="!p-0 !pt-3">{{ $it }}</flux:tab.panel>
    <flux:tab.panel name="en" class="!p-0 !pt-3">{{ $en }}</flux:tab.panel>
</flux:tab.group>
