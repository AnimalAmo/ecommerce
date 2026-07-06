{{-- Animal Holiday – Dettaglio struttura (XD: "Animal Holiday – Dettaglio struttura") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, titolo, azioni, CTA galleria --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/'.$structure->hero_img.'.jpg') }}" alt="{{ $structure->name }}" class="absolute inset-0 h-full w-full object-cover object-[center_35%]">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('holiday.region', $regionSlug) }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        Indietro
                    </a>
                    <h1 class="mt-7 text-[25px] font-bold leading-[30px] text-brand-yellow">{{ $structure->name }}</h1>
                    <p class="mt-2.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                        {{ $structure->location }}
                    </p>
                    <p class="mt-1.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.star class="h-[15px] w-4 shrink-0" />
                        {{ __('format.stars', ['rating' => \App\Support\Format::rating($structure->rating)]) }}
                    </p>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="Condividi" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    {{-- Base bianca come !bg-[#fff] (non !bg-white): nel CSS compilato i valori arbitrari precedono !bg-brand-yellow, così il toggle vince --}}
                    <flux:button square x-data="{ fav: false }" @click="fav = !fav" ::class="fav && '!bg-brand-yellow'" ::aria-pressed="fav" aria-label="Aggiungi ai preferiti" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-[#fff] !text-black !shadow-none">
                        <flux:icon.heart class="h-4 w-4" />
                    </flux:button>
                </div>

                {{-- CTA galleria (TODO: galleria foto struttura) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    Vedere tutte le foto
                </flux:button>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-[100px]">

                {{-- Colonna sinistra: contenuti struttura --}}
                <div class="min-w-0 max-w-[1048px] flex-1">

                    {{-- 2a. Descrizione breve --}}
                    <section>
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Descrizione breve</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $structure->description }}</p>
                    </section>

                    {{-- 2b. Informazioni generali --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Informazioni generali</h2>
                        @include('partials.general-info', ['rows' => $structure->general_info])
                    </section>

                    {{-- 3. Cosa troverai --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Cosa troverai</h2>
                        @include('partials.feature-cards', ['features' => $structure->features])
                    </section>

                    {{-- 4. Servizi Hotel / Servizi Animali --}}
                    <section class="mt-8 flex flex-wrap gap-3">
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">Servizi Hotel</h2>
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
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">Servizi Animali</h2>
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

                    {{-- 5. Dove siamo --}}
                    <section class="mt-8">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Dove siamo</h2>
                        <div class="relative mt-5 overflow-hidden rounded-[4px]">
                            {{-- TODO: screenshot placeholder dall'XD — sostituire con una mappa embedded reale --}}
                            <img src="{{ asset('img/xd/'.$structure->map_img.'.jpg') }}" alt="Mappa della zona — {{ $structure->name }}" class="h-[389px] w-full object-cover">
                            <span class="absolute left-1/2 top-[269px] inline-flex h-[38px] -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-brand-yellow px-[18px] text-[13px] font-semibold text-black">
                                <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                                {{ $structure->name }}
                            </span>
                        </div>
                    </section>

                    {{-- 6. Domande frequenti (accordion; prima riga aperta come da XD) --}}
                    <section class="mt-14" x-data="{ open: 0 }">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Domande frequenti</h2>
                        <div class="mt-4">
                            @foreach ($faqs as $index => $faq)
                                <div wire:key="faq-{{ $faq->id }}" class="border-b border-[#E2EAEB]">
                                    <flux:button variant="ghost" @click="open = open === {{ $index }} ? null : {{ $index }}" ::aria-expanded="(open === {{ $index }}).toString()" class="!flex !h-auto !w-full items-center !justify-between !gap-4 !whitespace-normal !rounded-none !px-0 !py-4 !text-left hover:!bg-transparent">
                                        <span class="text-[15px] font-medium text-[#0D171A]">{{ $faq->question }}</span>
                                        <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-[#1E2E33] transition-transform duration-200" ::class="open === {{ $index }} && 'rotate-180'" />
                                    </flux:button>
                                    <p x-show="open === {{ $index }}" @if ($index !== 0) style="display: none" @endif class="max-w-[1032px] pb-5 text-[15px] leading-[22px] text-[#627277]">{{ $faq->answer }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- 7. Recensioni dei clienti --}}
                    <section class="mt-14">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Recensioni dei clienti</h2>
                        <p class="mt-2 flex items-center gap-2.5">
                            <span class="text-lg font-semibold leading-[22px] text-brand-cyan">{{ \App\Support\Format::rating($structure->rating) }}</span>
                            <span class="flex items-center gap-[3px]">
                                @foreach (range(1, (int) floor($structure->rating)) as $i)
                                    <flux:icon.star-fill wire:key="sum-star-{{ $i }}" class="h-4 w-[17px]" />
                                @endforeach
                                @if ($structure->rating > floor($structure->rating))
                                    <flux:icon.star-mid class="h-4 w-[17px]" />
                                @endif
                            </span>
                        </p>
                        <p class="mt-1.5 flex items-baseline gap-3">
                            <span class="text-lg font-semibold text-black">{{ $reviewsCount }}</span>
                            <span class="text-[15px] text-black">Recensioni</span>
                        </p>

                        <div class="mt-6">
                            @foreach ($reviews as $index => $review)
                                <article wire:key="review-{{ $review->id }}" class="border-b border-[#E2EAEB] pb-6 {{ $loop->first ? '' : 'pt-5' }}">
                                    <p class="text-[13px] leading-[22px] text-[#627277]">{{ \App\Support\Format::dateSentence($review->reviewed_at) }}</p>
                                    <p class="mt-1 flex items-center gap-[3px]">
                                        @foreach (range(1, (int) floor($review->rating)) as $i)
                                            <flux:icon.star-fill wire:key="review-{{ $review->id }}-star-{{ $i }}" class="h-4 w-[17px]" />
                                        @endforeach
                                        @if ($review->rating > floor($review->rating))
                                            <flux:icon.star-mid class="h-4 w-[17px]" />
                                        @endif
                                    </p>
                                    <h3 class="mt-2.5 text-[15px] font-bold leading-[22px] text-[#0D171A]">{{ $review->title }}</h3>
                                    <p class="mt-0.5 max-w-[1032px] text-[15px] leading-[22px] text-[#0D171A]">{{ $review->body }}</p>
                                    <div class="mt-7 flex items-center gap-3">
                                        <span class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg text-white" style="background-color: {{ $review->avatar_color }}">{{ $review->author_initials }}</span>
                                        <span class="text-[13px] leading-[22px]">
                                            <span class="block text-[#627277]">Recensito da</span>
                                            <span class="block text-[#0D171A]">{{ $review->author_name }}</span>
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <flux:button wire:click="loadMoreReviews" class="mt-6 inline-flex h-10 items-center !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none">Carica altre recensioni</flux:button>
                    </section>
                </div>

                {{-- 2c. Card prenotazione (sticky su desktop) --}}
                <aside class="w-full max-w-[453px] shrink-0 lg:sticky lg:top-24 lg:w-[453px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white p-[22px]">
                        <p class="text-[28px] font-light text-[#2B2B2B]">{{ __('format.per_night', ['price' => \App\Support\Format::money($structure->price_cents)]) }}</p>

                        <div class="mt-[18px] rounded-[4px] border border-[#DEDEDE]">
                            <div class="grid grid-cols-2 divide-x divide-[#DEDEDE] border-b border-[#DEDEDE]">
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">Check-in</p>
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">17/12/2023</p>
                                </div>
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">Check-out</p>
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">22/12/2023</p>
                                </div>
                            </div>
                            {{-- TODO: dropdown selezione ospiti --}}
                            <flux:button variant="ghost" class="!flex !h-auto !w-full items-center !justify-between !gap-4 !whitespace-normal !rounded-none border-b border-[#DEDEDE] !px-[15px] !py-3 !text-left hover:!bg-transparent">
                                <span>
                                    <span class="block text-[17px] font-medium text-[#2B2B2B]">Ospiti</span>
                                    <span class="mt-1 block text-[17px] font-light text-[#2B2B2B]">2 adulti</span>
                                </span>
                                <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black" />
                            </flux:button>
                            {{-- TODO: dropdown selezione animali --}}
                            <flux:button variant="ghost" class="!flex !h-auto !w-full items-center !justify-between !gap-4 !whitespace-normal !rounded-none !px-[15px] !py-3 !text-left hover:!bg-transparent">
                                <span>
                                    <span class="block text-[17px] font-medium text-[#2B2B2B]">Animali</span>
                                    <span class="mt-1 block text-[17px] font-light text-[#2B2B2B]">1 cani</span>
                                </span>
                                <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black" />
                            </flux:button>
                        </div>

                        <flux:button wire:click="addToCart" class="mt-[26px] !flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">Aggiungi al carrello</flux:button>

                        <div class="mt-6 flex items-center justify-between text-[17px] text-[#2B2B2B]">
                            <span>{{ __('format.for_nights', ['price' => \App\Support\Format::money($structure->price_cents), 'count' => $nights]) }}</span>
                            <span>{{ \App\Support\Format::money($structure->price_cents * $nights) }}</span>
                        </div>
                        <hr class="mt-5 border-[#DEDEDE]">
                        <div class="mt-4 flex items-center justify-between text-[17px] font-bold text-[#2B2B2B]">
                            <span>Totale</span>
                            <span>{{ \App\Support\Format::money($structure->price_cents * $nights) }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />

    {{-- Pop-up "Aggiunto al carrello" (XD: "Pop-up aggiunta al carrello") — card ancorata in alto a destra sotto l'header --}}
    @if ($cartPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Aggiunto al carrello" x-data @keydown.escape.window="$wire.closeCartPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeCartPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">Aggiunto al carrello</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="Chiudi" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ asset('img/xd/'.$structure->hero_img.'.jpg') }}" alt="{{ $structure->name }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $structure->name }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    17/02/2024 - 22/02/2024
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <span class="flex w-[15px] shrink-0 justify-center" aria-hidden="true">
                                        <flux:icon.profile class="h-[13px] w-[13px]" />
                                        <flux:icon.profile class="-ml-[7px] h-[13px] w-[13px]" />
                                    </span>
                                    2 adulti
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.animal class="h-[15px] w-[15px] shrink-0" />
                                    1 cane
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- TODO: pagina Carrello --}}
                    <flux:button href="#" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">Vai al carrello</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
