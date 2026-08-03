{{-- Lavora con noi (XD: "Lavora con noi – inserimento campi"; app: "Diventa partner - click 'nome'") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    // Campo desktop XD: box 472x40, radius 3, bordo #C8C8C8, placeholder corsivo #555555.
    // Campo XD app: box 343x45, radius 5, bordo #C8C8C8, testo 14 (#0D171A, placeholder #959595); label 12 #555555.
    // La classe di flux:input finisce sul wrapper: gli stili del campo vanno con [&_input].
    $inputClass = '[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555] max-lg:[&_input]:!h-[45px] max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!text-sm max-lg:[&_input]:!text-[#0D171A] max-lg:[&_input]:placeholder:!not-italic max-lg:[&_input]:placeholder:!text-[#959595]';
    // flux:select (variante default) applica la classe direttamente al <select>.
    $selectClass = '!border-[#C8C8C8] max-lg:!h-[45px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A]';
    $labelClass = '!text-xs !text-gray-600 max-lg:!text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Banda gradiente (XD: 296deg #FF3EA526 → #68CDEB33); resta identica anche su mobile --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-[54px] max-lg:pb-10">
            <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">{{ __('partner.heading') }}</h1>
            <p class="mt-4 max-w-4xl text-lg text-black max-lg:mt-[10px] max-lg:text-[15px] max-lg:leading-5 max-lg:text-[#2B2B2B]">{{ __('partner.intro') }}</p>

            {{-- Card form (XD desktop: 1496x512, bianco op 0.5, bordo #E9E9E9, r 3); su mobile i campi
                 stanno direttamente sulla pagina, a tutta larghezza (XD app: 343px dentro px-4) --}}
            {{-- flux:field stacca la label con mb-3: sull'XD app il passo è 4px (label 271 → box 291) --}}
            {{-- Sotto lg la conferma è la modale dell'XD app, non la thank-you page --}}
            <form wire:submit="submit"
                x-data
                x-init="$wire.$set('confirmInPlace', window.innerWidth < 1024, false)"
                @resize.window="$wire.$set('confirmInPlace', window.innerWidth < 1024, false)"
                class="mt-10 rounded-[3px] border border-gray-150 bg-white/50 px-6 pb-8 pt-8 max-lg:mt-7 max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:p-0 max-lg:[&_[data-flux-label]]:!mb-1">
                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.first_name') }}</flux:label>
                        <flux:input wire:model="form.firstName" placeholder="{{ __('partner.first_name') }}" class="{{ $inputClass }}" />
                        <flux:error name="form.firstName" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.last_name') }}</flux:label>
                        <flux:input wire:model="form.lastName" placeholder="{{ __('partner.last_name') }}" class="{{ $inputClass }}" />
                        <flux:error name="form.lastName" />
                    </flux:field>
                    {{-- Utente ecommerce loggato: l'email è quella del suo account (è ciò che
                         a fine iscrizione decide quale utente promuovere a partner) --}}
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.email') }}</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="{{ __('partner.email') }}" :readonly="auth()->check()" class="{{ $inputClass }}" />
                        @auth
                            <flux:text class="!mt-1 !text-xs !text-[#555555]">{{ __('partner.email_account_hint') }}</flux:text>
                        @endauth
                        <flux:error name="form.email" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.phone') }}</flux:label>
                        <x-phone-input model="form.phone" :value="$form->phone" :number-placeholder="__('partner.phone')" :input-class="$inputClass" :select-class="$selectClass" />
                        <flux:error name="form.phone" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.website') }}</flux:label>
                        <flux:input wire:model="form.website" placeholder="{{ __('partner.website') }}" class="{{ $inputClass }}" />
                        <flux:error name="form.website" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.city') }}</flux:label>
                        <flux:input wire:model="form.city" placeholder="{{ __('partner.city') }}" class="{{ $inputClass }}" />
                        <flux:error name="form.city" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.business_name') }}</flux:label>
                        <flux:input wire:model="form.businessName" placeholder="{{ __('partner.business_name') }}" class="{{ $inputClass }}" />
                        <flux:error name="form.businessName" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.role') }}</flux:label>
                        <flux:select wire:model="form.role" placeholder="{{ __('partner.select_placeholder') }}" class="{{ $selectClass }}">
                            <flux:select.option>{{ __('partner.role_owner') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_manager') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_employee') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_other') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="form.role" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.offer_type') }}</flux:label>
                        <flux:select wire:model="form.offerType" placeholder="{{ __('partner.select_placeholder') }}" class="{{ $selectClass }}">
                            <flux:select.option>{{ __('partner.offer_accommodation') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_activities') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_dining') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_pet_services') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_other') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="form.offerType" />
                    </flux:field>
                </div>

                {{-- Descrizione: 1448x117 su desktop, 343x219 su mobile (XD app) --}}
                <flux:field class="mt-4">
                    <flux:label class="{{ $labelClass }}">{{ __('partner.description') }}</flux:label>
                    <flux:textarea wire:model="form.description" rows="4" placeholder="{{ __('partner.description_placeholder') }}" class="!h-[117px] !border-[#C8C8C8] placeholder:!italic placeholder:!text-[#555555] max-lg:!h-[219px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A] max-lg:placeholder:!not-italic max-lg:placeholder:!text-[#959595]" />
                    <flux:error name="form.description" />
                </flux:field>

                {{-- CTA: pill gialla a destra su desktop, 327x39 centrata su mobile (XD app) --}}
                <div class="mt-8 flex justify-end max-lg:justify-center">
                    <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-[327px]">{{ __('partner.submit') }}</flux:button>
                </div>
            </form>
        </div>
    </main>

    {{-- Conferma "Grazie!" (XD app "Diventa partner - click 'chiudi'") --}}
    <x-sweet-alert name="partner-application-sent" model="showConfirmation" wire:close="closeConfirmation"
        :heading="__('partner.thanks_heading')" :close-label="__('nav.close')">
        {{ __('partner.thanks_line_1') }}<br>{{ __('partner.thanks_line_2') }}

        <x-slot:decoration>
            {{-- Aeroplanino di carta (XD "Tracciato 654": nel mockup sborda di 7px sotto il bordo) --}}
            <svg viewBox="7.98 10.76 15.54 14.51" aria-hidden="true" class="absolute bottom-[8px] right-[28px] h-[15px] w-[16px] text-brand-cyan">
                <path fill="currentColor" d="M 23.45912933349609 18.17945861816406 C 23.47298622131348 18.16393852233887 23.48444366455078 18.14694023132324 23.49367904663086 18.12846374511719 C 23.49718856811523 18.12144470214844 23.50032997131348 18.11497688293457 23.50309944152832 18.1077709197998 C 23.51252365112305 18.08227920532227 23.5191764831543 18.05548477172852 23.5191764831543 18.02740478515625 C 23.5191764831543 17.99932098388672 23.51252365112305 17.97253036499023 23.5029182434082 17.94703483581543 C 23.50014686584473 17.93982887268066 23.49700927734375 17.93336296081543 23.49349212646484 17.92634391784668 C 23.4842586517334 17.90768432617188 23.47298622131348 17.890869140625 23.45894622802734 17.8753490447998 C 23.45469665527344 17.87054443359375 23.45118713378906 17.86537170410156 23.44656944274902 17.86093902587891 C 23.4288330078125 17.84394264221191 23.40887832641602 17.82897758483887 23.38578414916992 17.81826019287109 L 8.328280448913574 10.80331230163574 C 8.240521430969238 10.76211357116699 8.136136054992676 10.7807731628418 8.067221641540527 10.84950065612793 C 7.998679161071777 10.91804504394531 7.980387687683105 11.02243232727051 8.021403312683105 11.11037349700928 L 11.25516414642334 18.02721786499023 L 8.021403312683105 24.94424819946289 C 7.980387687683105 25.03237724304199 7.998679161071777 25.13657760620117 8.067221641540527 25.20512199401855 C 8.111563682556152 25.24946594238281 8.170868873596191 25.27292823791504 8.230730056762695 25.27292823791504 C 8.263799667358398 25.27292823791504 8.297056198120117 25.26590538024902 8.328280448913574 25.25131225585938 L 23.38578414916992 18.23654747009277 C 23.40887832641602 18.22564697265625 23.4288330078125 18.21068382263184 23.44656944274902 18.19386863708496 C 23.45137214660645 18.18943405151367 23.45469665527344 18.18444442749023 23.45912933349609 18.17945861816406 Z M 8.708874702453613 11.49004554748535 L 22.24547958374023 17.79645919799805 L 11.65700626373291 17.7962760925293 L 8.708874702453613 11.49004554748535 Z M 11.65719032287598 18.25816345214844 L 22.24584579467773 18.25834655761719 L 8.70887565612793 24.56457710266113 L 11.65719032287598 18.25816345214844 Z" />
            </svg>
        </x-slot:decoration>
    </x-sweet-alert>

    {{-- Su mobile il footer lascia il posto alla tabbar (XD app) --}}
    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
