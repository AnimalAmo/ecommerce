{{-- Animal Holiday – Regione (XD: "Animal Holiday – Regione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Padding verticale ridotto su mobile (XD app: titolo a 44px dall'header) --}}
        <div class="{{ $px }} pt-10 pb-20 max-lg:pt-6 max-lg:pb-10">
            <h1 class="text-4xl font-bold text-black max-lg:hidden">{{ __('catalog.region_title', ['region' => $regionName]) }}</h1>
            {{-- Titolo mobile (XD app "Cerca - risultati"): sostituisce titolo e sottotitolo desktop --}}
            <h1 class="text-xl font-medium text-[#0D171A] lg:hidden">{{ __('catalog.results_title') }}</h1>
            <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black max-lg:hidden">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

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

            {{-- Chip tipologie attive, solo mobile (XD app: pill #EBF9FD testo #4FB8D8 con X).
                 Con le chip Smartbox la riga sfora i 375px: scorre in orizzontale ("Scorri gruppo 3"). --}}
            <div class="-mx-4 mt-4 flex items-center gap-1.5 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:hidden">
                {{-- Chip fascia di prezzo (XD app "Cerca - risultati – click su 'filtri'": "9 - 415 €" con X) --}}
                @if ($priceMin !== \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN || $priceMax !== \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX)
                    <flux:button wire:click="resetPrice" class="!flex !h-[31px] !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
                        {{ $priceMin }} - {{ $priceMax }} €
                        <flux:icon.close class="h-2.5 w-2.5" />
                    </flux:button>
                @endif
                @foreach ($activeTypes as $activeType)
                    <flux:button wire:key="chip-{{ $activeType }}" wire:click="removeType('{{ $activeType }}')" class="!flex !h-[31px] shrink-0 !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
                        {{ $activeType === 'hotel' ? __('catalog.badge_hotel') : __('catalog.filter_types.'.$activeType) }}
                        <flux:icon.close class="h-2.5 w-2.5" />
                    </flux:button>
                @endforeach
                {{-- Chip Smartbox (XD app "Cerca - risultati - click 'filtri' – 2": Soggiorno/Benessere/Avventura) --}}
                @foreach ($smartboxTypes as $smartboxType)
                    <flux:button wire:key="chip-sb-{{ $smartboxType }}" wire:click="toggleSmartboxType('{{ $smartboxType }}')" class="!flex !h-[31px] shrink-0 !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
                        {{ __('catalog.smartbox_chips.'.$smartboxType) }}
                        <flux:icon.close class="h-2.5 w-2.5" />
                    </flux:button>
                @endforeach
            </div>

            {{-- Filtri (XD: due pill dropdown "Componente 20"; comportamento dropdown TODO) --}}
            <p class="mt-10 text-lg font-semibold leading-6 text-black max-lg:hidden">{{ __('holiday.filter_your_search') }}</p>
            <div class="mt-[17px] flex items-center gap-[11px] max-lg:hidden">
                <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                    {{ __('holiday.filter_type') }}
                    <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                </flux:button>
                <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                    {{ __('holiday.filter_price') }}
                    <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                </flux:button>
            </div>

            {{-- Nessun risultato (XD app "Nessun risultato"): al posto della griglia vuota
                 compare l'avviso e, sotto, le card proposte come alternativa. --}}
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

            {{-- Griglia risultati (XD: simbolo "Box hotel", 4 colonne × 3 righe) --}}
            <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-4 max-lg:mt-5 max-lg:grid-cols-1 max-lg:gap-y-6">
                @foreach ($empty ? $similar : $results as $result)
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
                                {{-- Strutture partner senza recensioni: stato "Nuovo" --}}
                                {{ $result->rating !== null ? \App\Support\Format::rating($result->rating) : __('holiday.new') }}
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

            {{-- Paginazione (statica; pagina 1 attiva, prev disabilitato) --}}
            {{-- Mobile: "Carica altro" al posto della paginazione (XD app; statico come la paginazione) --}}
            <div class="mt-8 flex justify-center lg:hidden">
                <flux:button class="!h-[39px] !rounded-full !bg-[#0D171A] !px-8 !text-sm !font-bold !text-white hover:!bg-black">{{ __('holiday.load_more') }}</flux:button>
            </div>
            <nav class="mt-10 flex items-center justify-center gap-3 max-lg:hidden" aria-label="{{ __('holiday.pagination') }}">
                <flux:button variant="ghost" square disabled aria-label="{{ __('holiday.prev_page') }}" class="!h-auto !w-auto !p-1 !text-[#C8C8C8]">
                    <flux:icon.arrow-down class="h-4 w-4 rotate-90" />
                </flux:button>
                @foreach (range(1, 4) as $page)
                    <flux:button wire:key="page-{{ $page }}" square :aria-current="$page === 1 ? 'page' : null" class="!h-8 !w-8 !rounded-full !border-0 !text-base !font-medium !shadow-none {{ $page === 1 ? '!bg-black !text-white' : '!bg-white !text-black' }}">{{ $page }}</flux:button>
                @endforeach
                <flux:button variant="ghost" square aria-label="{{ __('holiday.next_page') }}" class="!h-auto !w-auto !p-1 !text-black">
                    <flux:icon.arrow-down class="h-4 w-4 -rotate-90" />
                </flux:button>
            </nav>
        </div>
    </main>

    {{-- ============ MODAL FILTRI 2 (solo mobile, XD app "Filtri 2 ricerca") ============ --}}
    @php
        // Card tipologia XD: 156x100, r3, bordo 0.5 #C8C8C8; selezionata bg #EBF9FD
        $filterCard = '!flex !h-[100px] !w-full !rounded-[3px] !border !border-[#C8C8C8] !p-3 !shadow-none [&>span]:!flex [&>span]:!h-full [&>span]:!w-full [&>span]:!flex-col [&>span]:!items-start [&>span]:!justify-between';
        // Doppio slider prezzo: input sovrapposti, solo i pallini (24px, bianchi, bordo #E9E9E9) ricevono il tocco
        $rangeInput = '!absolute !inset-0 !h-6 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-6 [&_input]:!appearance-none [&_input]:!bg-transparent [&_input]:!border-0 [&_input]:!rounded-none [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:!outline-none [&_input]:!pointer-events-none [&_input::-webkit-slider-thumb]:pointer-events-auto [&_input::-webkit-slider-thumb]:h-6 [&_input::-webkit-slider-thumb]:w-6 [&_input::-webkit-slider-thumb]:appearance-none [&_input::-webkit-slider-thumb]:rounded-full [&_input::-webkit-slider-thumb]:border [&_input::-webkit-slider-thumb]:border-[#E9E9E9] [&_input::-webkit-slider-thumb]:bg-white [&_input::-moz-range-thumb]:pointer-events-auto [&_input::-moz-range-thumb]:h-6 [&_input::-moz-range-thumb]:w-6 [&_input::-moz-range-thumb]:appearance-none [&_input::-moz-range-thumb]:rounded-full [&_input::-moz-range-thumb]:border [&_input::-moz-range-thumb]:border-[#E9E9E9] [&_input::-moz-range-thumb]:bg-white';
        $priceBox = 'flex h-[50px] w-[112px] flex-col justify-center rounded-[3px] border border-[#C8C8C8] px-3';
        // "9 €" compatto come nel mock: larghezza dinamica via Alpine (x-bind:style sul wrapper), niente chrome né spinner
        $priceInput = '!w-auto !h-5 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-5 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!leading-5 [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:[-moz-appearance:textfield] [&_input::-webkit-inner-spin-button]:appearance-none [&_input::-webkit-outer-spin-button]:appearance-none';
        $checkbox = '[--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan';
    @endphp
    {{-- min+max h-dvh: pannello a tutto schermo che scrolla al suo interno (contenuto XD fino a 1353px) --}}
    <flux:modal name="mobile-filters" :closable="false" class="w-full !m-0 !max-w-full !min-h-dvh !max-h-dvh !overflow-y-auto !rounded-none bg-white !px-6 !py-6 lg:hidden">
        <div class="relative flex items-center justify-center py-1">
            <h2 class="text-lg font-semibold text-[#0D171A]">{{ __('catalog.filters_title') }}</h2>
            <div class="absolute right-0 top-1/2 -translate-y-1/2">
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.menu_close') }}" class="!rounded-full !text-[#555555]">
                        <flux:icon.close class="h-4 w-4" />
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>

        {{-- Fascia di prezzo (XD: istogramma cyan/#EBF9FD + doppio cursore + box Minimo/Massimo) --}}
        <div class="mt-4 border-t border-[#E9E9E9] pt-7">
            <h3 class="text-lg font-semibold text-[#0D171A]">{{ __('holiday.filter_price') }}</h3>

            <div x-data="{
                    floor: {{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN }},
                    ceil: {{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX }},
                    lo: {{ $priceMin }}, hi: {{ $priceMax }},
                    bars: [7, 12, 22, 33, 62, 47, 80, 70, 55, 42, 67, 55, 42, 34, 24, 14, 11, 7],
                    pct(v) { return (v - this.floor) / (this.ceil - this.floor) * 100 },
                    barOn(i) { const c = (i + 0.5) / this.bars.length * 100; return c >= this.pct(Math.min(this.lo, this.hi)) && c <= this.pct(Math.max(this.lo, this.hi)) },
                    {{-- assegnazione diretta = deferred; $set forza il roundtrip (porta con sé anche priceMin) --}}
                    push() { $wire.priceMin = Math.min(this.lo, this.hi); $wire.$set('priceMax', Math.max(this.lo, this.hi)) },
                 }"
                 x-effect="lo = $wire.priceMin; hi = $wire.priceMax"
                 class="mt-6">
                {{-- Istogramma decorativo (altezze dal mock): barre nella fascia in cyan, fuori #EBF9FD --}}
                <div class="flex h-20 items-end justify-center gap-[3px]" aria-hidden="true">
                    <template x-for="(bar, i) in bars" :key="i">
                        <div class="w-[7px]" x-bind:class="barOn(i) ? 'bg-brand-cyan' : 'bg-[#EBF9FD]'" x-bind:style="`height:${bar}px`"></div>
                    </template>
                </div>
                {{-- -mt-3: i pallini stanno centrati SULLA baseline dell'istogramma (XD y256≈fondo barre) --}}
                <div class="relative -mt-3 h-6">
                    <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-[#EBF9FD]"></div>
                    <div class="absolute top-1/2 h-px -translate-y-1/2 bg-brand-cyan" x-bind:style="`left:${pct(Math.min(lo, hi))}%; right:${100 - pct(Math.max(lo, hi))}%`"></div>
                    <flux:label class="sr-only" for="filter-price-lo">{{ __('catalog.filter_price_min') }}</flux:label>
                    <flux:input id="filter-price-lo" type="range" min="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN }}" max="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX }}" x-model.number="lo" x-on:change="push" class="{{ $rangeInput }}" />
                    <flux:label class="sr-only" for="filter-price-hi">{{ __('catalog.filter_price_max') }}</flux:label>
                    <flux:input id="filter-price-hi" type="range" min="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN }}" max="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX }}" x-model.number="hi" x-on:change="push" class="{{ $rangeInput }}" />
                </div>

                {{-- Box Minimo/Massimo (XD: 112x50, r3, bordo #C8C8C8, label 11 light, valore 14 + €) --}}
                <div class="mt-5 flex items-center justify-between gap-4">
                    <label class="{{ $priceBox }}">
                        <span class="text-[11px] font-light text-[#555555]">{{ __('catalog.filter_price_min') }}</span>
                        <span class="flex items-center gap-1 text-sm text-[#0D171A]">
                            <flux:input type="number" min="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN }}" max="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX }}" x-model.number="lo" x-bind:style="`width:${String(lo).length * 9 + 4}px`" x-on:change="push" class="{{ $priceInput }}" />
                            €
                        </span>
                    </label>
                    <label class="{{ $priceBox }}">
                        <span class="text-[11px] font-light text-[#555555]">{{ __('catalog.filter_price_max') }}</span>
                        <span class="flex items-center gap-1 text-sm text-[#0D171A]">
                            <flux:input type="number" min="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MIN }}" max="{{ \App\Livewire\Catalog\AnimalHolidayRegion::PRICE_MAX }}" x-model.number="hi" x-bind:style="`width:${String(hi).length * 9 + 4}px`" x-on:change="push" class="{{ $priceInput }}" />
                            €
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Tipologia (XD: griglia 2 colonne, card icona+label; selezionata bg #EBF9FD e testo cyan) --}}
        <div class="mt-7 border-t border-[#E9E9E9] pt-7">
            <h3 class="text-lg font-semibold text-[#0D171A]">{{ __('holiday.filter_type') }}</h3>
            <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                <flux:button wire:click="toggleType('hotel')" class="{{ $filterCard }} {{ in_array('hotel', $activeTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                    <flux:icon.structure-bb class="h-8 w-8 {{ in_array('hotel', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-[15px] font-medium {{ in_array('hotel', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.hotel') }}</span>
                </flux:button>
                <flux:button wire:click="toggleType('servizi')" class="{{ $filterCard }} {{ in_array('servizi', $activeTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                    <flux:icon.animal class="h-8 w-8 {{ in_array('servizi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-[15px] font-medium {{ in_array('servizi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.servizi') }}</span>
                </flux:button>
                <flux:button wire:click="toggleType('attivita')" class="{{ $filterCard }} {{ in_array('attivita', $activeTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                    <flux:icon.activity-balloons class="h-8 w-8 {{ in_array('attivita', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-[15px] font-medium {{ in_array('attivita', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.attivita') }}</span>
                </flux:button>
                <flux:button wire:click="toggleType('eventi')" class="{{ $filterCard }} {{ in_array('eventi', $activeTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                    <flux:icon.activity-navigation class="h-8 w-8 {{ in_array('eventi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-[15px] font-medium {{ in_array('eventi', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.eventi') }}</span>
                </flux:button>
                <flux:button wire:click="toggleType('smartbox')" class="{{ $filterCard }} {{ in_array('smartbox', $activeTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                    <flux:icon.gift class="h-8 w-8 {{ in_array('smartbox', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                    <span class="text-[15px] font-medium {{ in_array('smartbox', $activeTypes, true) ? 'text-brand-cyan' : 'text-[#0D171A]' }}">{{ __('catalog.filter_types.smartbox') }}</span>
                </flux:button>
            </div>
        </div>

        {{-- Sezioni Smartbox (XD "Filtri 2 ricerca - click su 'soggiorno'": compaiono con Smartbox attivo) --}}
        @if (in_array('smartbox', $activeTypes, true))
            <div class="mt-7 border-t border-[#E9E9E9] pt-7">
                <h3 class="text-lg font-semibold text-[#0D171A]">{{ __('catalog.smartbox_type_title') }}</h3>
                <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4">
                    <flux:button wire:click="toggleSmartboxType('soggiorno')" class="{{ $filterCard }} {{ in_array('soggiorno', $smartboxTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                        <flux:icon.structure-bb class="h-8 w-8 {{ in_array('soggiorno', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                        <span class="text-left text-sm font-normal text-[#959595]">{{ __('catalog.smartbox_types.soggiorno') }}</span>
                    </flux:button>
                    <flux:button wire:click="toggleSmartboxType('benessere')" class="{{ $filterCard }} {{ in_array('benessere', $smartboxTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                        <flux:icon.smartbox-wellness class="h-8 w-8 {{ in_array('benessere', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                        <span class="text-left text-sm font-normal text-[#959595]">{{ __('catalog.smartbox_types.benessere') }}</span>
                    </flux:button>
                    <flux:button wire:click="toggleSmartboxType('avventura')" class="{{ $filterCard }} {{ in_array('avventura', $smartboxTypes, true) ? '!bg-[#EBF9FD]' : '!bg-white' }}">
                        <flux:icon.smartbox-adventure class="h-8 w-8 {{ in_array('avventura', $smartboxTypes, true) ? 'text-brand-cyan' : 'text-[#2B2B2B]' }}" />
                        <span class="text-left text-sm font-normal text-[#959595]">{{ __('catalog.smartbox_types.avventura') }}</span>
                    </flux:button>
                </div>
            </div>

            <div class="mt-7">
                <h3 class="text-lg font-semibold text-[#0D171A]">{{ __('catalog.people_title') }}</h3>
                <flux:checkbox.group wire:model.live="peopleGroups" class="mt-4 flex flex-col gap-4">
                    @foreach (['coppia', 'famiglia', 'gruppo'] as $group)
                        <label wire:key="people-{{ $group }}" class="flex items-center gap-3">
                            <flux:checkbox value="{{ $group }}" class="{{ $checkbox }}" />
                            <span class="text-[15px] font-medium text-[#0D171A]">{{ __('catalog.people_groups.'.$group) }}</span>
                        </label>
                    @endforeach
                </flux:checkbox.group>
            </div>
        @endif

        <flux:modal.close>
            <flux:button class="mx-auto mt-10 !flex !h-[39px] !rounded-full !bg-[#0D171A] !px-8 !text-sm !font-bold !text-white hover:!bg-black">{{ __('catalog.show_results', ['count' => $results->count()]) }}</flux:button>
        </flux:modal.close>
    </flux:modal>

    @include('partials.site-footer')
</div>
