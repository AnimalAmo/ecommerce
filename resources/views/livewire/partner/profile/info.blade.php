{{-- Profilo partner – Informazioni personali (XD "Profilo - info personali") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp
@php $selectClass = '!h-10 !rounded-[3px] !border-[#C8C8C8]'; @endphp
@php $labelClass = '!text-xs !font-normal !text-[#555555]'; @endphp

<div class="flex min-h-screen flex-col bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="flex flex-col gap-8 md:flex-row">

                @include('partials.partner-profile-sidebar')

                <div class="hidden w-px self-stretch bg-white md:block"></div>

                {{-- Card contenuto --}}
                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.info_heading') }}</h1>

                    <form wire:submit="save" class="mt-8">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-3">
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.first_name') }} *</flux:label>
                                <flux:input wire:model="form.firstName" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.last_name') }} *</flux:label>
                                <flux:input wire:model="form.lastName" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.email') }} *</flux:label>
                                <flux:input type="email" wire:model="form.email" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.tax_code') }} *</flux:label>
                                <flux:input wire:model="form.taxCode" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.phone') }} *</flux:label>
                                <x-phone-input model="form.phone" :value="$form->phone" :input-class="$fieldClass" :select-class="$selectClass" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.vat') }} *</flux:label>
                                <flux:input wire:model="form.vat" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.business_name') }} *</flux:label>
                                <flux:input wire:model="form.businessName" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.address') }} *</flux:label>
                                <flux:input wire:model="form.address" class="{{ $fieldClass }}" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <x-partner.province-select :provinces="$provinces" model="form.province" :label="__('partner.profile.province')" />
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.zip') }} *</flux:label>
                                    <flux:input wire:model="form.zip" inputmode="numeric" class="{{ $fieldClass }}" />
                                </flux:field>
                            </div>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.city') }} *</flux:label>
                                <flux:input wire:model="form.city" class="{{ $fieldClass }}" />
                            </flux:field>
                        </div>

                        {{-- Orari di apertura o disponibilità (richiesta della cliente, 27/09/2026):
                             facoltativi, testo libero localizzato it/en. Fuori dalla griglia a tre
                             colonne dei dati anagrafici: è una riga sola, più larga, e i tab lingua
                             non stanno in una colonna. Tab presi da
                             livewire/partner/structure/hotel-title.blade.php (x-partner.locale-tabs). --}}
                        <div class="mt-6 max-w-[520px]">
                            <x-partner.locale-tabs>
                                <x-slot:it>
                                    {{-- Campo composto a mano: Flux non inietta l'errore, flux:error va messo a mano. --}}
                                    <flux:field>
                                        <flux:label class="{{ $labelClass }}">{{ __('partner.profile.opening_hours') }}</flux:label>
                                        <flux:input wire:model="form.openingHours.it" class="{{ $fieldClass }}" />
                                        <flux:error name="form.openingHours.it" />
                                    </flux:field>
                                </x-slot:it>
                                <x-slot:en>
                                    <flux:field>
                                        <flux:label class="{{ $labelClass }}">{{ __('partner.profile.opening_hours') }} (EN)</flux:label>
                                        <flux:input wire:model="form.openingHours.en" class="{{ $fieldClass }}" />
                                        <flux:error name="form.openingHours.en" />
                                    </flux:field>
                                </x-slot:en>
                            </x-partner.locale-tabs>
                            <p class="mt-2 text-xs text-[#555555]">{{ __('partner.profile.opening_hours_hint') }}</p>
                        </div>

                        {{-- Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6): voci
                             nuove e facoltative, mai il cellulare o l'email qui sopra né la sede
                             legale. Compaiono sulle schede solo con la spunta di consenso; telefono,
                             WhatsApp, email e sito si nascondono quando il partner incassa online,
                             perché scavalcherebbero la piattaforma. Il testo introduttivo lo dice
                             prima che il partner scriva. --}}
                        <section class="mt-10 border-t border-[#E2EAEB] pt-8">
                            <h2 class="text-lg font-bold text-[#0D171A]">{{ __('partner.profile.public_contacts_heading') }}</h2>
                            <p class="mt-2 max-w-[720px] text-xs text-[#555555]">{{ __('partner.profile.public_contacts_intro') }}</p>

                            {{-- items-start: flux:field è un grid, un errore sotto un campo non deve
                                 spostare quelli accanto. --}}
                            <div class="mt-6 grid grid-cols-1 items-start gap-x-4 gap-y-5 md:grid-cols-3">
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_phone') }}</flux:label>
                                    <x-phone-input model="form.publicPhone" :value="$form->publicPhone" :input-class="$fieldClass" :select-class="$selectClass" />
                                    <flux:error name="form.publicPhone" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_whatsapp') }}</flux:label>
                                    <x-phone-input model="form.publicWhatsapp" :value="$form->publicWhatsapp" :input-class="$fieldClass" :select-class="$selectClass" />
                                    <flux:error name="form.publicWhatsapp" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_email') }}</flux:label>
                                    <flux:input type="email" wire:model="form.publicEmail" class="{{ $fieldClass }}" />
                                    <flux:error name="form.publicEmail" />
                                </flux:field>

                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_website') }}</flux:label>
                                    <flux:input type="url" wire:model="form.publicWebsite" placeholder="https://" class="{{ $fieldClass }}" />
                                    <flux:error name="form.publicWebsite" />
                                </flux:field>
                                <flux:field class="md:col-span-2">
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_address') }}</flux:label>
                                    <flux:input wire:model="form.publicAddress" class="{{ $fieldClass }}" />
                                    <flux:error name="form.publicAddress" />
                                </flux:field>
                            </div>

                            <flux:field variant="inline" class="mt-6">
                                <flux:checkbox wire:model="form.publicContactsConsent" />
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.public_contacts_consent') }}</flux:label>
                                <flux:error name="form.publicContactsConsent" />
                            </flux:field>
                        </section>

                        <div class="mt-10 flex items-center justify-end">
                            <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-10 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.profile.save') }}</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
