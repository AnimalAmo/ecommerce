{{-- Animal Holiday – Dettaglio servizio (XD: "Animal Holiday – Dettaglio servizio") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, titolo, azioni, CTA galleria --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/'.$service->hero_img.'.jpg') }}" alt="{{ $service->name }}" class="absolute inset-0 h-full w-full object-cover object-[center_35%]">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('holiday.region', $regionSlug) }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        Indietro
                    </a>
                    <h1 class="mt-7 text-[25px] font-bold leading-[30px] text-brand-yellow">{{ $service->name }}</h1>
                    <p class="mt-2.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                        {{ $service->location }}
                    </p>
                    <p class="mt-1.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.star class="h-[15px] w-4 shrink-0" />
                        {{ __('format.stars', ['rating' => \App\Support\Format::rating($service->rating)]) }}
                    </p>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="Condividi" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    {{-- I servizi sono righe Structure: alias morph 'structure' --}}
                    @include('partials.favorite-heart', ['type' => 'structure', 'id' => $service->id, 'active' => $isFav])
                </div>

                {{-- CTA galleria (TODO: galleria foto servizio) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    Vedere tutte le foto
                </flux:button>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-[100px]">

                {{-- Colonna sinistra: contenuti servizio --}}
                <div class="min-w-0 max-w-[1048px] flex-1">

                    {{-- 2a. Descrizione breve --}}
                    <section>
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Descrizione breve</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $service->description }}</p>
                    </section>

                    {{-- 2b. Informazioni generali --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Informazioni generali</h2>
                        @include('partials.general-info', ['rows' => $service->general_info])
                    </section>

                    {{-- 3. Servizi Animali --}}
                    <section class="mt-8">
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4 lg:px-[22px]">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">Servizi Animali</h2>
                            <ul class="mt-2 space-y-[7px]">
                                {{-- $row e non $service: il nome del model di pagina non va sovrascritto dal loop --}}
                                @foreach ($animalServices as $row)
                                    <li wire:key="srv-animal-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] text-[#0D171A]">
                                        @if ($row['included'])
                                            <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                        @else
                                            <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                        @endif
                                        {{ $row['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>

                    {{-- 4. Dove siamo --}}
                    <section class="mt-8">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Dove siamo</h2>
                        <div class="relative mt-5 overflow-hidden rounded-[4px]">
                            {{-- TODO: screenshot placeholder dall'XD — sostituire con una mappa embedded reale --}}
                            <img src="{{ asset('img/xd/'.$service->map_img.'.jpg') }}" alt="Mappa della zona — {{ $service->name }}" class="h-[389px] w-full object-cover">
                            <span class="absolute left-1/2 top-[269px] inline-flex h-[38px] -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-brand-yellow px-[18px] text-[13px] font-semibold text-black">
                                <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                                {{ $service->name }}
                            </span>
                        </div>
                    </section>

                    {{-- 5. Domande frequenti (accordion; prima riga aperta come da XD) --}}
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

                    {{-- 6. Recensioni dei clienti --}}
                    <section class="mt-14">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Recensioni dei clienti</h2>
                        <p class="mt-2 flex items-center gap-2.5">
                            <span class="text-lg font-semibold leading-[22px] text-brand-cyan">{{ \App\Support\Format::rating($service->rating) }}</span>
                            <span class="flex items-center gap-[3px]">
                                @foreach (range(1, (int) floor($service->rating)) as $i)
                                    <flux:icon.star-fill wire:key="sum-star-{{ $i }}" class="h-4 w-[17px]" />
                                @endforeach
                                @if ($service->rating > floor($service->rating))
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

                        @if ($reviewsCount > count($reviews))
                            <flux:button wire:click="loadMoreReviews" class="mt-6 inline-flex h-10 items-center !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none">Carica altre recensioni</flux:button>
                        @endif
                    </section>
                </div>

                {{-- 2c. Card prenotazione (sticky su desktop) --}}
                <aside class="w-full max-w-[453px] shrink-0 lg:sticky lg:top-24 lg:w-[453px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white p-[22px]">
                        <p class="text-[28px] font-light text-[#2B2B2B]">{{ __('format.per_hour', ['price' => \App\Support\Format::money($service->price_cents)]) }}</p>

                        <div class="mt-[18px] rounded-[4px] border border-[#DEDEDE]">
                            {{-- Giorno: apre il calendario condiviso a giorno singolo (accordion nello stile del pop-up carrello) --}}
                            <div class="border-b border-[#DEDEDE]">
                                <flux:button variant="ghost" wire:click="toggleField('date')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                    <span class="flex flex-col gap-[7px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Giorno</span>
                                        <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckIn }}</span>
                                    </span>
                                    <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $expandedField === 'date' ? 'rotate-180' : '' }}" />
                                </flux:button>

                                @if ($expandedField === 'date')
                                    {{-- Calendario inline condiviso (giorni chiusi del servizio + passati disabilitati) --}}
                                    <div class="px-[10px] pb-4">
                                        @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                                    </div>
                                @endif
                            </div>
                            {{-- Dalle / Alle: entrambe le celle aprono il pannello orari (select 08:00–20:00) --}}
                            <div class="border-b border-[#DEDEDE]">
                                <div class="grid grid-cols-2 divide-x divide-[#DEDEDE]">
                                    <flux:button variant="ghost" wire:click="toggleField('orari')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                        <span class="flex flex-col gap-[7px]">
                                            <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Dalle:</span>
                                            <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editTimeFrom }}</span>
                                        </span>
                                        <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $expandedField === 'orari' ? 'rotate-180' : '' }}" />
                                    </flux:button>
                                    <flux:button variant="ghost" wire:click="toggleField('orari')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                        <span class="flex flex-col gap-[7px]">
                                            <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Alle:</span>
                                            <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editTimeTo }}</span>
                                        </span>
                                        <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $expandedField === 'orari' ? 'rotate-180' : '' }}" />
                                    </flux:button>
                                </div>

                                @if ($expandedField === 'orari')
                                    <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                                    <div class="flex gap-4 px-6 pb-4 pt-[10px]">
                                        <div class="flex-1">
                                            <div class="text-sm leading-none text-[#627277]">Dalle</div>
                                            <flux:select wire:model.live="editTimeFrom" class="mt-2">
                                                @foreach ($bookingHours as $hour)
                                                    <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm leading-none text-[#627277]">Alle</div>
                                            <flux:select wire:model.live="editTimeTo" class="mt-2">
                                                @foreach ($bookingHours as $hour)
                                                    <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            {{-- Animali: apre lo stepper condiviso per specie --}}
                            <div>
                                <flux:button variant="ghost" wire:click="toggleField('animali')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                    <span class="flex flex-col gap-[7px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Animali</span>
                                        <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ \App\Support\Format::animals($editAnimals) }}</span>
                                    </span>
                                    <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $expandedField === 'animali' ? 'rotate-180' : '' }}" />
                                </flux:button>

                                @if ($expandedField === 'animali')
                                    <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                                    <div class="px-6 pb-3 pt-[10px]">
                                        @include('partials.booking.animal-stepper', ['animals' => $editAnimals, 'animalsAtMax' => $animalsAtMax])
                                    </div>
                                @endif
                            </div>
                        </div>

                        <flux:button wire:click="addToCart" class="mt-[26px] !flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">Aggiungi al carrello</flux:button>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between text-[17px] text-[#2B2B2B]">
                                <span>{{ __('format.for_hours', ['price' => \App\Support\Format::money($service->price_cents), 'count' => $hours]) }}</span>
                                <span>{{ \App\Support\Format::money($hoursCents) }}</span>
                            </div>
                            {{-- Supplemento animali per ora: riga mostrata solo se il seed lo valorizza --}}
                            @if ($animalSupplementCents > 0)
                                <div class="flex items-center justify-between text-[17px] text-[#2B2B2B]">
                                    <span>Supplemento animali</span>
                                    <span>{{ \App\Support\Format::money($animalSupplementCents) }}</span>
                                </div>
                            @endif
                        </div>
                        <hr class="mt-5 border-[#DEDEDE]">
                        <div class="mt-4 flex items-center justify-between text-[17px] font-bold text-[#2B2B2B]">
                            <span>Totale</span>
                            <span>{{ \App\Support\Format::money($totalCents) }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Pop-up "Aggiunto al carrello" — stesso pattern del dettaglio struttura, con giorno/orario del servizio --}}
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
                        <img src="{{ asset('img/xd/'.$service->hero_img.'.jpg') }}" alt="{{ $service->name }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $service->name }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    {{ $editCheckIn }}
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    {{-- Icona orologio (variante tintabile di flux:icon.time) --}}
                                    <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="h-[15px] w-[15px] shrink-0" aria-hidden="true">
                                        <g transform="translate(-1.965 -1.965)">
                                            <path d="M9.959,3.375a6.591,6.591,0,1,0,6.6,6.591A6.588,6.588,0,0,0,9.959,3.375Zm.006,11.863a5.272,5.272,0,1,1,5.272-5.272A5.272,5.272,0,0,1,9.966,15.238Z" />
                                            <path d="M17.526,10.688h-.989v3.954L20,16.717l.494-.811-2.966-1.759Z" transform="translate(-7.231 -4.017)" />
                                        </g>
                                    </svg>
                                    {{ $editTimeFrom }} - {{ $editTimeTo }}
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.animal class="h-[15px] w-[15px] shrink-0" />
                                    {{ \App\Support\Format::animals($editAnimals) }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <flux:button href="{{ route('carrello') }}" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">Vai al carrello</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
