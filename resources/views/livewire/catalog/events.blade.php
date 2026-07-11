{{-- Attività ed Eventi (XD: "Eventi") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Colonna contenuti XD: x211..1709 → 1498px centrati dentro il container $px --}}
        <div class="{{ $px }} pt-10 pb-20">
            <div class="mx-auto w-full max-w-[1498px]">
                <h1 class="text-4xl font-bold text-black">{{ __('events.title') }}</h1>
                <p class="mt-4 max-w-[1295px] text-lg leading-6 text-black">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>

                {{-- Barra ricerca (simbolo "Ricerca" condiviso con Animal Holiday) --}}
                <form wire:submit="search" class="mt-10 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 shadow-[1px_1px_10px_#0000001A]">
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

                {{-- Filtri (XD: due pill dropdown "Componente 20"; comportamento dropdown TODO) --}}
                <p class="mt-10 text-lg font-semibold leading-6 text-black">{{ __('events.filter_your_search') }}</p>
                <div class="mt-[17px] flex items-center gap-[13px]">
                    <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                        {{ __('events.filter_type') }}
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                    <flux:button class="!h-[30px] !gap-2 !rounded-full !border !border-[#C8C8C8] !bg-white !px-3.5 !text-sm !font-normal !text-[#555555] !shadow-none">
                        {{ __('events.filter_price') }}
                        <flux:icon.arrow-down class="h-3 w-3 shrink-0" />
                    </flux:button>
                </div>

                {{-- Griglia eventi (XD: simbolo "Box eventi" 354x415, 4 colonne × 3 righe) --}}
                <div class="mt-10 grid grid-cols-4 gap-x-[27px] gap-y-6">
                    @foreach ($events as $event)
                        <article wire:key="event-{{ $event->id }}" class="group relative flex flex-col rounded-[3px] border border-[#E9E9E9] bg-white">
                            <div class="relative overflow-hidden rounded-t-[3px]">
                                <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}" class="aspect-[354/246] w-full object-cover transition duration-500 group-hover:scale-105">
                                {{-- Badge sempre 'Evento' come da XD, anche sulle attività --}}
                                <span class="absolute left-[18px] top-[20px] inline-flex h-[27px] items-center rounded-[3px] bg-brand-purple-soft px-[10px] text-sm font-medium text-white">{{ \App\Enums\ProductType::Event->label() }}</span>
                            </div>
                            <div class="flex flex-1 flex-col px-[18px] pb-[22px]">
                                {{-- Riga orario: slot fisso; sulle attività compare solo la durata non-weekend (accent #8E53E6) --}}
                                <p class="mt-[15px] flex h-[17px] items-center gap-1 text-[13px] font-bold uppercase tracking-[0.025em] {{ $event->type === \App\Enums\ProductType::Activity && $event->duration_days ? 'text-[#8E53E6]' : 'text-brand-purple-soft' }}">
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
                                <h3 class="mt-[10px] text-[20px] font-semibold leading-[25px] text-black">{{ $event->title }}</h3>
                                <div class="mt-auto flex items-center justify-between gap-2 pt-[18px]">
                                    @if (! $event->hasJoinCta())
                                        <flux:button wire:click="addToCart({{ $event->id }})" class="relative !z-[2] !h-[39px] !w-[204px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                                            <flux:icon.cart class="h-4 w-4 shrink-0" />
                                            {{ __('events.add_to_cart') }}
                                        </flux:button>
                                    @else
                                        {{-- TODO: azione Partecipa --}}
                                        <flux:button class="relative !z-[2] !h-[39px] !w-[136px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !text-sm !font-bold !text-[#0D171A] !shadow-none">
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

                {{-- Paginazione (XD "Raggruppa 744"): pill reali del paginator, stile invariato --}}
                @if ($events->hasPages())
                    <nav class="mt-10 flex items-center justify-center gap-3" aria-label="{{ __('events.pagination') }}">
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

    @include('partials.site-footer')
</div>
