{{-- Dashboard B2B – struttura ricettiva - smartbox (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $types = [
        'tutta' => ['label' => 'partner.hotel_smartbox.type_all'],
        'pernottamento' => ['label' => 'partner.hotel_smartbox.type_overnight'],
        'benessere' => ['label' => 'partner.hotel_smartbox.type_wellness', 'desc' => 'partner.hotel_smartbox.type_wellness_desc'],
        'avventura' => ['label' => 'partner.hotel_smartbox.type_adventure', 'desc' => 'partner.hotel_smartbox.type_adventure_desc'],
    ];
    // Controlli tondi cyan + label SemiBold 15 #555 / descrizione 14 #627277.
    $controlWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555] [&_[data-flux-subheading]]:!text-sm [&_[data-flux-subheading]]:!text-[#627277]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x632, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 9 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.hotel_smartbox.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.hotel_smartbox.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.hotel_smartbox.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.hotel_smartbox.helper') }}</p>

                <form wire:submit="next" class="mt-6 {{ $controlWrap }}">
                    {{-- Consenso Si/No (scelta singola) --}}
                    <flux:radio.group wire:model.live="consent" class="!flex !flex-col !gap-0">
                        <div class="border-b border-[#E2EAEB] py-3">
                            <flux:radio value="no" :label="__('partner.hotel_smartbox.no')" />
                        </div>
                        <div class="border-b border-[#E2EAEB] py-3">
                            <flux:radio value="si" :label="__('partner.hotel_smartbox.yes')" />
                        </div>
                    </flux:radio.group>

                    {{-- Tipologie smartbox (solo se "Si"): multi-scelta --}}
                    @if ($consent === 'si')
                        <h2 class="mt-6 text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_smartbox.types_heading') }}</h2>
                        <div class="mt-2">
                            @foreach ($types as $key => $opt)
                                <div class="border-b border-[#E2EAEB] py-3" wire:key="type-{{ $key }}">
                                    <flux:checkbox wire:model="types" value="{{ $key }}" :label="__($opt['label'])" :description="isset($opt['desc']) ? __($opt['desc']) : null" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Azioni: Indietro (a servizi animali) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.structure.hotel.animal-services') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_smartbox.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_smartbox.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
