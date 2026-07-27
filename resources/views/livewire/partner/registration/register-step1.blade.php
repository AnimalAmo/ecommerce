{{-- Iscrizione B2B - step 1 (XD "Iscrizione B2B - step 1", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
{{-- Su mobile i campi seguono lo stile dei form ecommerce (XD app): 45px, r5, testo 14 --}}
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8] max-lg:[&_input]:!h-[45px] max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!text-sm max-lg:[&_input]:!text-[#0D171A]'; @endphp
{{-- Il select del prefisso (<x-phone-input>) riceve la classe sul <select>, non sul wrapper --}}
@php $selectClass = '!h-10 !rounded-[3px] !border-[#C8C8C8] max-lg:!h-[45px] max-lg:!rounded-[5px] max-lg:!text-sm max-lg:!text-[#0D171A]'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-header')

    <main class="flex-1 pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x665, r10, bordo #E9E9E9); su mobile i campi stanno direttamente sulla pagina (come work-with-us) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8 max-lg:rounded-none max-lg:border-0 max-lg:p-0">

                {{-- Testata card: titolo + "Step 1 di 2" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A] max-lg:text-[18px] max-lg:leading-[21px]">{{ __('partner.register.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8] max-lg:mt-0 max-lg:text-[13px]">{{ __('partner.register.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A] max-lg:mt-3 max-lg:text-[15px]">{{ __('partner.register.section_personal') }}</h2>

                <form wire:submit="submit" class="mt-8 max-lg:mt-6">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2">
                        {{-- Nome | Cognome --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.first_name') }} *</flux:label>
                            <flux:input wire:model="form.firstName" class="{{ $fieldClass }}" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.last_name') }} *</flux:label>
                            <flux:input wire:model="form.lastName" class="{{ $fieldClass }}" />
                        </flux:field>

                        {{-- Ragione Sociale | Email --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.business_name') }} *</flux:label>
                            <flux:input wire:model="form.businessName" class="{{ $fieldClass }}" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.email') }} *</flux:label>
                            <flux:input type="email" wire:model="form.email" class="{{ $fieldClass }}" />
                        </flux:field>

                        {{-- Indirizzo | (Provincia + Cap) --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.address') }} *</flux:label>
                            <flux:input wire:model="form.address" class="{{ $fieldClass }}" />
                        </flux:field>
                        <div class="grid grid-cols-2 gap-4">
                            <x-partner.province-select :provinces="$provinces" model="form.province" :label="__('partner.register.province')" />
                            <flux:field>
                                <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.zip') }} *</flux:label>
                                <flux:input wire:model="form.zip" inputmode="numeric" class="{{ $fieldClass }}" />
                            </flux:field>
                        </div>

                        {{-- Cellulare | Partita IVA --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.phone') }} *</flux:label>
                            <x-phone-input model="form.phone" :value="$form->phone" :input-class="$fieldClass" :select-class="$selectClass" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.vat') }} *</flux:label>
                            <flux:input wire:model="form.vat" class="{{ $fieldClass }}" />
                        </flux:field>

                        {{-- Codice Fiscale | PEC --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.tax_code') }} *</flux:label>
                            <flux:input wire:model="form.taxCode" class="{{ $fieldClass }}" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.pec') }} *</flux:label>
                            <flux:input type="email" wire:model="form.pec" class="{{ $fieldClass }}" />
                        </flux:field>

                        {{-- SDI | (vuoto) --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.register.sdi') }} *</flux:label>
                            <flux:input wire:model="form.sdi" class="{{ $fieldClass }}" />
                        </flux:field>
                    </div>

                    {{-- Azioni: Indietro (link) + Prosegui (pill scuro) --}}
                    {{-- Su mobile: CTA a tutta larghezza sopra, "Indietro" centrato sotto (pattern work-with-us) --}}
                    <div class="mt-12 flex items-center justify-end gap-6 max-lg:mt-8 max-lg:flex-col-reverse max-lg:items-stretch max-lg:gap-3">
                        <flux:button href="{{ route('home') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.register.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C] max-lg:!h-[39px] max-lg:!w-full">{{ __('partner.register.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
