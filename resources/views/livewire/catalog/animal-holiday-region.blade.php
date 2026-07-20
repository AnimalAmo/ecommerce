{{-- Animal Holiday – Regione (XD: "Animal Holiday – Regione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-10 pb-20">
            <h1 class="text-4xl font-bold text-black max-lg:hidden">{{ __('catalog.region_title', ['region' => $regionName]) }}</h1>
            {{-- Titolo mobile (XD app "Cerca - risultati"): sostituisce titolo e sottotitolo desktop --}}
            <h1 class="text-xl font-medium text-[#0D171A] lg:hidden">{{ __('catalog.results_title') }}</h1>
            <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black max-lg:hidden">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

            {{-- Barra ricerca (simbolo condiviso, pre-compilata con la regione).
                 Mobile: pill solo Dove + bottone Filtri a lato (XD app; pannello Filtri 2 in arrivo) --}}
            <div class="flex items-center gap-3">
            <form wire:submit="search" class="mt-10 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A] max-lg:mt-5">
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                    <flux:label class="sr-only">{{ __('holiday.search_where') }}</flux:label>
                    <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                    {{-- Valore pre-compilato: da spec Nunito SemiBold 18px #000000 (override della tipografia condivisa della pill) --}}
                    <flux:input wire:model="where" type="text" placeholder="{{ __('holiday.search_where') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-lg [&_input]:!font-semibold [&_input]:!text-black [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400 max-lg:[&_input]:!text-[15px]" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200 max-lg:hidden"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2 max-lg:hidden">
                    <flux:label class="sr-only">{{ __('holiday.search_when') }}</flux:label>
                    <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="when" type="text" placeholder="{{ __('holiday.search_when') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200 max-lg:hidden"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2 max-lg:hidden">
                    <flux:label class="sr-only">{{ __('holiday.search_guests') }}</flux:label>
                    <flux:icon.team class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="guests" type="text" placeholder="{{ __('holiday.search_guests') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <span class="h-6 w-px shrink-0 bg-gray-200 max-lg:hidden"></span>
                <flux:field class="flex flex-1 items-center gap-3 px-4 py-2 max-lg:hidden">
                    <flux:label class="sr-only">{{ __('holiday.search_animals') }}</flux:label>
                    <flux:icon.animal class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model="animals" type="text" placeholder="{{ __('holiday.search_animals') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:text-gray-400" />
                </flux:field>
                <flux:button type="submit" square aria-label="{{ __('holiday.search_cta') }}" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                    <flux:icon.search class="h-5 w-5" />
                </flux:button>
            </form>
            {{-- Bottone Filtri, solo mobile (XD app: icona a destra della barra; pannello "Filtri 2" TODO) --}}
            <flux:button variant="ghost" square aria-label="{{ __('holiday.filter_your_search') }}" class="mt-5 !h-11 !w-11 shrink-0 !rounded-full !text-ink lg:!hidden">
                <flux:icon.filter class="h-6 w-6" />
            </flux:button>
            </div>

            {{-- Chip tipologie attive, solo mobile (XD app: pill #EBF9FD testo #4FB8D8 con X) --}}
            <div class="mt-4 flex items-center gap-1.5 lg:hidden">
                @foreach ($activeTypes as $activeType)
                    <flux:button wire:key="chip-{{ $activeType }}" wire:click="removeType('{{ $activeType }}')" class="!flex !h-[31px] !items-center !rounded-full !border !border-[#C8C8C8] !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
                        {{ $activeType === 'hotel' ? __('catalog.badge_hotel') : __('catalog.badge_services') }}
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

            {{-- Griglia risultati (XD: simbolo "Box hotel", 4 colonne × 3 righe) --}}
            <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-4 max-lg:mt-5 max-lg:grid-cols-1 max-lg:gap-y-6">
                @foreach ($results as $result)
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

    @include('partials.site-footer')
</div>
