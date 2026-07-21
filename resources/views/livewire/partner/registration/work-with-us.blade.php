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

    {{-- Banda gradiente (XD desktop: 296deg #FF3EA526 → #68CDEB33); su mobile la pagina è su bianco --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)] max-lg:bg-none">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-[54px] max-lg:pb-10">
            <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">{{ __('partner.heading') }}</h1>
            <p class="mt-4 max-w-4xl text-lg text-black max-lg:mt-[10px] max-lg:text-[15px] max-lg:leading-5 max-lg:text-[#2B2B2B]">{{ __('partner.intro') }}</p>

            {{-- Card form (XD desktop: 1496x512, bianco op 0.5, bordo #E9E9E9, r 3); su mobile i campi
                 stanno direttamente sulla pagina, a tutta larghezza (XD app: 343px dentro px-4) --}}
            {{-- flux:field stacca la label con mb-3: sull'XD app il passo è 4px (label 271 → box 291) --}}
            <form wire:submit="submit" class="mt-10 rounded-[3px] border border-gray-150 bg-white/50 px-6 pb-8 pt-8 max-lg:mt-7 max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:p-0 max-lg:[&_[data-flux-label]]:!mb-1">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.first_name') }}</flux:label>
                        <flux:input wire:model="form.firstName" placeholder="{{ __('partner.first_name') }}" class="{{ $inputClass }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.last_name') }}</flux:label>
                        <flux:input wire:model="form.lastName" placeholder="{{ __('partner.last_name') }}" class="{{ $inputClass }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.email') }}</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="{{ __('partner.email') }}" class="{{ $inputClass }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.phone') }}</flux:label>
                        <flux:input type="tel" wire:model="form.phone" placeholder="{{ __('partner.phone') }}" class="{{ $inputClass }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.website') }}</flux:label>
                        <flux:input wire:model="form.website" placeholder="{{ __('partner.website') }}" class="{{ $inputClass }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.city') }}</flux:label>
                        <flux:input wire:model="form.city" placeholder="{{ __('partner.city') }}" class="{{ $inputClass }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.business_name') }}</flux:label>
                        <flux:input wire:model="form.businessName" placeholder="{{ __('partner.business_name') }}" class="{{ $inputClass }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="{{ $labelClass }}">{{ __('partner.role') }}</flux:label>
                        <flux:select wire:model="form.role" placeholder="{{ __('partner.select_placeholder') }}" class="{{ $selectClass }}">
                            <flux:select.option>{{ __('partner.role_owner') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_manager') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_employee') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_other') }}</flux:select.option>
                        </flux:select>
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
                    </flux:field>
                </div>

                {{-- Descrizione: 1448x117 su desktop, 343x219 su mobile (XD app) --}}
                <flux:field class="mt-4">
                    <flux:label class="{{ $labelClass }}">{{ __('partner.description') }}</flux:label>
                    <flux:textarea wire:model="form.description" rows="4" placeholder="{{ __('partner.description_placeholder') }}" class="!h-[117px] !border-[#C8C8C8] placeholder:!italic placeholder:!text-[#555555] max-lg:!h-[219px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A] max-lg:placeholder:!not-italic max-lg:placeholder:!text-[#959595]" />
                </flux:field>

                {{-- CTA: pill gialla a destra su desktop, 327x39 centrata su mobile (XD app) --}}
                <div class="mt-8 flex justify-end max-lg:justify-center">
                    <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white max-lg:!h-[39px] max-lg:!w-[327px]">{{ __('partner.submit') }}</flux:button>
                </div>
            </form>
        </div>
    </main>

    {{-- Su mobile il footer lascia il posto alla tabbar (XD app) --}}
    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
