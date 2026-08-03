{{-- Attività ed Eventi (XD: "Eventi"; XD app: "Cerca - risultati - click 'filtri' – 1") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px.
             Padding verticale ridotto su mobile (XD app: titolo a 44px dall'header). --}}
        <div class="{{ $px }} pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
            <div class="mx-auto w-full max-w-[1498px]">
                {{-- Titolo resta quello desktop anche su mobile (l'artboard app
                     "Cerca - risultati" è la pagina dei risultati di ricerca, non questa index) --}}
                <h1 class="text-lg font-bold text-[#0D171A] lg:text-4xl lg:text-black">{{ __('events.title') }}</h1>

                {{-- Barra ricerca mobile (XD app, simbolo "Box ricerca"): pill 287x56, raggio 28,
                     bordo #E2EAEB e nessuna ombra; destinazione sulla prima riga, riepilogo
                     quando/ospiti/animali sulla seconda. A lato il bottone Filtri (cerchio 40 #F2F2F2).
                     Il "quando" qui è sola lettura: lo screen app non ha il popover calendario. --}}
                <div class="mt-5 flex items-center gap-4 lg:hidden">
                    <form wire:submit="search" class="flex h-14 min-w-0 flex-1 items-center gap-3 rounded-[28px] border border-[#E2EAEB] bg-white pl-3 pr-4">
                        <flux:button type="submit" variant="ghost" square aria-label="{{ __('events.search_cta') }}" class="!h-6 !w-6 shrink-0 !p-0 !text-brand-cyan hover:!bg-transparent">
                            <flux:icon.search class="h-6 w-6" />
                        </flux:button>
                        <span class="flex min-w-0 flex-1 flex-col justify-center">
                            <flux:label class="sr-only" for="mobile-where">{{ __('events.search_where') }}</flux:label>
                            <flux:input id="mobile-where" wire:model="where" type="text" placeholder="{{ __('events.search_where') }}" class="!h-5 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-5 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-[15px] [&_input]:!font-medium [&_input]:!leading-5 [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:!text-[#959595]" />
                            <span class="mt-0.5 flex items-center gap-2.5 text-[11px] font-light text-[#555555]">
                                <span class="truncate">{{ $editCheckIn ? $editCheckIn.' – '.($editCheckOut ?? '…') : __('events.search_when') }}</span>
                                <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                                <span class="truncate">{{ $guests !== '' ? $guests : __('events.search_guests') }}</span>
                                <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                                <span class="truncate">{{ $animals !== '' ? $animals : __('events.search_animals') }}</span>
                            </span>
                        </span>
                    </form>
                    <flux:modal.trigger name="mobile-filters">
                        <flux:button square aria-label="{{ __('events.filter_your_search') }}" class="!h-10 !w-10 shrink-0 !rounded-full !border-0 !bg-[#F2F2F2] !text-[#0D171A] !shadow-none">
                            <flux:icon.filter-sliders class="h-6 w-6" />
                        </flux:button>
                    </flux:modal.trigger>
                </div>

                {{-- Barra ricerca desktop (simbolo "Ricerca" condiviso con Animal Holiday) --}}
                <form wire:submit="search" class="mt-10 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A] max-lg:hidden">
                    <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:label class="sr-only">{{ __('events.search_where') }}</flux:label>
                        <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <flux:input wire:model="where" type="text" placeholder="{{ __('events.search_where') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:text-gray-400" />
                    </flux:field>
                    <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                    {{-- "Quando": trigger + popover Alpine col calendario range condiviso. Il popover NON si
                         chiude al click su un giorno (il range richiede 2 click); i wire:click del calendario
                         restano funzionanti dentro. Le date raccolte (editCheckIn/editCheckOut) NON filtrano
                         ancora gli eventi: servono per lo step disponibilità (step 6). --}}
                    <div x-data="{ open: false }" class="relative flex flex-1 items-center">
                        <flux:button type="button" variant="ghost" x-on:click="open = ! open" class="!flex !h-auto !w-full !items-center !justify-start !gap-3 !rounded-none !bg-transparent !px-4 !py-2 !shadow-none hover:!bg-transparent [&>span]:!flex [&>span]:!min-w-0 [&>span]:!items-center [&>span]:!gap-3">
                            <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                            <span class="truncate text-sm {{ $editCheckIn ? 'text-ink' : 'text-gray-400' }}">{{ $editCheckIn ? $editCheckIn.' – '.($editCheckOut ?? '…') : __('events.search_when') }}</span>
                        </flux:button>
                        <div x-show="open" x-on:click.outside="open = false" x-transition.opacity style="display: none" class="absolute left-0 top-full z-50 mt-3 w-[320px] rounded-[4px] border border-[#DEDEDE] bg-white p-[10px] text-left shadow-[0px_3px_6px_#00000029]">
                            @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                        </div>
                    </div>
                    <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                    <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:label class="sr-only">{{ __('events.search_guests') }}</flux:label>
                        <flux:icon.team class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <flux:input wire:model="guests" type="text" placeholder="{{ __('events.search_guests') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:text-gray-400" />
                    </flux:field>
                    <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                    <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:label class="sr-only">{{ __('events.search_animals') }}</flux:label>
                        <flux:icon.animal class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <flux:input wire:model="animals" type="text" placeholder="{{ __('events.search_animals') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:text-gray-400" />
                    </flux:field>
                    <flux:button type="submit" square aria-label="{{ __('events.search_cta') }}" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                        <flux:icon.search class="h-5 w-5" />
                    </flux:button>
                </form>

                @include('partials.catalog.filter-chips', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX])

                {{-- Filtri desktop (XD: due pill dropdown "Componente 20"), collegati agli stessi
                     stati del modal mobile: Tipologia accende attività/eventi, Prezzo filtra
                     sugli input min/max condivisi. --}}
                @php
                    $pill = '!h-[30px] !gap-2 !rounded-full !border !bg-white !px-3.5 !text-sm !font-normal !shadow-none';
                    $pillOff = '!border-[#C8C8C8] !text-[#555555]';
                    $pillOn = '!border-brand-cyan !text-brand-cyan';
                    $typesMoved = array_values(array_intersect(['attivita', 'eventi'], $activeTypes)) !== ['attivita', 'eventi'];
                    $priceMoved = $priceMin !== $this::PRICE_MIN || $priceMax !== $this::PRICE_MAX;
                @endphp
                <p class="mt-10 text-lg font-semibold leading-6 text-black max-lg:hidden">{{ __('events.filter_your_search') }}</p>
                <div class="mt-[17px] flex items-center gap-[13px] max-lg:hidden">
                    <flux:dropdown>
                        <flux:button class="{{ $pill }} {{ $typesMoved ? $pillOn : $pillOff }}">
                            {{ __('events.filter_type') }}
                            <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.checkbox.group wire:model.live="activeTypes">
                                @foreach (['attivita', 'eventi'] as $type)
                                    <flux:menu.checkbox wire:key="pill-type-{{ $type }}" value="{{ $type }}">{{ __('catalog.filter_types.'.$type) }}</flux:menu.checkbox>
                                @endforeach
                            </flux:menu.checkbox.group>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:dropdown>
                        <flux:button class="{{ $pill }} {{ $priceMoved ? $pillOn : $pillOff }}">
                            {{ __('events.filter_price') }}@if ($priceMoved) ({{ $priceMin }}–{{ $priceMax }} €)@endif
                            <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                        </flux:button>
                        <flux:popover class="w-64 !p-4">
                            <div class="flex items-end gap-3">
                                <flux:input type="number" min="{{ $this::PRICE_MIN }}" max="{{ $this::PRICE_MAX }}" wire:model.live.debounce.500ms="priceMin" label="{{ __('catalog.filter_price_min') }}" size="sm" />
                                <flux:input type="number" min="{{ $this::PRICE_MIN }}" max="{{ $this::PRICE_MAX }}" wire:model.live.debounce.500ms="priceMax" label="{{ __('catalog.filter_price_max') }}" size="sm" />
                            </div>
                        </flux:popover>
                    </flux:dropdown>
                </div>

                {{-- Nessun risultato (XD app "Nessun risultato"): al posto della griglia vuota
                     compare l'avviso e, sotto, le card proposte come alternativa.
                     Caso tipico qui: nel modal restano attive solo tipologie inerti per questa
                     pagina (hotel/servizi/smartbox), che non hanno un catalogo eventi da filtrare. --}}
                @if ($empty)
                    <div class="mt-6 border-y border-[#E9E9E9] py-6 text-center">
                        <p class="flex items-center justify-center gap-2 text-[15px] font-semibold text-[#EA2E68]">
                            <flux:icon.exclamation-circle class="h-5 w-5 shrink-0" />
                            {{ __('catalog.no_results_title') }}
                        </p>
                        <p class="mt-2 text-[15px] text-[#555555]">{{ __('catalog.no_results_hint') }}</p>
                    </div>
                    <p class="mt-6 text-[15px] font-semibold text-[#0D171A]">{{ __('catalog.similar_results_title') }}</p>
                @endif

                {{-- Griglia eventi (XD: simbolo "Box eventi" 354x415, 4 colonne × 3 righe;
                     XD app: colonna singola a tutta larghezza, gap verticale 16) --}}
                <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-6 max-lg:mt-4 max-lg:grid-cols-1 max-lg:gap-y-4">
                    @foreach ($empty ? $similar : $events as $event)
                        <article wire:key="ev-{{ $event->id }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                            <div class="relative overflow-hidden rounded-t-[3px]">
                                <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}" class="aspect-[354/246] w-full object-cover transition duration-500 group-hover:scale-105 max-lg:aspect-[327/184]">
                                {{-- Badge desktop: sempre 'Evento' come da simbolo XD "Box eventi", anche sulle attività --}}
                                <span class="absolute left-[18px] top-[20px] inline-flex h-[27px] items-center rounded-[3px] bg-brand-purple-soft px-[10px] text-sm font-medium text-white max-lg:hidden">{{ \App\Enums\ProductType::Event->label() }}</span>
                                {{-- Badge mobile (XD app): label e colore dalla tassonomia ProductType (Attività #8E53E6 / Evento #C59FFD) --}}
                                <span class="absolute left-[18px] top-[20px] inline-flex h-[26px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white lg:hidden" style="background-color: {{ $event->type->color() }}">{{ $event->type->label() }}</span>
                            </div>
                            <div class="flex flex-1 flex-col px-[18px] pb-[22px]">
                                {{-- Riga orario (eventi) o durata (attività): slot fisso, colore della tipologia.
                                     Il colore XD app coincide con quello desktop: Attività #8E53E6, Evento #C59FFD = brand-purple-soft. --}}
                                <p class="mt-[15px] flex h-[17px] items-center gap-1 text-[13px] font-bold uppercase tracking-[0.025em] max-lg:text-xs" style="color: {{ $event->type->color() }}">
                                    @if ($event->type === \App\Enums\ProductType::Event && $event->starts_at)
                                        <flux:icon.time class="h-[15px] w-[15px] shrink-0" />
                                        {{ \App\Support\Format::eventTime($event->starts_at) }}
                                    @elseif ($event->type === \App\Enums\ProductType::Activity && $event->duration_days)
                                        <flux:icon.time class="h-[15px] w-[15px] shrink-0" />
                                        {{ __('format.duration_days', ['days' => $event->duration_days]) }}
                                    @endif
                                </p>
                                <p class="mt-[5px] flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                    <flux:icon.pin class="h-[14px] w-4 shrink-0" />
                                    {{ $event->location }}
                                </p>
                                <h3 class="mt-[10px] text-[20px] font-semibold leading-[25px] text-black max-lg:text-[18px] max-lg:leading-[22px] max-lg:text-[#0D171A]">{{ $event->title }}</h3>
                                <div class="mt-auto flex items-center justify-between gap-2 pt-[18px]">
                                    @if (! $event->hasJoinCta())
                                        {{-- Su mobile la pill si adatta al contenuto (XD app: label 14 medium), su desktop resta a larghezza fissa --}}
                                        <flux:button wire:click="addToCart({{ $event->id }})" class="relative !z-[2] !h-[39px] !w-[204px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !text-sm !font-bold !text-[#0D171A] !shadow-none max-lg:!w-auto max-lg:!px-4 max-lg:!font-medium [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                                            <flux:icon.cart class="h-4 w-4 shrink-0" />
                                            {{ __('events.add_to_cart') }}
                                        </flux:button>
                                    @else
                                        {{-- TODO: azione Partecipa --}}
                                        <flux:button class="relative !z-[2] !h-[39px] !w-[136px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !text-sm !font-bold !text-[#0D171A] !shadow-none max-lg:!w-auto max-lg:!px-4 max-lg:!font-medium">
                                            <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                                            {{ __('events.join') }}
                                        </flux:button>
                                    @endif
                                    @if ($event->price_cents !== null)
                                        {{-- Stesso stile prezzo delle card eventi in home (label grigia, importo scuro semibold); min-w-0 così il testo va a capo dentro i padding della card, con "a persona" indivisibile --}}
                                        <p class="min-w-0 text-right text-[15px] font-normal leading-tight text-[#627277]">{!! str_replace('a persona', 'a&nbsp;persona', __('format.per_person', ['price' => '<span class="whitespace-nowrap font-semibold text-[#0D171A]">'.e(\App\Support\Format::money($event->price_cents)).'</span>'])) !!}</p>
                                    @elseif ($event->is_free)
                                        {{-- Gratis come in home: italic regular, stesso grigio della label "a persona" --}}
                                        <p class="whitespace-nowrap text-right text-[15px] italic text-[#627277]">{{ __('format.free') }}</p>
                                    @else
                                        <p class="min-w-0 text-right text-[15px] font-normal leading-tight text-[#627277]">{!! __('format.from_price', ['price' => '<span class="whitespace-nowrap font-semibold text-[#0D171A]">'.e(\App\Support\Format::money(0)).'</span>']) !!}</p>
                                    @endif
                                </div>
                            </div>
                            {{-- Le attività multi-giorno aprono la scheda attività, gli eventi la scheda evento --}}
                            <a href="{{ $event->type === \App\Enums\ProductType::Activity ? route('eventi.activity', $event->slug) : route('eventi.detail', $event->slug) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $event->title }}"></a>
                            @include('partials.favorite-heart', ['type' => 'event', 'id' => $event->id, 'active' => $this->isFavorite('event', $event->id), 'classes' => '!absolute !right-[18px] !top-[18px] !z-[2]'])
                        </article>
                    @endforeach
                </div>

                {{-- Mobile: "Carica altro" al posto della paginazione (XD app: 140x39, r20, #0D171A) --}}
                @if (! $empty && $events->hasMorePages())
                    <div class="mt-8 flex justify-center lg:hidden">
                        <flux:button wire:click="loadMore" class="!h-[39px] !w-[140px] !rounded-full !bg-[#0D171A] !text-sm !font-bold !text-white hover:!bg-black">{{ __('events.load_more') }}</flux:button>
                    </div>
                @endif

                {{-- Paginazione (XD "Raggruppa 744"): pill reali del paginator, stile invariato --}}
                @if (! $empty && $events->hasPages())
                    <nav class="mt-10 flex items-center justify-center gap-3 max-lg:hidden" aria-label="{{ __('events.pagination') }}">
                        @if ($events->onFirstPage())
                            <flux:button variant="ghost" square disabled aria-label="{{ __('events.prev_page') }}" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                                <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                            </flux:button>
                        @else
                            <flux:button variant="ghost" square wire:click="previousPage" aria-label="{{ __('events.prev_page') }}" class="!h-auto !w-auto !p-1 !text-black">
                                <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                            </flux:button>
                        @endif
                        @foreach (range(1, $events->lastPage()) as $page)
                            <flux:button wire:key="page-{{ $page }}" square wire:click="gotoPage({{ $page }})" :aria-current="$page === $events->currentPage() ? 'page' : null" class="!h-8 !w-8 !rounded-full !border-0 !text-base !font-medium !shadow-none {{ $page === $events->currentPage() ? '!bg-black !text-white' : '!bg-white !text-black' }}">{{ $page }}</flux:button>
                        @endforeach
                        @if ($events->hasMorePages())
                            <flux:button variant="ghost" square wire:click="nextPage" aria-label="{{ __('events.next_page') }}" class="!h-auto !w-auto !p-1 !text-black">
                                <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                            </flux:button>
                        @else
                            <flux:button variant="ghost" square disabled aria-label="{{ __('events.next_page') }}" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                                <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                            </flux:button>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    </main>

    @include('partials.catalog.filters-modal', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX, 'resultsCount' => $events->total()])

    @include('partials.site-footer')
</div>
