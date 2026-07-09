{{-- Smartbox – Dettaglio (XD: "Smartbox - dettaglio") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, titolo, azioni, CTA galleria --}}
        <section class="relative h-[590px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/'.$box->hero_img.'.jpg') }}" alt="{{ $box->title }}" class="absolute inset-0 h-full w-full object-cover object-[center_68%]">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('smartbox') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        {{ __('smartbox.back') }}
                    </a>
                    <h1 class="mt-7 text-[25px] font-bold leading-[30px] text-brand-yellow">{{ $box->title }}</h1>
                    <p class="mt-2.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.profile class="h-[14px] w-[14px] shrink-0" />
                        {{ $box->audience }} - {{ $box->audience_people }} {{ __('smartbox.people') }}
                    </p>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="{{ __('smartbox.share') }}" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    @include('partials.favorite-heart', ['type' => 'smartbox_package', 'id' => $box->id, 'active' => $isFav])
                </div>

                {{-- CTA galleria (TODO: galleria foto cofanetto) --}}
                <flux:button class="!absolute bottom-[88px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    {{ __('smartbox.view_all_photos') }}
                </flux:button>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-[100px]">

                {{-- Colonna sinistra: contenuti cofanetto --}}
                <div class="min-w-0 max-w-[1032px] flex-1 pt-10">

                    {{-- 2a. Descrizione breve --}}
                    <section>
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.short_description') }}</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $box->description }}</p>
                    </section>

                    {{-- 2b. Informazioni generali --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.general_info') }}</h2>
                        @include('partials.general-info', ['rows' => $box->general_info])
                    </section>

                    {{-- 3. Cosa troverai --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.what_you_find') }}</h2>
                        @include('partials.feature-cards', ['features' => $box->features])
                    </section>

                    {{-- 4. Il tuo weekend --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.your_weekend') }}</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $box->extended_description }}</p>
                    </section>

                    {{-- 5. Servizi Hotel / Servizi Animali --}}
                    <section class="mt-8 flex flex-wrap gap-3">
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.hotel_services') }}</h2>
                            <ul class="mt-2 space-y-[7px]">
                                @foreach ($hotelServices as $service)
                                    <li wire:key="srv-hotel-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] text-[#0D171A]">
                                        @if ($service['included'])
                                            <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                        @else
                                            <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                        @endif
                                        {{ $service['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4 lg:px-[22px]">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">{{ __('smartbox.animal_services') }}</h2>
                            <ul class="mt-2 space-y-[7px]">
                                @foreach ($animalServices as $service)
                                    <li wire:key="srv-animal-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] text-[#0D171A]">
                                        @if ($service['included'])
                                            <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                        @else
                                            <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                        @endif
                                        {{ $service['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                </div>

                {{-- 2c. Card "Box acquista": stesso pt-10 della colonna sinistra così i due top si allineano su desktop. --}}
                <aside class="relative z-10 w-full max-w-[453px] shrink-0 lg:w-[453px] lg:pt-10">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white">
                        {{-- Acquista / Regala (toggle server-driven: ?regalo=1 preseleziona Regala, la riga nasce con is_gift) --}}
                        <div class="flex h-[57px] items-stretch">
                            <flux:button variant="ghost" wire:click="setGift(false)" aria-pressed="{{ $gift ? 'false' : 'true' }}" class="!h-full !flex-1 !rounded-none !px-0 !text-[17px] !font-normal !text-[#2B2B2B] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center [&>span]:gap-2.5">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $gift ? 'border border-[#C8C8C8] bg-white' : 'bg-brand-yellow' }}">
                                    @unless ($gift)
                                        <flux:icon.check class="h-2.5 w-2.5 text-black" />
                                    @endunless
                                </span>
                                {{ __('smartbox.buy') }}
                            </flux:button>
                            <span class="my-[9px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                            <flux:button variant="ghost" wire:click="setGift(true)" aria-pressed="{{ $gift ? 'true' : 'false' }}" class="!h-full !flex-1 !rounded-none !px-0 !text-[17px] !font-normal !text-[#2B2B2B] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center [&>span]:gap-2.5">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $gift ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white' }}">
                                    @if ($gift)
                                        <flux:icon.check class="h-2.5 w-2.5 text-black" />
                                    @endif
                                </span>
                                {{ __('smartbox.gift') }}
                            </flux:button>
                        </div>
                        <div class="mx-[22px] border-b border-[#DEDEDE]" aria-hidden="true"></div>

                        <p class="mt-[22px] text-center text-[28px] font-bold leading-[38px] text-[#2B2B2B]">{{ \App\Support\Format::money($box->price_cents) }}</p>

                        <div class="mx-[22px] mt-4 rounded-[4px] border border-[#DEDEDE]">
                            <div class="grid grid-cols-2 divide-x divide-[#DEDEDE] border-b border-[#DEDEDE]">
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">{{ __('smartbox.valid_from') }}</p>
                                    {{-- Data campione XD: il "valido dal" reale dipenderà dall'acquisto (step 3-4) --}}
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">17/01/2024</p>
                                </div>
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">{{ __('smartbox.valid_for') }}</p>
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">{{ \App\Support\Format::validity($box->validity_months) }}</p>
                                </div>
                            </div>
                            <div class="border-b border-[#DEDEDE] px-[15px] py-3">
                                <p class="text-[17px] font-medium text-[#2B2B2B]">{{ __('smartbox.guests') }}</p>
                                <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">{{ __('smartbox.guests_default') }}</p>
                            </div>
                            {{-- Accordion Animali (stepper condiviso, stile pop-up "Modifica prenotazione" del carrello) --}}
                            <div>
                                <flux:button variant="ghost" wire:click="toggleAnimals" class="!flex !h-auto !w-full items-center !justify-between !gap-4 !rounded-none !px-[15px] !py-3 !text-left hover:!bg-transparent [&>span]:flex [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                    <span>
                                        <span class="block text-[17px] font-medium text-[#2B2B2B]">{{ __('smartbox.animals') }}</span>
                                        <span class="mt-1 block text-[17px] font-light text-[#2B2B2B]">{{ \App\Support\Format::animals($editAnimals) }}</span>
                                    </span>
                                    {{-- XD "Tracciato 619": chevron nero verso il basso (stessa forma di arrow-down) --}}
                                    <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $animalsOpen ? 'rotate-180' : '' }}" />
                                </flux:button>

                                @if ($animalsOpen)
                                    <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                                    <div class="px-6 pb-3 pt-[10px]">
                                        @include('partials.booking.animal-stepper', ['animals' => $editAnimals, 'animalsAtMax' => $animalsAtMax])
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-[26px] px-[22px] pb-6">
                            <flux:button wire:click="addToCart" class="!flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">{{ __('smartbox.add_to_cart') }}</flux:button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Pop-up "Aggiunto al carrello": layout riusato dal dettaglio struttura (estrapolazione ratificata, l'XD non ne definisce uno per le smartbox) --}}
    @if ($cartPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="{{ __('smartbox.added_to_cart') }}" x-data @keydown.escape.window="$wire.closeCartPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeCartPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">{{ __('smartbox.added_to_cart') }}</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="{{ __('smartbox.close') }}" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ asset('img/xd/'.$box->hero_img.'.jpg') }}" alt="{{ $box->title }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $box->title }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    {{ __('format.valid_for', ['validity' => \App\Support\Format::validity($box->validity_months)]) }}
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.animal class="h-[15px] w-[15px] shrink-0" />
                                    {{ \App\Support\Format::animals($editAnimals) }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <flux:button href="{{ route('carrello', $gift ? ['regalo' => 1] : []) }}" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">{{ __('smartbox.go_to_cart') }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
