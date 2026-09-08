{{-- Smartbox (XD: "Smartbox"; XD app: "Cerca - risultati - click 'filtri' – 2") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Padding verticale ridotto su mobile (XD app: titolo a 44px dall'header) --}}
        <div class="{{ $px }} pt-10 pb-[120px] max-lg:pt-6 max-lg:pb-10">
            {{-- Titolo resta quello desktop anche su mobile (l'artboard app
                 "Cerca - risultati" è la pagina dei risultati di ricerca, non questa index) --}}
            <h1 class="text-lg font-bold text-[#0D171A] lg:text-4xl lg:text-black">{{ __('smartbox.title') }}</h1>
            <p class="mt-2 text-[15px] text-[#555555] lg:mt-3 lg:text-[18px]">{{ __('smartbox.subtitle') }}</p>

            {{-- Barra ricerca mobile (XD app, simbolo "Box ricerca"): pill 56px alta, raggio 28,
                 bordo #E2EAEB e nessuna ombra; termine sulla prima riga, riepilogo
                 quando/ospiti/animali sulla seconda. A lato il bottone Filtri (cerchio 40 #F2F2F2).
                 Quando/ospiti/animali qui sono sola lettura: i cofanetti non li filtrano. --}}
            <div class="mt-5 flex items-center gap-4 lg:hidden">
                <form wire:submit="search" class="flex h-14 min-w-0 flex-1 items-center gap-3 rounded-[28px] border border-[#E2EAEB] bg-white pl-3 pr-4">
                    <flux:button type="submit" variant="ghost" square aria-label="{{ __('smartbox.search_cta') }}" class="!h-6 !w-6 shrink-0 !p-0 !text-brand-cyan hover:!bg-transparent">
                        <flux:icon.search class="h-6 w-6" />
                    </flux:button>
                    <span class="flex min-w-0 flex-1 flex-col justify-center">
                        <flux:label class="sr-only" for="mobile-where">{{ __('smartbox.search_where') }}</flux:label>
                        <flux:input id="mobile-where" wire:model="where" type="text" placeholder="{{ __('smartbox.search_where') }}" class="!h-5 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-5 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-[15px] [&_input]:!font-medium [&_input]:!leading-5 [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:!text-[#959595]" />
                        <span class="mt-0.5 flex items-center gap-2.5 text-[11px] font-light text-[#555555]">
                            <span class="truncate">{{ __('smartbox.search_when') }}</span>
                            <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                            <span class="truncate">{{ $guests !== '' ? $guests : __('smartbox.search_guests') }}</span>
                            <span class="h-3 w-px shrink-0 bg-[#E2EAEB]" aria-hidden="true"></span>
                            <span class="truncate">{{ $animals !== '' ? $animals : __('smartbox.search_animals') }}</span>
                        </span>
                    </span>
                </form>
                <flux:modal.trigger name="mobile-filters">
                    <flux:button square aria-label="{{ __('smartbox.filter_your_search') }}" class="!h-10 !w-10 shrink-0 !rounded-full !border-0 !bg-[#F2F2F2] !text-[#0D171A] !shadow-none">
                        <flux:icon.filter-sliders class="h-6 w-6" />
                    </flux:button>
                </flux:modal.trigger>
            </div>

            @include('partials.catalog.filter-chips', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX])

            {{-- Filtri desktop (XD: quattro pill dropdown "Componente 20"), collegati agli stessi
                 stati del modal mobile: Tipologia e Prezzo filtrano la griglia; Persone raccoglie
                 i gruppi come nel modal (la regola di filtro è in attesa del cliente); Luogo cerca
                 nel titolo, perché i cofanetti non hanno una colonna luogo. --}}
            @php
                $pill = '!h-[30px] !gap-2 !rounded-full !border !bg-white !px-3.5 !text-sm !font-normal !shadow-none';
                $pillOff = '!border-[#C8C8C8] !text-[#555555]';
                $pillOn = '!border-brand-cyan !text-brand-cyan';
                $priceMoved = $priceMin !== $this::PRICE_MIN || $priceMax !== $this::PRICE_MAX;
            @endphp
            <p class="mt-10 text-lg font-semibold leading-6 text-black max-lg:hidden">{{ __('smartbox.filter_your_search') }}</p>
            <div class="mt-[17px] flex flex-wrap items-center gap-[11px] max-lg:hidden">
                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ $smartboxTypes !== [] ? $pillOn : $pillOff }}">
                        {{ __('smartbox.filter_type') }}@if ($smartboxTypes !== []) ({{ count($smartboxTypes) }})@endif
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.checkbox.group wire:model.live="smartboxTypes">
                            @foreach ($this::SMARTBOX_TYPES as $type)
                                <flux:menu.checkbox wire:key="pill-type-{{ $type }}" value="{{ $type }}">{{ __('catalog.smartbox_chips.'.$type) }}</flux:menu.checkbox>
                            @endforeach
                        </flux:menu.checkbox.group>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ $priceMoved ? $pillOn : $pillOff }}">
                        {{ __('smartbox.filter_price') }}@if ($priceMoved) ({{ $priceMin }}–{{ $priceMax }} €)@endif
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:popover class="w-64 !p-4">
                        <div class="flex items-end gap-3">
                            <flux:input type="number" min="{{ $this::PRICE_MIN }}" max="{{ $this::PRICE_MAX }}" wire:model.live.debounce.500ms="priceMin" label="{{ __('catalog.filter_price_min') }}" size="sm" />
                            <flux:input type="number" min="{{ $this::PRICE_MIN }}" max="{{ $this::PRICE_MAX }}" wire:model.live.debounce.500ms="priceMax" label="{{ __('catalog.filter_price_max') }}" size="sm" />
                        </div>
                    </flux:popover>
                </flux:dropdown>

                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ $peopleGroups !== [] ? $pillOn : $pillOff }}">
                        {{ __('smartbox.filter_people') }}@if ($peopleGroups !== []) ({{ count($peopleGroups) }})@endif
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.checkbox.group wire:model.live="peopleGroups">
                            @foreach (['coppia', 'famiglia', 'gruppo'] as $group)
                                <flux:menu.checkbox wire:key="pill-people-{{ $group }}" value="{{ $group }}">{{ __('catalog.people_groups.'.$group) }}</flux:menu.checkbox>
                            @endforeach
                        </flux:menu.checkbox.group>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown>
                    <flux:button class="{{ $pill }} {{ trim($where) !== '' ? $pillOn : $pillOff }}">
                        {{ __('smartbox.filter_place') }}@if (trim($where) !== '') ({{ trim($where) }})@endif
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:popover class="w-72 !p-4">
                        <form wire:submit="search" class="flex items-end gap-2">
                            <flux:input wire:model="where" type="text" placeholder="{{ __('smartbox.search_where') }}" size="sm" class="flex-1" />
                            <flux:button type="submit" size="sm" class="!rounded-full !bg-brand-yellow !px-4 !text-sm !font-bold !text-ink [&>span]:flex [&>span]:items-center">{{ __('smartbox.search_cta') }}</flux:button>
                        </form>
                    </flux:popover>
                </flux:dropdown>
            </div>

            {{-- Griglia vuota, due copy diversi.
                 1) Catalogo ancora vuoto (nessun partner ha pubblicato un cofanetto): non c'è
                    nessun filtro da allargare e nessuna card simile da proporre, quindi niente
                    avviso rosso — una riga onesta e due strade che portano dove il contenuto
                    c'è davvero (Animal Times, Lavora con noi).
                 2) Filtri troppo stretti: resta l'avviso XD app "Nessun risultato" e, sotto, le
                    card proposte come alternativa. Caso tipico qui: nel modal è stata spenta la
                    tipologia Smartbox, oppure la fascia di prezzo esclude tutti i cofanetti. --}}
            @if ($catalogueEmpty)
                <div class="mt-6 border-y border-[#E9E9E9] py-10 text-center">
                    <p class="text-[18px] font-semibold text-[#0D171A]">{{ __('smartbox.empty_catalogue_title') }}</p>
                    <p class="mx-auto mt-2 max-w-[560px] text-[15px] text-[#555555]">{{ __('smartbox.empty_catalogue_body') }}</p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <flux:button :href="route('news')" class="!h-[39px] !rounded-full !border-0 !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink !shadow-none">{{ __('smartbox.empty_catalogue_news_cta') }}</flux:button>
                        <flux:button :href="route('work-with-us')" class="!h-[39px] !rounded-full !border !border-[#C8C8C8] !bg-white !px-6 !text-sm !font-bold !text-[#0D171A] !shadow-none">{{ __('smartbox.empty_catalogue_partner_cta') }}</flux:button>
                    </div>
                </div>
            @elseif ($empty)
                <div class="mt-6 border-y border-[#E9E9E9] py-6 text-center">
                    <p class="flex items-center justify-center gap-2 text-[15px] font-semibold text-[#EA2E68]">
                        <flux:icon.exclamation-circle class="h-5 w-5 shrink-0" />
                        {{ __('catalog.no_results_title') }}
                    </p>
                    <p class="mt-2 text-[15px] text-[#555555]">{{ __('catalog.no_results_hint') }}</p>
                </div>
                {{-- Il titolo solo se card simili ce ne sono: sopra una griglia vuota
                     sarebbe una promessa non mantenuta. --}}
                @if ($similar->isNotEmpty())
                    <p class="mt-6 text-[15px] font-semibold text-[#0D171A]">{{ __('catalog.similar_results_title') }}</p>
                @endif
            @endif

            {{-- Griglia cofanetti (XD: simbolo "Box smartbox" 354x391, 4 colonne × 3 righe;
                 XD app: colonna singola a tutta larghezza, gap verticale 16).
                 Senza card (catalogo vuoto) la griglia non viene disegnata affatto:
                 meglio niente che un contenitore vuoto. --}}
            @php $cards = $empty ? $similar : $boxes; @endphp
            @if ($cards->isNotEmpty())
            <div class="mt-[29px] grid grid-cols-4 gap-x-[27px] gap-y-6 max-lg:mt-4 max-lg:grid-cols-1 max-lg:gap-y-4">
                @foreach ($cards as $box)
                    <article wire:key="box-{{ $box->id }}" class="group relative flex min-h-[391px] flex-col rounded-[3px] border border-[#E9E9E9] bg-white max-lg:min-h-0">
                        <div class="relative m-2 overflow-hidden rounded-t-[3px]">
                            <img src="{{ $box->imageUrl() }}" alt="{{ $box->title }}" class="aspect-[338/237] w-full object-cover transition duration-500 group-hover:scale-105 max-lg:aspect-[327/218]">
                            {{-- Chip tag: label e colore dalla tassonomia ProductType (Soggiorno/Benessere/Avventura) --}}
                            <span class="absolute left-[10px] top-[10px] inline-flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white max-lg:h-[26px]" style="background-color: {{ $box->type->color() }}">{{ $box->type->label() }}</span>
                        </div>
                        <div class="flex flex-1 flex-col px-[18px] pt-2 pb-[14px]">
                            <h3 class="text-[20px] font-semibold leading-[25px] text-black max-lg:text-[18px] max-lg:leading-[22px] max-lg:text-[#0D171A]">{{ $box->title }}</h3>
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

            {{-- Mobile: "Carica altro" al posto della paginazione (XD app: 140x39, r20, #0D171A) --}}
            @if (! $empty && $boxes->hasMorePages())
                <div class="mt-8 flex justify-center lg:hidden">
                    <flux:button wire:click="loadMore" class="!h-[39px] !w-[140px] !rounded-full !bg-[#0D171A] !text-sm !font-bold !text-white hover:!bg-black">{{ __('smartbox.load_more') }}</flux:button>
                </div>
            @endif

            {{-- Paginazione (XD "Raggruppa 744"): pill reali del paginator, stile invariato --}}
            @if (! $empty && $boxes->hasPages())
                <nav class="mt-10 flex items-center justify-center gap-3 max-lg:hidden" aria-label="{{ __('smartbox.pagination') }}">
                    @if ($boxes->onFirstPage())
                        <flux:button variant="ghost" square disabled aria-label="{{ __('smartbox.prev_page') }}" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                            <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                        </flux:button>
                    @else
                        <flux:button variant="ghost" square wire:click="previousPage" aria-label="{{ __('smartbox.prev_page') }}" class="!h-auto !w-auto !p-1 !text-black">
                            <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                        </flux:button>
                    @endif
                    @foreach (range(1, $boxes->lastPage()) as $page)
                        <flux:button wire:key="page-{{ $page }}" square wire:click="gotoPage({{ $page }})" :aria-current="$page === $boxes->currentPage() ? 'page' : null" class="!h-8 !w-8 !rounded-full !border-0 !text-base !font-medium !shadow-none {{ $page === $boxes->currentPage() ? '!bg-black !text-white' : '!bg-white !text-black' }}">{{ $page }}</flux:button>
                    @endforeach
                    @if ($boxes->hasMorePages())
                        <flux:button variant="ghost" square wire:click="nextPage" aria-label="{{ __('smartbox.next_page') }}" class="!h-auto !w-auto !p-1 !text-black">
                            <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                        </flux:button>
                    @else
                        <flux:button variant="ghost" square disabled aria-label="{{ __('smartbox.next_page') }}" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                            <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                        </flux:button>
                    @endif
                </nav>
            @endif
        </div>
    </main>

    @include('partials.catalog.filters-modal', ['priceFloor' => $this::PRICE_MIN, 'priceCeil' => $this::PRICE_MAX, 'resultsCount' => $boxes->total()])

    @include('partials.site-footer')
</div>
