{{-- Dashboard B2B - smartbox - cosa è incluso (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    // Opzioni identiche ai servizi struttura del flusso hotel: riuso le label.
    $servizi = [
        'aria_condizionata' => 'partner.hotel_services.svc_ac',
        'riscaldamento' => 'partner.hotel_services.svc_heating',
        'wifi' => 'partner.hotel_services.svc_wifi',
        'ricarica_elettrica' => 'partner.hotel_services.svc_ev',
        'tv' => 'partner.hotel_services.svc_tv',
        'piscina' => 'partner.hotel_services.svc_pool',
        'sauna' => 'partner.hotel_services.svc_sauna',
    ];
    // Checkbox tondi cyan (come "check b2b" XD) + label SemiBold 15 #555, divisore per riga.
    $checkboxWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 8 di 12" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.smartbox_included.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.smartbox_included.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.smartbox_included.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.smartbox_included.helper') }}</p>

                <form wire:submit="next" class="mt-6 {{ $checkboxWrap }}">
                    <div>
                        @foreach ($servizi as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="inc-{{ $key }}">
                                <flux:checkbox wire:model="included" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Azioni: Indietro (a cosa troverai) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.smartbox.offers') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.smartbox_included.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.smartbox_included.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
