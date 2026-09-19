{{-- Testi di una pagina del sito (design: is_pagina con tipo "Sito"). --}}
@php
    $path = parse_url($publicUrl, PHP_URL_PATH) ?: '/';
    $updated = $status['updated_at'] ? 'modificata il '.$status['updated_at']->locale('it')->translatedFormat('j M Y') : 'mai modificata';
@endphp

<div class="flex flex-col gap-[18px]">
    <x-admin.back-link :href="route('admin.pages.index')">Torna alle pagine</x-admin.back-link>

    <x-admin.page-header :heading="$definition['label']" :sub="$path.' · '.$updated">
        <x-slot:actions>
            <x-admin.button icon="arrow-top-right-on-square" :href="$publicUrl" target="_blank">Vedi sul sito</x-admin.button>
            <x-admin.button tone="primary" wire:click="save">Salva e pubblica</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid items-start gap-3.5 lg:grid-cols-3">
        <x-admin.card class="lg:col-span-2">
            <x-admin.tabs class="px-5" model="locale" :current="$locale" :items="[
                ['key' => 'it', 'label' => 'Italiano'],
                ['key' => 'en', 'label' => 'Inglese'],
            ]" />

            <div class="flex flex-col gap-5 p-5" wire:key="site-fields-{{ $locale }}">
                @if ($isOriginal)
                    <div class="flex gap-[11px] rounded-lg border border-gray-150 bg-[#FAFBFB] px-[15px] py-[13px]">
                        <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0 text-gray-600" />
                        <p class="m-0 text-[13px] leading-normal text-gray-600">Questa pagina mostra ancora il testo scritto nel sito. Se lasci i campi vuoti non cambia niente; quello che scrivi qui prende il posto dell'originale.</p>
                    </div>
                @endif

                @foreach ($fields as $field)
                    <flux:field wire:key="field-{{ $locale }}-{{ $field['index'] }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <flux:label class="!text-[13.5px] !font-bold !text-admin-rail">{{ $field['label'] }}</flux:label>
                            @if ($field['saved'])
                                <x-admin.button tone="ghost" size="sm" icon="arrow-uturn-left" class="!h-7 !px-2 !text-[12.5px]" wire:click="restore({{ $field['index'] }})">Ripristina il testo originale</x-admin.button>
                            @endif
                        </div>

                        @if ($field['type'] === 'line')
                            <flux:input wire:model="values.{{ $locale }}.{{ $field['index'] }}" :placeholder="$field['original']" />
                        @else
                            <flux:textarea
                                wire:model="values.{{ $locale }}.{{ $field['index'] }}"
                                :placeholder="$field['original']"
                                :rows="$field['type'] === 'paragraphs' ? 9 : 3"
                                resize="vertical"
                            />
                        @endif

                        @if ($field['type'] === 'paragraphs')
                            <flux:description>Lascia una riga vuota tra un paragrafo e l'altro.</flux:description>
                        @endif

                        <flux:error name="values.{{ $locale }}.{{ $field['index'] }}" />
                    </flux:field>
                @endforeach
            </div>
        </x-admin.card>

        <div class="flex min-w-0 flex-col gap-3.5">
            <x-admin.card heading="Impostazioni">
                <div class="flex flex-col gap-4 p-5 text-[14px]">
                    <div>
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">Tipo di pagina</p>
                        <p class="mt-1.5 text-gray-600">Pagina del sito</p>
                    </div>
                    <div>
                        <p class="m-0 text-[13.5px] font-bold text-admin-rail">Indirizzo</p>
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="mt-1.5 inline-block text-admin-teal hover:underline">{{ $path }}</a>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.notice tone="warning" heading="Pagina con una struttura fissa">
                Qui cambi i testi, non la disposizione dei blocchi: immagini, griglie e pulsanti restano dove sono.
            </x-admin.notice>
        </div>
    </div>
</div>
