{{-- Iscrizione B2B - step 2 (XD "Iscrizione B2B – step 2", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $services = [
        'struttura' => ['partner.register2.struttura_title', 'partner.register2.struttura_subtitle'],
        'attivita' => ['partner.register2.attivita_title', 'partner.register2.attivita_subtitle'],
        'servizi' => ['partner.register2.servizi_title', 'partner.register2.servizi_subtitle'],
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-header')

    <main class="flex-1 pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x429, r10, bordo #E9E9E9); su mobile il contenuto sta direttamente sulla pagina (come work-with-us) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8 max-lg:rounded-none max-lg:border-0 max-lg:p-0">

                {{-- Testata card: titolo + "Step 2 di 2" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A] max-lg:text-[18px] max-lg:leading-[21px]">{{ __('partner.register.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8] max-lg:mt-0 max-lg:text-[13px]">{{ __('partner.register2.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A] max-lg:mt-3 max-lg:text-[15px]">{{ __('partner.register2.section') }}</h2>

                {{-- Account disattivato: l'iscrizione non si chiude, detto al caricamento
                     (chi arriva qui per URL o col tasto indietro non deve scoprirlo cliccando) --}}
                @if ($accountInactive)
                    <div class="mt-4 rounded-[5px] border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-sm text-red-500">{{ __('partner.register.error_account_inactive') }}</p>
                        <flux:link href="{{ route('contact') }}" variant="ghost" class="!mt-1 !inline-block !text-xs !font-bold !text-[#68CDEB] hover:!text-ink">{{ __('partner.register.contact_support') }}</flux:link>
                    </div>
                @endif

                {{-- Scelta tipologia servizio: flux radio group, variant cards, in colonna --}}
                <flux:radio.group wire:model="service" variant="cards" class="radio-check mt-6 flex-col [--color-accent:#68CDEB] [&_[data-flux-radio-cards]]:flex-row-reverse [&_[data-flux-radio-cards]]:justify-end [&_[data-flux-heading]]:!text-[15px] [&_[data-flux-heading]]:!font-semibold [&_[data-flux-heading]]:!text-[#1E2E33] [&_[data-flux-subheading]]:!text-sm [&_[data-flux-subheading]]:!text-[#627277]">
                    @foreach ($services as $key => [$titleKey, $subtitleKey])
                        <flux:radio value="{{ $key }}" wire:key="svc-{{ $key }}" :label="__($titleKey)" :description="__($subtitleKey)" />
                    @endforeach
                </flux:radio.group>

                @error('service')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Azioni: Indietro (link a step 1) + Crea un account (pill scuro) --}}
                {{-- Su mobile: CTA a tutta larghezza sopra, "Indietro" centrato sotto (pattern work-with-us) --}}
                <div class="mt-8 flex items-center justify-end gap-6 max-lg:flex-col-reverse max-lg:items-stretch max-lg:gap-3">
                    <flux:button href="{{ route('partner.register') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.register.back') }}</flux:button>
                    <flux:button wire:click="createAccount" :disabled="$accountInactive" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C] max-lg:!h-[39px] max-lg:!w-full">{{ __('partner.register2.submit') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
