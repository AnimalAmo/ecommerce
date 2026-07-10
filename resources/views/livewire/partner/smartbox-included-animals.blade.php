{{-- Dashboard B2B - smartbox - cosa è incluso per gli animali (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    // Opzioni identiche ai servizi animali del flusso hotel: riuso label + descrizioni.
    $options = [
        'nessuno' => ['label' => 'partner.hotel_animal_services.opt_none'],
        'omaggio' => ['label' => 'partner.hotel_animal_services.opt_welcome', 'desc' => 'partner.hotel_animal_services.opt_welcome_desc'],
        'pet_sitting' => ['label' => 'partner.hotel_animal_services.opt_petsitting', 'desc' => 'partner.hotel_animal_services.opt_petsitting_desc'],
        'veterinario' => ['label' => 'partner.hotel_animal_services.opt_vet', 'desc' => 'partner.hotel_animal_services.opt_vet_desc'],
        'area_animali' => ['label' => 'partner.hotel_animal_services.opt_area', 'desc' => 'partner.hotel_animal_services.opt_area_desc'],
        'altro' => ['label' => 'partner.hotel_animal_services.opt_other'],
    ];
    // Checkbox tondi cyan + label SemiBold 15 #555 / descrizione 14 #627277.
    $checkboxWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555] [&_[data-flux-subheading]]:!text-sm [&_[data-flux-subheading]]:!text-[#627277]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 9 di 12" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.smartbox_included_animals.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.smartbox_included_animals.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.smartbox_included_animals.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.smartbox_included_animals.helper') }}</p>

                <form wire:submit="next" class="mt-6 {{ $checkboxWrap }}">
                    @foreach ($options as $key => $opt)
                        <div class="border-b border-[#E2EAEB] py-3" wire:key="anim-{{ $key }}">
                            <flux:checkbox wire:model.live="services" value="{{ $key }}" :label="__($opt['label'])" :description="isset($opt['desc']) ? __($opt['desc']) : null" />

                            @if ($key === 'altro' && in_array('altro', $services, true))
                                <flux:textarea wire:model="other" rows="3" maxlength="200" placeholder="{{ __('partner.hotel_animal_services.other_placeholder') }}" class="!mt-3 !rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                            @endif
                        </div>
                    @endforeach

                    {{-- Azioni: Indietro (a cosa è incluso) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.smartbox.included') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.smartbox_included_animals.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.smartbox_included_animals.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
