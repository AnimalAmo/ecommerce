{{-- Animal Holiday – Dettaglio struttura (XD: "Animal Holiday – Dettaglio struttura") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1m. Hero mobile (XD app "Dettaglio struttura"): foto 250px senza scrim,
               freccia indietro in un cerchio traslucido e cuore preferiti in overlay.
               Titolo, valutazione e località stanno sotto la foto, non sopra. --}}
        <section class="relative h-[250px] w-full overflow-hidden lg:hidden">
            <img src="{{ $structure->heroImageUrl() }}" alt="{{ $structure->name }}" class="absolute inset-0 h-full w-full object-cover">
            <flux:button href="{{ route('holiday.region', $regionSlug) }}" square aria-label="{{ __('holiday.back') }}" class="!absolute !left-4 !top-4 !h-[26px] !w-[26px] !rounded-full !border-0 !bg-[#F2F2F2]/30 !text-white !shadow-none">
                <flux:icon.arrow-back class="h-3 w-3" />
            </flux:button>
            @include('partials.favorite-heart', ['type' => 'structure', 'id' => $structure->id, 'active' => $isFav, 'classes' => '!absolute !right-4 !top-4'])
        </section>

        <div class="{{ $px }} lg:hidden">
            <p class="mt-3 flex items-center gap-2 text-[13px] font-semibold text-[#555555]">
                <flux:icon.star-fill class="h-4 w-[17px] shrink-0" />
                @if ($structure->rating !== null)
                    {{ __('holiday.rating_with_reviews', ['rating' => \App\Support\Format::rating($structure->rating), 'count' => $reviewsCount]) }}
                @else
                    {{ __('holiday.new') }}
                @endif
            </p>
        </div>
        <hr class="mt-3 border-[#E9E9E9] lg:hidden">
        <div class="{{ $px }} lg:hidden">
            <h1 class="mt-4 text-xl font-bold text-black">{{ $structure->name }}</h1>
            <p class="mt-2 flex items-center gap-2 text-[13px] font-semibold text-[#68CDEB]">
                <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                {{ $structure->location }}
            </p>
        </div>

        {{-- 1. Hero foto full-bleed: scrim a sinistra, titolo, azioni, CTA galleria --}}
        <section class="relative h-[524px] w-full overflow-hidden max-lg:hidden">
            <img src="{{ $structure->heroImageUrl() }}" alt="{{ $structure->name }}" class="absolute inset-0 h-full w-full object-cover object-[center_35%]">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('holiday.region', $regionSlug) }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        {{ __('holiday.back') }}
                    </a>
                    <h1 class="mt-7 text-[25px] font-bold leading-[30px] text-brand-yellow">{{ $structure->name }}</h1>
                    <p class="mt-2.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                        {{ $structure->location }}
                    </p>
                    <p class="mt-1.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.star class="h-[15px] w-4 shrink-0" />
                        {{-- Struttura partner appena pubblicata: nessuna recensione, stato "Nuovo" --}}
                        @if ($structure->rating !== null)
                            {{ __('format.stars', ['rating' => \App\Support\Format::rating($structure->rating)]) }}
                        @else
                            {{ __('holiday.new') }}
                        @endif
                    </p>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="{{ __('holiday.share') }}" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    @include('partials.favorite-heart', ['type' => 'structure', 'id' => $structure->id, 'active' => $isFav])
                </div>

                {{-- CTA galleria (TODO: galleria foto struttura) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    {{ __('holiday.view_all_photos') }}
                </flux:button>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10 max-lg:pb-10 max-lg:pt-5">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-[100px]">

                {{-- Colonna sinistra: contenuti struttura --}}
                <div class="min-w-0 max-w-[1048px] flex-1">

                    {{-- 2a. Descrizione breve (su mobile il testo segue la località, senza titolo di sezione; nascosta senza copy) --}}
                    @if (filled($structure->description))
                        <section>
                            <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:hidden">{{ __('holiday.short_description') }}</h2>
                            <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B] max-lg:mt-0">{{ $structure->description }}</p>
                        </section>
                    @endif

                    {{-- 2b. Informazioni generali --}}
                    {{-- first:mt-0: con la descrizione nascosta questa è la prima sezione, il mt-10 raddoppierebbe il padding della colonna --}}
                    <section class="mt-10 first:mt-0">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.general_info') }}</h2>
                        @include('partials.general-info', ['rows' => $structure->general_info])
                    </section>

                    {{-- 3. Cosa troverai (assente per le strutture partner: nessuna fonte wizard, v2) --}}
                    @if (filled($structure->features))
                        <section class="mt-10">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.what_you_find') }}</h2>
                            @include('partials.feature-cards', ['features' => $structure->features])
                        </section>
                    @endif

                    {{-- 4. Servizi Hotel / Servizi Animali (mobile: due liste impilate senza cornice) --}}
                    <section class="mt-8 flex flex-wrap gap-3 max-lg:flex-col max-lg:gap-7">
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4 max-lg:min-h-0 max-lg:w-full max-lg:rounded-none max-lg:border-0 max-lg:p-0">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.hotel_services') }}</h2>
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
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4 max-lg:min-h-0 max-lg:w-full max-lg:rounded-none max-lg:border-0 max-lg:p-0 lg:px-[22px]">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.animal_services') }}</h2>
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

                    {{-- 5. Dove siamo (Google Maps con la chiave configurata, screenshot XD come fallback; senza entrambi la sezione sparisce) --}}
                    @if ($structure->hasMap())
                        <section class="mt-8">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.where_we_are') }}</h2>
                            <div class="relative mt-5 overflow-hidden rounded-[4px]">
                                <x-google-map :query="$structure->mapQuery()" :fallback="$structure->mapFallbackUrl()" :alt="__('holiday.map_alt', ['name' => $structure->name])" class="h-[389px] max-lg:h-[200px]" />
                                <span class="pointer-events-none absolute left-1/2 top-[269px] max-lg:top-[130px] inline-flex h-[38px] -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full bg-brand-yellow px-[18px] text-[13px] font-semibold text-black">
                                    <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                                    {{ $structure->name }}
                                </span>
                            </div>
                        </section>
                    @endif

                    {{-- 6. Domande frequenti (accordion; prima riga aperta come da XD; nascosta senza FAQ) --}}
                    @if ($faqs->isNotEmpty())
                    <section class="mt-14 max-lg:mt-10" x-data="{ open: 0 }">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.faq') }}</h2>
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
                    @endif

                    {{-- 7. Recensioni dei clienti --}}
                    <section class="mt-14 max-lg:mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black max-lg:text-lg">{{ __('holiday.reviews') }}</h2>
                        @if ($structure->rating !== null)
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
                        @else
                            {{-- Struttura partner senza recensioni: stato "Nuovo" al posto delle stelle --}}
                            <p class="mt-2 text-lg font-semibold leading-[22px] text-brand-cyan">{{ __('holiday.new') }}</p>
                        @endif
                        <p class="mt-1.5 flex items-baseline gap-3">
                            <span class="text-lg font-semibold text-black">{{ $reviewsCount }}</span>
                            <span class="text-[15px] text-black">{{ __('holiday.reviews_count_label') }}</span>
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
                                    @if (filled($review->body))
                                        <p class="mt-0.5 max-w-[1032px] text-[15px] leading-[22px] text-[#0D171A]">{{ $review->body }}</p>
                                    @endif
                                    {{-- Mobile (XD app): avatar 32px e "Recensito da <nome>" su una riga sola --}}
                                    <div class="mt-7 flex items-center gap-3 max-lg:mt-5">
                                        <span class="flex h-[46px] w-[46px] shrink-0 items-center justify-center rounded-full text-lg text-white max-lg:h-8 max-lg:w-8 max-lg:text-[15px]" style="background-color: {{ $review->avatar_color }}">{{ $review->author_initials }}</span>
                                        <span class="text-[13px] leading-[22px]">
                                            <span class="block text-[#627277] max-lg:inline max-lg:text-[#0D171A]">{{ __('holiday.reviewed_by') }}</span>
                                            <span class="block text-[#0D171A] max-lg:inline">{{ $review->author_name }}</span>
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($reviewsCount > count($reviews))
                            <flux:button wire:click="loadMoreReviews" class="mt-6 inline-flex h-10 items-center !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none max-lg:!mx-auto max-lg:!flex max-lg:!h-[39px] max-lg:!text-sm">{{ __('holiday.load_more_reviews') }}</flux:button>
                        @endif
                    </section>
                </div>

                {{-- 2c. Card prenotazione (sticky su desktop).
                       Su mobile l'XD app non la prevede: la scheda si prenota dalla barra CTA in basso,
                       con le date/ospiti/animali di default della ricerca. --}}
                <aside class="w-full max-w-[453px] shrink-0 max-lg:hidden lg:sticky lg:top-24 lg:w-[453px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white p-[22px]">
                        <p class="text-[28px] font-light text-[#2B2B2B]">{{ __('format.per_night', ['price' => \App\Support\Format::money($structure->price_cents)]) }}</p>

                        <div class="mt-[18px] rounded-[4px] border border-[#DEDEDE]">
                            {{-- Check-in / Check-out: apre il calendario range condiviso (accordion nello stile del pop-up carrello) --}}
                            <div class="border-b border-[#DEDEDE]">
                                <flux:button variant="ghost" wire:click="toggleField('date')" class="!h-[67px] !w-full !rounded-none !p-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-stretch">
                                    <span class="flex flex-1 flex-col justify-center gap-[7px] pl-[15px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('holiday.checkin') }}</span>
                                        <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckIn }}</span>
                                    </span>
                                    <span class="w-px self-stretch bg-[#DEDEDE]" aria-hidden="true"></span>
                                    <span class="flex flex-1 flex-col justify-center gap-[7px] pl-[15px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('holiday.checkout') }}</span>
                                        <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckOut ?? '—' }}</span>
                                    </span>
                                </flux:button>

                                @if ($expandedField === 'date')
                                    {{-- Calendario inline condiviso (giorni chiusi della struttura + passati disabilitati) --}}
                                    <div class="px-[10px] pb-4">
                                        @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                                    </div>
                                @endif
                            </div>
                            {{-- Ospiti: apre gli stepper condivisi --}}
                            <div class="border-b border-[#DEDEDE]">
                                <flux:button variant="ghost" wire:click="toggleField('ospiti')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                    <span class="flex flex-col gap-[7px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('holiday.guests') }}</span>
                                        <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ \App\Support\Format::guests($editGuests) }}</span>
                                    </span>
                                    <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black {{ $expandedField === 'ospiti' ? 'rotate-180' : '' }}" />
                                </flux:button>

                                @if ($expandedField === 'ospiti')
                                    <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                                    <div class="px-6 pb-3 pt-[10px]">
                                        @include('partials.booking.guest-steppers', ['guests' => $editGuests, 'guestsAtMax' => $guestsAtMax])
                                    </div>
                                @endif
                            </div>
                            {{-- Animali: apre lo stepper condiviso per specie --}}
                            <div>
                                <flux:button variant="ghost" wire:click="toggleField('animali')" class="!h-[67px] !w-full !rounded-none !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                    <span class="flex flex-col gap-[7px]">
                                        <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('holiday.animals') }}</span>
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

                        <flux:button wire:click="addToCart" class="mt-[26px] !flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">{{ __('holiday.add_to_cart') }}</flux:button>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center justify-between text-[17px] text-[#2B2B2B]">
                                <span>{{ __('format.for_nights', ['price' => \App\Support\Format::money($structure->price_cents), 'count' => $nights]) }}</span>
                                <span>{{ \App\Support\Format::money($nightsCents) }}</span>
                            </div>
                            {{-- Supplemento animali per notte: riga mostrata solo se il seed lo valorizza --}}
                            @if ($animalSupplementCents > 0)
                                <div class="flex items-center justify-between text-[17px] text-[#2B2B2B]">
                                    <span>{{ __('holiday.animal_supplement') }}</span>
                                    <span>{{ \App\Support\Format::money($animalSupplementCents) }}</span>
                                </div>
                            @endif
                        </div>
                        <hr class="mt-5 border-[#DEDEDE]">
                        <div class="mt-4 flex items-center justify-between text-[17px] font-bold text-[#2B2B2B]">
                            <span>{{ __('holiday.total') }}</span>
                            <span>{{ \App\Support\Format::money($totalCents) }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @include('partials.site-footer', ['hideMobileTabbar' => true])

    {{-- Barra fissa mobile (XD app "Dettaglio struttura"): sulla scheda la tabbar lascia il posto alla CTA carrello --}}
    <div class="fixed inset-x-0 bottom-0 z-40 flex h-20 items-center justify-center border-t border-gray-150 bg-white lg:hidden">
        <flux:button wire:click="addToCart" class="!h-[39px] !w-[189px] !rounded-full !border-0 !bg-brand-cyan !text-sm !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">{{ __('holiday.add_to_cart') }}</flux:button>
    </div>
    {{-- Spaziatore: evita che la barra CTA copra il fondo pagina --}}
    <div class="h-20 lg:hidden"></div>

    {{-- Pop-up "Aggiunto al carrello" (XD: "Pop-up aggiunta al carrello") — card ancorata in alto a destra sotto l'header --}}
    @if ($cartPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="{{ __('holiday.added_to_cart') }}" x-data @keydown.escape.window="$wire.closeCartPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeCartPopup" aria-hidden="true"></div>

            {{-- Sweet alert mobile (XD app "Sweet alert - chiudi"): card verde centrata sullo schermo,
                 senza CTA — si chiude con la X e resta sulla scheda. --}}
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-4 lg:hidden">
                <div class="pointer-events-auto relative w-full max-w-[346px] rounded-[4px] border border-[#43BA6C] bg-[#EAFFF1] p-4">
                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="{{ __('holiday.close') }}" class="!absolute !right-1 !top-1 !text-[#0D171A] hover:!bg-transparent">
                        <flux:icon.close class="h-4 w-4" />
                    </flux:button>

                    <p class="flex items-center justify-center gap-2.5 pt-4 text-lg font-bold text-[#43BA6C]">
                        <flux:icon.check-circle class="h-[26px] w-[26px] shrink-0" />
                        {{ __('holiday.added_to_cart') }}
                    </p>

                    <div class="mt-6 flex items-start gap-3">
                        <img src="{{ $structure->heroImageUrl() }}" alt="{{ $structure->name }}" class="h-[67px] w-[100px] shrink-0 rounded-[4px] object-cover">
                        <ul class="space-y-[9px] text-[13px] text-[#0D171A]">
                            <li>{{ $editCheckIn }} - {{ $editCheckOut ?? $editCheckIn }}</li>
                            <li>{{ \App\Support\Format::guests($editGuests) }}</li>
                            <li>{{ \App\Support\Format::animals($editAnimals) }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="{{ $px }} pointer-events-none relative max-lg:hidden">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">{{ __('holiday.added_to_cart') }}</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="{{ __('holiday.close') }}" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ $structure->heroImageUrl() }}" alt="{{ $structure->name }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $structure->name }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    {{ $editCheckIn }} - {{ $editCheckOut ?? $editCheckIn }}
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <span class="flex w-[15px] shrink-0 justify-center" aria-hidden="true">
                                        <flux:icon.profile class="h-[13px] w-[13px]" />
                                        <flux:icon.profile class="-ml-[7px] h-[13px] w-[13px]" />
                                    </span>
                                    {{ \App\Support\Format::guests($editGuests) }}
                                </li>
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.animal class="h-[15px] w-[15px] shrink-0" />
                                    {{ \App\Support\Format::animals($editAnimals) }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <flux:button href="{{ route('carrello') }}" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">{{ __('holiday.go_to_cart') }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
