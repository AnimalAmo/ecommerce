{{-- Animal Holiday – Regione (XD: "Animal Holiday – Regione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Padding verticale ridotto su mobile (XD app: titolo a 44px dall'header) --}}
        <div class="{{ $px }} pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
            <h1 class="text-4xl font-bold text-black max-lg:hidden">{{ __('catalog.region_title', ['region' => $regionName]) }}</h1>
            {{-- Titolo mobile (XD app "Cerca - risultati"): sostituisce il titolo desktop --}}
            <h1 class="text-xl font-medium text-[#0D171A] lg:hidden">{{ __('catalog.results_title') }}</h1>

            {{-- Barra ricerca mobile (XD app, simbolo "Box ricerca"): pill 56px alta, raggio 28,
                 bordo #E2EAEB e nessuna ombra; destinazione sulla prima riga, riepilogo
                 quando/persone/animali sulla seconda. A lato il bottone Filtri (cerchio 40 #F2F2F2). --}}
            <div class="mt-5 flex items-center gap-4 lg:hidden">
                <form wire:submit="search" class="flex h-14 min-w-0 flex-1 items-center gap-3 rounded-[28px] border border-[#E2EAEB] bg-white pl-3 pr-4">
                    <flux:button type="submit" variant="ghost" square aria-label="{{ __('holiday.search_cta') }}" class="!h-6 !w-6 shrink-0 !p-0 !text-brand-cyan hover:!bg-transparent">
                        <flux:icon.search class="h-6 w-6" />
                    </flux:button>
                    <span class="flex min-w-0 flex-1 flex-col justify-center">
                        <flux:label class="sr-only" for="mobile-where">{{ __('holiday.search_where') }}</flux:label>
                        <flux:input id="mobile-where" wire:model="where" type="text" placeholder="{{ __('holiday.search_where') }}" class="!h-5 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-5 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-[15px] [&_input]:!font-medium [&_input]:!leading-5 [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:!text-[#959595]" />
                        <span class="mt-0.5 flex items-center gap-2.5 text-[11px] font-light text-[#555555]">
                            <span class="truncate">{{ $when !== '' ? $when : __('holiday.search_when') }}</span>
                            <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                            <span class="truncate">{{ $guests !== '' ? $guests : __('holiday.search_guests') }}</span>
                            <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                            <span class="truncate">{{ $animals !== '' ? $animals : __('holiday.search_animals') }}</span>
                        </span>
                    </span>
                </form>
                <flux:modal.trigger name="mobile-filters">
                    <flux:button square aria-label="{{ __('holiday.filter_your_search') }}" class="!h-10 !w-10 shrink-0 !rounded-full !border-0 !bg-[#F2F2F2] !text-[#0D171A] !shadow-none">
                        <flux:icon.filter-sliders class="h-6 w-6" />
                    </flux:button>
                </flux:modal.trigger>
            </div>

            {{-- Barra ricerca desktop (simbolo condiviso, pre-compilata con la regione) --}}
            <div class="flex items-center gap-3 max-lg:hidden">
            <form wire:submit="search" class="mt-10 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A]">
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('holiday.search_where') }}</flux:label>
                    <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                    {{-- Valore pre-compilato: da spec Nunito SemiBold 18px #000000 (override della tipografia condivisa della pill) --}}
                    <flux:input wire:model="where" type="text" placeholder="{{ __('holiday.search_where') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-lg [&_input]:!font-semibold [&_input]:!text-black [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('holiday.search_when') }}</flux:label>
                    <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="when" type="text" placeholder="{{ __('holiday.search_when') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('holiday.search_guests') }}</flux:label>
                    <flux:icon.team class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="guests" type="text" placeholder="{{ __('holiday.search_guests') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('holiday.search_animals') }}</flux:label>
                    <flux:icon.animal class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="animals" type="text" placeholder="{{ __('holiday.search_animals') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <flux:button type="submit" square aria-label="{{ __('holiday.search_cta') }}" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                    <flux:icon.search class="h-5 w-5" />
                </flux:button>
            </form>
            </div>

            @include('partials.catalog.filter-chips', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX])

            {{-- Filtri desktop (XD: due pill dropdown "Componente 20"), collegati agli stessi
                 stati del modal mobile: Tipologia accende le cinque famiglie del catalogo
                 (default hotel+servizi), Prezzo filtra sugli input min/max condivisi. --}}
            @php
                $pill = '!h-[30px] !gap-2 !rounded-full !border !bg-white !px-3.5 !text-sm !font-normal !shadow-none';
                $pillOff = '!border-[#C8C8C8] !text-[#555555]';
                $pillOn = '!border-brand-cyan !text-brand-cyan';
                $typesMoved = array_values(array_intersect($this::FILTER_TYPES, $activeTypes)) !== ['hotel', 'servizi'];
                $priceMoved = $priceMin !== $this::PRICE_MIN || $priceMax !== $this::PRICE_MAX;
            @endphp
            <p class="mt-10 text-lg font-semibold leading-6 text-black max-lg:hidden">{{ __('holiday.filter_your_search') }}</p>
            <div class="mt-[17px] flex items-center gap-[11px] max-lg:hidden">
                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ $typesMoved ? $pillOn : $pillOff }}">
                        {{ __('holiday.filter_type') }}
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.checkbox.group wire:model.live="activeTypes">
                            @foreach ($this::FILTER_TYPES as $type)
                                <flux:menu.checkbox wire:key="pill-type-{{ $type }}" value="{{ $type }}">{{ __('catalog.filter_types.'.$type) }}</flux:menu.checkbox>
                            @endforeach
                        </flux:menu.checkbox.group>
                    </flux:menu>
                </flux:dropdown>
                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ $priceMoved ? $pillOn : $pillOff }}">
                        {{ __('holiday.filter_price') }}@if ($priceMoved) ({{ $priceMin }}–{{ $priceMax }} €)@endif
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
                 compare l'avviso e, sotto, le card proposte come alternativa. --}}
            @if ($empty)
                @if ($filtersCanHelp)
                    <div class="mt-6 border-y border-[#E9E9E9] py-6 text-center">
                        <p class="flex items-center justify-center gap-2 text-[15px] font-semibold text-[#EA2E68]">
                            <flux:icon.exclamation-circle class="h-5 w-5 shrink-0" />
                            {{ __('catalog.no_results_title') }}
                        </p>
                        <p class="mt-2 text-[15px] text-[#555555]">{{ __('catalog.no_results_hint') }}</p>
                    </div>
                @else
                    {{-- Nessun filtro del visitatore e niente da mostrare cambiandoli: qui non
                         c'è ancora niente pubblicato. Niente rosso d'errore e niente "prova a
                         modificare i filtri", una riga onesta e due strade verso il contenuto
                         che esiste davvero. --}}
                    <div class="mt-6 rounded-[4px] border border-[#E9E9E9] bg-gray-100 px-6 py-6">
                        <p class="text-lg font-semibold text-ink">{{ __('holiday.empty_catalogue_region_title', ['region' => $regionName]) }}</p>
                        <p class="mt-1.5 text-[15px] leading-[22px] text-[#555555]">{{ __('holiday.empty_catalogue_region_body') }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <flux:button href="{{ route('work-with-us') }}" class="!h-10 !rounded-full !border-0 !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink !shadow-none">{{ __('holiday.empty_catalogue_partner_cta') }}</flux:button>
                            <flux:button href="{{ route('news') }}" variant="ghost" class="!h-10 !rounded-full !px-6 !text-sm !font-bold !text-ink">{{ __('holiday.empty_catalogue_news_cta') }}</flux:button>
                        </div>
                    </div>
                @endif

                {{-- Il titolo "Risultati simili" solo se card simili ce ne sono davvero --}}
                @if ($similar->isNotEmpty())
                    <p class="mt-6 text-[15px] font-semibold text-[#0D171A]">{{ __('catalog.similar_results_title') }}</p>
                @endif
            @endif

            @php $cards = $empty ? $similar : $results; @endphp

            {{-- Griglia risultati (XD: simbolo "Box hotel", 4 colonne × 3 righe).
                 Senza nemmeno una card la griglia sarebbe un guscio vuoto: meglio non disegnarla. --}}
            @if ($cards->isNotEmpty() || $events->isNotEmpty() || $boxes->isNotEmpty())
            <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-4 max-lg:mt-5 max-lg:grid-cols-1 max-lg:gap-y-6">
                @foreach ($cards as $result)
                    <article wire:key="res-{{ $result->id }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ $result->imageUrl() }}" alt="{{ $result->name }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105">
                            {{-- Badge verbatim XD ('Hotel'/'Servizi'), colore dalla tassonomia ProductType --}}
                            <span class="absolute left-[10px] top-[15px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $result->type->color() }}">{{ $result->type === \App\Enums\ProductType::Structure ? __('catalog.badge_hotel') : __('catalog.badge_services') }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-[9px] pb-[18px]">
                            <p class="flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.pin class="h-[14px] w-4 shrink-0" />
                                {{ $result->location }}
                            </p>
                            <p class="mt-1.5 flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                <flux:icon.star class="h-[15px] w-4 shrink-0" />
                                {{-- Senza recensioni non c'è media: stato "Nuovo" invece di un voto inventato --}}
                                {{ $result->rating !== null && $result->reviews_count > 0 ? \App\Support\Format::rating($result->rating) : __('holiday.new') }}
                            </p>
                            <h3 class="mt-2.5 text-[20px] font-semibold leading-[25px] text-black">{{ $result->name }}</h3>
                            <p class="mt-auto pt-4 text-right text-[15px] font-normal text-[#627277]">{{ __('holiday.from_price_label') }} <span class="whitespace-nowrap font-semibold tracking-[0.025em] text-[#0D171A]">{{ \App\Support\Format::money($result->price_from_cents) }}</span></p>
                        </div>
                        {{-- Link alla scheda: servizi → "Animal Holiday – Dettaglio servizio", hotel → "Animal Holiday – Dettaglio struttura" --}}
                        <a href="{{ $result->type === \App\Enums\ProductType::Service ? route('holiday.service', ['region' => $regionSlug, 'service' => $result->slug]) : route('holiday.structure', ['region' => $regionSlug, 'structure' => $result->slug]) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $result->name }}"></a>
                        {{-- Hotel e servizi sono righe Structure: alias morph 'structure' --}}
                        @include('partials.favorite-heart', ['type' => 'structure', 'id' => $result->id, 'active' => $this->isFavorite('structure', $result->id), 'classes' => '!absolute !right-[18px] !top-[18px] !z-[2]'])
                    </article>
                @endforeach

                {{-- Card attività ed eventi (XD app "Cerca - risultati - click 'filtri' – 1"):
                     tag e riga durata/orario nel colore della tipologia, CTA Acquista/Partecipa e prezzo a persona. --}}
                @foreach ($events as $event)
                    <article wire:key="ev-{{ $event->id }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative overflow-hidden rounded-t-[3px]">
                            <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}" class="aspect-[354/246] w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-[18px] top-[20px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $event->type->color() }}">{{ $event->type->label() }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pb-[22px]">
                            {{-- Riga orario (eventi) o durata (attività): slot fisso, colore della tipologia --}}
                            <p class="mt-[15px] flex h-[17px] items-center gap-1 text-[13px] font-bold uppercase tracking-[0.025em]" style="color: {{ $event->type->color() }}">
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
                            <h3 class="mt-2.5 text-[20px] font-semibold leading-[25px] text-black">{{ $event->title }}</h3>
                            <div class="mt-auto flex items-center justify-between gap-2 pt-[18px]">
                                @if ($event->hasJoinCta())
                                    {{-- TODO: azione Partecipa (partecipazioni allo step 5) --}}
                                    <flux:button class="relative !z-[2] !h-[39px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !px-4 !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                                        <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                                        {{ __('events.join') }}
                                    </flux:button>
                                @else
                                    <flux:button wire:click="addToCart({{ $event->id }})" class="relative !z-[2] !h-[39px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !px-4 !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                                        <flux:icon.cart class="h-4 w-4 shrink-0" />
                                        {{ __('catalog.buy') }}
                                    </flux:button>
                                @endif
                                @if ($event->price_cents !== null)
                                    <p class="min-w-0 text-right text-[15px] font-normal leading-tight text-[#627277]">{!! __('format.per_person', ['price' => '<span class="whitespace-nowrap font-semibold text-[#0D171A]">'.e(\App\Support\Format::money($event->price_cents)).'</span>']) !!}</p>
                                @elseif ($event->is_free)
                                    <p class="whitespace-nowrap text-right text-[15px] italic text-[#627277]">{{ __('format.free') }}</p>
                                @endif
                            </div>
                        </div>
                        {{-- Le attività multi-giorno aprono la scheda attività, gli eventi la scheda evento --}}
                        <a href="{{ $event->type === \App\Enums\ProductType::Activity ? route('eventi.activity', $event->slug) : route('eventi.detail', $event->slug) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $event->title }}"></a>
                        @include('partials.favorite-heart', ['type' => 'event', 'id' => $event->id, 'active' => $this->isFavorite('event', $event->id), 'classes' => '!absolute !right-[18px] !top-[18px] !z-[2]'])
                    </article>
                @endforeach

                {{-- Card smartbox (XD app "Cerca - risultati - click 'filtri' – 2") --}}
                @foreach ($boxes as $box)
                    <article wire:key="box-{{ $box->id }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ $box->imageUrl() }}" alt="{{ $box->title }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute left-[10px] top-[10px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $box->type->color() }}">{{ $box->type->label() }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-2 pb-[14px]">
                            <h3 class="text-[20px] font-semibold leading-[25px] text-black">{{ $box->title }}</h3>
                            {{-- I cofanetti partner non hanno audience (nessun input wizard, v2) --}}
                            @if ($box->audience)
                                <p class="mt-[14px] flex items-center gap-1.5 text-[13px] font-semibold tracking-[0.025em] text-[#555555]">
                                    <flux:icon.profile class="h-[14px] w-[14px] shrink-0" />
                                    {{ $box->audience }}
                                </p>
                            @endif
                            <p class="mt-auto pt-4 text-right text-[15px] font-normal text-[#627277]">{{ __('smartbox.from_price_label') }} <span class="whitespace-nowrap font-semibold tracking-[0.025em] text-[#0D171A]">{{ \App\Support\Format::money($box->price_from_cents) }}</span></p>
                        </div>
                        <a href="{{ route('smartbox.detail', $box->slug) }}" class="absolute inset-0 z-[1] rounded-[3px]" aria-label="{{ $box->title }}"></a>
                        @include('partials.favorite-heart', ['type' => 'smartbox_package', 'id' => $box->id, 'active' => $this->isFavorite('smartbox_package', $box->id), 'classes' => '!absolute !right-[18px] !top-[18px] !z-[2]'])
                    </article>
                @endforeach
            </div>
            @endif

            {{-- Niente paginazione né "Carica altro": erano finti (range(1, 4) con pagina 1
                 sempre attiva e nessun wire:click, disegnati anche a zero risultati) e un
                 controllo che mente è peggio di nessun controllo. Qui non c'è nulla da
                 impaginare: le tre query prendono l'intero elenco della regione. Quando i
                 volumi lo richiederanno, il paginator vero è quello della pagina Eventi
                 (resources/views/livewire/catalog/events.blade.php) — serve però prima
                 decidere come impaginare insieme strutture, eventi e cofanetti. --}}
        </div>
    </main>

    @include('partials.catalog.filters-modal', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX, 'resultsCount' => $results->count()])

    @include('partials.site-footer')
</div>
