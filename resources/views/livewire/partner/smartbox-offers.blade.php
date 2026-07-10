{{-- Dashboard B2B - smartbox - alloggio - cosa troverai (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $amenities = [
        'camera_da_letto' => 'partner.smartbox_offers.amenity_bedroom',
        'bagno' => 'partner.smartbox_offers.amenity_bathroom',
        'cucina' => 'partner.smartbox_offers.amenity_kitchen',
        'balcone' => 'partner.smartbox_offers.amenity_balcony',
        'terrazzo' => 'partner.smartbox_offers.amenity_terrace',
    ];
    $additional = [
        'piscina' => 'partner.smartbox_offers.add_pool',
        'spa' => 'partner.smartbox_offers.add_spa',
        'campo_da_tennis' => 'partner.smartbox_offers.add_tennis',
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

                {{-- Testata: titolo + "Step 7 di 12" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.smartbox_offers.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.smartbox_offers.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.smartbox_offers.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.smartbox_offers.helper') }}</p>

                <form wire:submit="next" class="mt-6 {{ $checkboxWrap }}">
                    {{-- Sezione 1: Cosa troverai (alloggio) --}}
                    <div>
                        @foreach ($amenities as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="amenity-{{ $key }}">
                                <flux:checkbox wire:model="amenities" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Sezione 2: Servizi aggiuntivi presenti --}}
                    <h2 class="mt-6 text-lg font-medium text-[#0D171A]">{{ __('partner.smartbox_offers.additional_heading') }}</h2>
                    <div class="mt-2">
                        @foreach ($additional as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="add-{{ $key }}">
                                <flux:checkbox wire:model="additional" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Azioni: Indietro (a cibo) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.smartbox.meals') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.smartbox_offers.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.smartbox_offers.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
