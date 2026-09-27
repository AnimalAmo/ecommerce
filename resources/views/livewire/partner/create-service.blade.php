{{-- Dashboard B2B – crea servizio (XD "Dashboard B2B – crea servizio", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    // Le quattro voci chieste dalla cliente (27/09/2026) nel suo ordine —
    // Struttura, Attività, Servizio professionale, Evento — più la Smartbox, che
    // resta l'ultima. 'servizi' porta l'etichetta "Servizio professionale" (il
    // valore del radio non cambia), 'eventi' è lo slug che la bozza scrive in
    // `type`: le chiavi di questo elenco sono i valori che next() valida.
    $services = [
        'struttura' => ['partner.create_service.struttura_title', 'partner.create_service.struttura_subtitle'],
        'attivita' => ['partner.create_service.attivita_title', 'partner.create_service.attivita_subtitle'],
        'servizi' => ['partner.create_service.servizi_title', 'partner.create_service.servizi_subtitle'],
        'eventi' => ['partner.create_service.eventi_title', 'partner.create_service.eventi_subtitle'],
        'smartbox' => ['partner.create_service.smartbox_title', 'partner.create_service.smartbox_subtitle'],
    ];

    // La card Smartbox porta a un wizard di dodici sezioni che, senza incasso
    // online, non produrrebbe niente di vendibile (richiesta della cliente del
    // 27/09/2026). Si legge qui, dove la scelta si fa. Il dato sta nella vista e
    // non fra quelli passati dal componente perché la card è una riga di questo
    // elenco: nessun altro pezzo della pagina lo usa.
    $smartboxPaymentRequired = auth()->user()?->partnerProfile?->canPublishFamily('smartbox') !== true;
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x542, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.create_service.heading') }}</h1>
                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.create_service.section') }}</h2>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.create_service.helper') }}</p>

                {{-- Scelta tipologia servizio: flux radio group, variant cards, in colonna --}}
                <flux:radio.group wire:model="service" variant="cards" class="radio-check mt-6 flex-col [--color-accent:#68CDEB] [&_[data-flux-radio-cards]]:flex-row-reverse [&_[data-flux-radio-cards]]:justify-end [&_[data-flux-heading]]:!text-[15px] [&_[data-flux-heading]]:!font-semibold [&_[data-flux-heading]]:!text-[#1E2E33] [&_[data-flux-subheading]]:!text-sm [&_[data-flux-subheading]]:!text-[#627277]">
                    @foreach ($services as $key => [$titleKey, $subtitleKey])
                        <flux:radio value="{{ $key }}" wire:key="svc-{{ $key }}" :label="__($titleKey)" :description="__($subtitleKey)" />
                    @endforeach
                </flux:radio.group>

                @error('service')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Avviso sulla card Smartbox: avvisa e non blocca, la scelta
                     resta selezionabile (si modifica anche un cofanetto già fatto). --}}
                @if ($smartboxPaymentRequired)
                    <flux:callout
                        variant="warning"
                        icon="credit-card"
                        class="mt-6"
                        :heading="__('partner.publish.smartbox_payment_required')"
                        :text="__('partner.publish.smartbox_payment_required_hint')"
                    >
                        <x-slot name="actions">
                            <flux:button size="sm" href="{{ route('partner.profile.payment') }}" class="!rounded-full !border-0 !bg-[#232A2C] !px-5 !font-bold !text-white hover:!bg-[#0D171A]">{{ __('partner.publish.smartbox_payment_required_cta') }}</flux:button>
                        </x-slot>
                    </flux:callout>
                @endif

                {{-- Azioni: Indietro (link alla dashboard) + Avanti (pill scuro) --}}
                <div class="mt-8 flex items-center justify-end gap-6">
                    <flux:button href="{{ route('partner.dashboard') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.create_service.back') }}</flux:button>
                    <flux:button wire:click="next" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.create_service.next') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
