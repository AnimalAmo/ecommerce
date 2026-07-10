{{-- Dashboard B2B – tipologia struttura (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $types = [
        'hotel' => ['icon' => 'bed', 'label' => 'partner.structure_type.hotel'],
        'bb' => ['icon' => 'coffee', 'label' => 'partner.structure_type.bb'],
        'agriturismo' => ['icon' => 'home', 'label' => 'partner.structure_type.agriturismo'],
    ];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x452, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 1 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.create_service.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.structure_type.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.create_service.section') }}</h2>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.create_service.helper') }}</p>

                {{-- Tipologie struttura: flux radio cards con icona in alto + label, selezione cyan --}}
                <flux:radio.group wire:model="type" variant="cards" class="mt-8 [--color-accent:#6CD1EF] [&_[data-flux-radio-cards]]:justify-center [&_[data-flux-radio-cards]]:py-8 [&_[data-flux-radio-cards]>div]:!flex-col [&_[data-flux-radio-cards]>div]:items-center [&_[data-flux-radio-cards]>div]:gap-3 [&_[data-flux-radio-cards]>div]:text-center [&_[data-flux-radio-cards]_svg]:!size-9 [&_[data-flux-radio-cards]_svg]:!text-[#2B2B2B] [&_[data-flux-heading]]:!text-[15px] [&_[data-flux-heading]]:!font-semibold [&_[data-flux-heading]]:!text-[#1E2E33]">
                    @foreach ($types as $key => $t)
                        <flux:radio value="{{ $key }}" wire:key="type-{{ $key }}" :icon="$t['icon']" :label="__($t['label'])" :indicator="false" />
                    @endforeach
                </flux:radio.group>

                @error('type')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

                {{-- Azioni: Indietro (a crea servizio) + Avanti (pill scuro) --}}
                <div class="mt-8 flex items-center justify-end gap-6">
                    <flux:button href="{{ route('partner.service.create') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.structure_type.back') }}</flux:button>
                    <flux:button wire:click="next" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.structure_type.next') }}</flux:button>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
