{{-- Dashboard B2B – tipologia attività/eventi - nome (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]';
    // Checkbox tondi cyan + label SemiBold 15 #555, come lo step dei servizi animali.
    $checkboxWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x329, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 2 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_name.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_name.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.activity_name.section') }}</h2>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_name.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Campo unico a tutta larghezza (XD: 752x40, r3, bordo #C8C8C8), localizzato it/en --}}
                    <x-partner.locale-tabs>
                        <x-slot:it>
                            <flux:field>
                                <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.activity_name.field_label') }}</flux:label>
                                <flux:input wire:model="name.it" class="{{ $fieldClass }}" />
                            </flux:field>
                        </x-slot:it>
                        <x-slot:en>
                            <flux:field>
                                <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.activity_name.field_label') }} (EN)</flux:label>
                                <flux:input wire:model="name.en" class="{{ $fieldClass }}" />
                            </flux:field>
                        </x-slot:en>
                    </x-partner.locale-tabs>

                    {{-- Tipologie a scelta multipla, facoltative (risposte della cliente,
                         27/09/2026): le categorie professionali per le attività, le
                         tipologie di evento per gli eventi. Le voci le dà
                         ServiceOptionLabels, la stessa lista che valida lo step. --}}
                    <div class="mt-6">
                        <p class="text-xs font-normal text-[#555555]">{{ __($isEvent ? 'partner.activity_name.field_categories_event' : 'partner.activity_name.field_categories') }}</p>
                        <p class="mt-1 text-xs font-normal text-[#959595]">{{ __('partner.activity_name.field_categories_hint') }}</p>

                        {{-- wire:model.live: senza `.live` il testo libero di "Altro" qui
                             sotto non comparirebbe fino al submit. --}}
                        {{-- `$optionLabel` e non `$label`: la variabile sopravvive al
                             foreach, e `flux:input` elenca `label` fra i prop che
                             eredita dallo scope del chiamante — i campi qui sotto si
                             auto-avvolgerebbero in un campo con l'ultima etichetta
                             dell'elenco. --}}
                        <div class="mt-3 grid grid-cols-1 gap-y-2 sm:grid-cols-2 sm:gap-x-4 {{ $checkboxWrap }}">
                            @foreach ($categoryOptions as $slug => $optionLabel)
                                <flux:checkbox wire:model.live="categories" value="{{ $slug }}" :label="$optionLabel" wire:key="cat-{{ $slug }}" />
                            @endforeach
                        </div>

                        {{-- Campo composto a mano: Flux non inietta l'errore, va messo qui.
                             `name="categories"` prende anche gli errori di
                             `categories.*` (flux:error cerca la chiave `.*` in
                             fallback), che è dove finisce uno slug forgiato. --}}
                        <flux:error name="categories" />

                        @if (in_array('altro', $categories, true))
                            <div class="mt-3">
                                <x-partner.locale-tabs>
                                    <x-slot:it>
                                        <flux:field>
                                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.activity_name.field_categories_other') }}</flux:label>
                                            <flux:input wire:model="categoriesOther.it" maxlength="200" class="{{ $fieldClass }}" />
                                            <flux:error name="categoriesOther.it" />
                                        </flux:field>
                                    </x-slot:it>
                                    <x-slot:en>
                                        <flux:field>
                                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.activity_name.field_categories_other') }} (EN)</flux:label>
                                            <flux:input wire:model="categoriesOther.en" maxlength="200" class="{{ $fieldClass }}" />
                                            <flux:error name="categoriesOther.en" />
                                        </flux:field>
                                    </x-slot:en>
                                </x-partner.locale-tabs>
                            </div>
                        @endif
                    </div>

                    {{-- Azioni: Indietro + Avanti (pill scuro). Indietro NON va allo step
                         del tipo: le card «Servizio professionale» ed «Evento» lo saltano
                         (difetto W4, 28/09/2026). La destinazione la sceglie
                         ActivityName::render() con serviceChoiceBackUrl(). --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ $backUrl }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.activity_name.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.activity_name.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
