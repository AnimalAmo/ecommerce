{{-- Dashboard B2B – crea servizio (XD "Dashboard B2B – crea servizio", artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $services = [
        'struttura' => ['partner.create_service.struttura_title', 'partner.create_service.struttura_subtitle'],
        'attivita' => ['partner.create_service.attivita_title', 'partner.create_service.attivita_subtitle'],
        'servizi' => ['partner.create_service.servizi_title', 'partner.create_service.servizi_subtitle'],
        'smartbox' => ['partner.create_service.smartbox_title', 'partner.create_service.smartbox_subtitle'],
    ];
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
                <flux:radio.group wire:model="service" variant="cards" class="mt-6 flex-col [&_[data-flux-radio-cards]]:flex-row-reverse [&_[data-flux-radio-cards]]:justify-end [&_[data-flux-heading]]:!text-[15px] [&_[data-flux-heading]]:!font-semibold [&_[data-flux-heading]]:!text-[#1E2E33] [&_[data-flux-subheading]]:!text-sm [&_[data-flux-subheading]]:!text-[#627277]">
                    @foreach ($services as $key => [$titleKey, $subtitleKey])
                        <flux:radio value="{{ $key }}" wire:key="svc-{{ $key }}" :label="__($titleKey)" :description="__($subtitleKey)" />
                    @endforeach
                </flux:radio.group>

                @error('service')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

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
