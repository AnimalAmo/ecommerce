{{-- Container centrato stile matsuri: max width desktop, niente full-width stretched --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    {{-- ============ HEADER (partial condiviso) ============ --}}
    @include('partials.site-header')

    {{-- ============ HERO ============ --}}
    <section class="relative isolate overflow-hidden bg-brand-cyan-bg">
        <img src="{{ asset('img/home-hero.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">

        <div class="{{ $px }} flex min-h-[560px] max-h-[976px] flex-col justify-center py-24 lg:h-[calc(100svh-5rem)]">
            {{-- Box hero (stile XD: #152E36, radius 2px): titolo, testo, Dove/Quando --}}
            {{-- Mobile (XD app "Cerca - home"): niente box scuro, testo diretto sulla foto --}}
            <div class="w-full max-w-2xl rounded-[2px] bg-[#152E36] px-4 py-6 shadow-[0px_3px_6px_#00000029] max-lg:bg-transparent max-lg:px-0 max-lg:py-0 max-lg:shadow-none">
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white max-lg:text-2xl max-lg:font-bold">{{ __('home.hero_title') }}</h1>
                <p class="mt-4 text-sm leading-relaxed text-white/70 max-lg:text-[15px] max-lg:text-white">{{ __('home.hero_text') }}</p>

                {{-- Search bar stile XD: pill bianco (border #F4F4F4, radius 100px) con input + pulsante dentro --}}
                {{-- Mobile: la pill è solo un trigger, la ricerca vive nel modal "Filtri cerca" (XD app) --}}
                <form wire:submit="search" class="mt-8 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2 max-lg:hidden">
                    <flux:field class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:label class="sr-only">{{ __('home.search_where') }}</flux:label>
                        <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <flux:input wire:model="where" type="text" placeholder="{{ __('home.search_where') }}" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-sm [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:text-gray-400" />
                    </flux:field>
                    <span class="h-6 w-px shrink-0 bg-gray-200 max-lg:hidden"></span>
                    {{-- "Quando": trigger + popover Alpine col calendario range condiviso. Il popover NON si
                         chiude al click su un giorno (il range richiede 2 click); i wire:click del calendario
                         restano funzionanti dentro. Layout della pill invariato: stesso segmento flex-1. --}}
                    {{-- Mobile: pill con solo "Dove" (XD app), il segmento Quando resta desktop-only --}}
                    <div x-data="{ open: false }" class="relative flex flex-1 items-center max-lg:hidden">
                        <flux:button type="button" variant="ghost" x-on:click="open = ! open" class="!flex !h-auto !w-full !items-center !justify-start !gap-3 !rounded-none !bg-transparent !px-4 !py-2 !shadow-none hover:!bg-transparent [&>span]:!flex [&>span]:!min-w-0 [&>span]:!items-center [&>span]:!gap-3">
                            <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                            <span class="truncate text-sm {{ $editCheckIn ? 'text-ink' : 'text-gray-400' }}">{{ $editCheckIn ? $editCheckIn.' – '.($editCheckOut ?? '…') : __('home.search_when') }}</span>
                        </flux:button>
                        <div x-show="open" x-on:click.outside="open = false" x-transition.opacity style="display: none" class="absolute left-0 top-full z-50 mt-3 w-[320px] rounded-[4px] border border-[#DEDEDE] bg-white p-[10px] text-left shadow-[0px_3px_6px_#00000029]">
                            @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                        </div>
                    </div>
                    <flux:button type="submit" square aria-label="{{ __('home.search_cta') }}" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                        <flux:icon.search class="h-5 w-5" />
                    </flux:button>
                </form>

                {{-- Pill mobile: apre il modal filtri (XD app "Filtri cerca - click su 'destinazione'") --}}
                <flux:modal.trigger name="mobile-search">
                    <flux:button class="mt-8 !flex !h-auto !w-full !items-center !justify-between !gap-2 !whitespace-normal !rounded-[100px] !border-[#F4F4F4] !bg-white !p-2 !font-normal !shadow-none lg:!hidden">
                        <span class="flex min-w-0 flex-1 items-center gap-3 px-4 py-2">
                            <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                            <span class="truncate text-sm font-normal {{ $where !== '' ? 'text-ink' : 'text-gray-400' }}">{{ $where !== '' ? $where : __('home.search_where') }}</span>
                        </span>
                        <span class="flex h-12 w-12 shrink-0 grow-0 items-center justify-center rounded-full bg-brand-cyan text-white">
                            <flux:icon.search class="h-5 w-5" />
                        </span>
                    </flux:button>
                </flux:modal.trigger>
            </div>

            {{-- "Scopri di più" solo mobile (XD app: link bianco centrato in fondo all'hero) --}}
            <a href="#holiday" class="mt-auto flex items-center gap-2 self-center pt-10 text-[15px] text-white lg:hidden">
                {{ __('home.discover_more') }}
                <flux:icon.chevron-down class="h-4 w-4" />
            </a>
        </div>
    </section>

    {{-- ============ MODAL FILTRI CERCA (solo mobile, XD app "Filtri cerca - click su 'destinazione'") ============ --}}
    <flux:modal name="mobile-search" :closable="false" class="w-full !m-0 !max-w-full !min-h-dvh !max-h-none !rounded-none bg-white !px-4 !py-6 lg:hidden">
        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm" square aria-label="{{ __('nav.menu_close') }}" class="!rounded-full !text-ink">
                    <flux:icon.close class="h-4 w-4" />
                </flux:button>
            </flux:modal.close>
        </div>

        <h2 class="mt-2 text-xl font-medium text-[#0D171A]">{{ __('home.search_title') }}</h2>

        <form wire:submit="search" class="mt-5 flex flex-col gap-3">
            {{-- Destinazione (XD "click su 'lombardia'"): la riga si espande dentro la stessa pill
                 con divider #F2F2F2 e lista suggerimenti (nomi regione, live sul testo digitato) --}}
            <div x-data="{ open: false }" x-on:click.outside="open = false" class="rounded-[28px] border border-[#E2EAEB] bg-white">
                <label class="flex h-14 items-center gap-3 px-5">
                    <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <flux:input wire:model.live.debounce.300ms="where" x-on:focus="open = true" type="text" placeholder="{{ __('home.search_destination') }}" class="!flex-1 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!border-0 [&_input]:!bg-transparent [&_input]:!p-0 [&_input]:!text-[15px] [&_input]:!font-medium [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:focus:!outline-none [&_input]:focus-visible:!outline-none [&_input]:placeholder:!font-normal [&_input]:placeholder:text-[#555555]" />
                    <flux:icon.chevron-down class="h-4 w-4 shrink-0 text-[#0D171A] transition" x-bind:class="open && 'rotate-180'" />
                </label>
                <div x-show="open && $wire.where.length > 0" x-transition.opacity style="display: none" class="mx-2 border-t border-[#F2F2F2] px-3 pb-3 pt-2">
                    @forelse ($destinations as $destination)
                        <flux:button variant="ghost" wire:key="dest-{{ $destination }}" wire:click="selectDestination('{{ addslashes($destination) }}')" x-on:click="open = false" class="!flex !h-11 !w-full !justify-start !rounded-none !px-1 !text-[15px] !font-normal !text-black hover:!bg-gray-100">{{ $destination }}</flux:button>
                    @empty
                        <p class="px-1 py-2.5 text-[15px] text-[#959595]">{{ __('holiday.no_location') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Quando (XD "click su 'calendario'"): calendario range dentro la stessa pill,
                 variante app (mese cyan, selezione quadrata cyan, banda range #EBF9FD) --}}
            <div x-data="{ open: false }" class="rounded-[28px] border border-[#E2EAEB] bg-white">
                <flux:button variant="ghost" x-on:click="open = ! open" class="!flex !h-14 !w-full !items-center !rounded-[28px] !px-5 hover:!bg-transparent [&>span]:!flex [&>span]:!w-full [&>span]:!items-center [&>span]:!gap-3">
                    <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <span class="flex-1 truncate text-left text-[15px] {{ $editCheckIn ? 'font-medium text-[#0D171A]' : 'font-normal text-[#555555]' }}">{{ $editCheckIn ? $editCheckIn.' – '.($editCheckOut ?? '…') : __('home.search_when') }}</span>
                    <flux:icon.chevron-down class="h-4 w-4 shrink-0 text-[#0D171A] transition" x-bind:class="open && 'rotate-180'" />
                </flux:button>
                <div x-show="open" x-transition.opacity style="display: none" class="mx-2 border-t border-[#F2F2F2] px-2 pb-4">
                    @include('partials.booking.calendar', [
                        'calendar' => $calendar,
                        'calendarLabel' => $calendarLabel,
                        'appStyle' => true,
                        'rangeStart' => $editCheckIn ? \DateTimeImmutable::createFromFormat('!d/m/Y', $editCheckIn)->format('Y-m-d') : null,
                        'rangeEnd' => $editCheckOut ? \DateTimeImmutable::createFromFormat('!d/m/Y', $editCheckOut)->format('Y-m-d') : null,
                    ])
                </div>
            </div>

            {{-- Tipologia (XD "click su 'tipologia'"): lista a scelta singola dentro la pill --}}
            <div x-data="{ open: false }" class="rounded-[28px] border border-[#E2EAEB] bg-white">
                <flux:button variant="ghost" x-on:click="open = ! open" class="!flex !h-14 !w-full !items-center !rounded-[28px] !px-5 hover:!bg-transparent [&>span]:!flex [&>span]:!w-full [&>span]:!items-center [&>span]:!gap-3">
                    <flux:icon.spa class="h-5 w-5 shrink-0 text-brand-cyan" />
                    <span class="flex-1 truncate text-left text-[15px] {{ $type !== '' ? 'font-medium text-[#0D171A]' : 'font-normal text-[#555555]' }}">{{ $type !== '' ? __('home.search_types.'.$type) : __('home.search_type') }}</span>
                    <flux:icon.chevron-down class="h-4 w-4 shrink-0 text-[#0D171A] transition" x-bind:class="open && 'rotate-180'" />
                </flux:button>
                <div x-show="open" x-transition.opacity style="display: none" class="mx-2 border-t border-[#F2F2F2] px-3 pb-3 pt-2">
                    @foreach (\App\Livewire\Catalog\HomePage::SEARCH_TYPES as $searchType)
                        <flux:button variant="ghost" wire:key="type-{{ $searchType }}" wire:click="selectType('{{ $searchType }}')" x-on:click="open = false" class="!flex !h-11 !w-full !justify-start !rounded-none !px-1 !text-[15px] !font-normal !text-black hover:!bg-gray-100">{{ __('home.search_types.'.$searchType) }}</flux:button>
                    @endforeach
                </div>
            </div>

            {{-- Aggiungi ospiti (XD "click su 'aggiungi ospiti'"): stepper condivisi dentro la pill --}}
            <div x-data="{ open: false }" class="rounded-[28px] border border-[#E2EAEB] bg-white">
                <flux:button variant="ghost" x-on:click="open = ! open" class="!flex !h-14 !w-full !items-center !rounded-[28px] !px-5 hover:!bg-transparent [&>span]:!flex [&>span]:!w-full [&>span]:!items-center [&>span]:!gap-3">
                    <flux:icon.team class="h-5 w-5 shrink-0 text-brand-cyan" />
                    @php $guestsTotal = array_sum($editGuests); @endphp
                    <span class="flex-1 truncate text-left text-[15px] {{ $guestsTotal > 1 ? 'font-medium text-[#0D171A]' : 'font-normal text-[#555555]' }}">{{ $guestsTotal > 1 ? trans_choice('home.search_guests_count', $guestsTotal) : __('home.search_guests') }}</span>
                    <flux:icon.chevron-down class="h-4 w-4 shrink-0 text-[#0D171A] transition" x-bind:class="open && 'rotate-180'" />
                </flux:button>
                <div x-show="open" x-transition.opacity style="display: none" class="mx-2 border-t border-[#F2F2F2] px-3 pb-2 pt-1">
                    @include('partials.booking.guest-steppers', ['guests' => $editGuests, 'guestsAtMax' => $this->guestsAtMax()])
                </div>
            </div>

            {{-- Animali (XD "click su 'animali' – 1"): stepper cani/gatti dentro la pill --}}
            <div x-data="{ open: false }" class="rounded-[28px] border border-[#E2EAEB] bg-white">
                <flux:button variant="ghost" x-on:click="open = ! open" class="!flex !h-14 !w-full !items-center !rounded-[28px] !px-5 hover:!bg-transparent [&>span]:!flex [&>span]:!w-full [&>span]:!items-center [&>span]:!gap-3">
                    <flux:icon.animal class="h-5 w-5 shrink-0 text-brand-cyan" />
                    @php $animalsTotal = array_sum($editAnimals); @endphp
                    <span class="flex-1 truncate text-left text-[15px] {{ $animalsTotal > 0 ? 'font-medium text-[#0D171A]' : 'font-normal text-[#555555]' }}">{{ $animalsTotal > 0 ? trans_choice('home.search_animals_count', $animalsTotal) : __('home.search_animals') }}</span>
                    <flux:icon.chevron-down class="h-4 w-4 shrink-0 text-[#0D171A] transition" x-bind:class="open && 'rotate-180'" />
                </flux:button>
                <div x-show="open" x-transition.opacity style="display: none" class="mx-2 border-t border-[#F2F2F2] px-3 pb-2 pt-1">
                    @include('partials.booking.animal-stepper', ['animals' => $editAnimals, 'animalsAtMax' => $this->animalsAtMax()])
                </div>
            </div>

            {{-- Button azzurro XD: 343x39, r19, #6CD1EF, testo 15 semibold bianco --}}
            <flux:button type="submit" class="mt-3 !h-[39px] !w-full !rounded-full !bg-brand-cyan !text-[15px] !font-semibold !text-white hover:!bg-brand-cyan-soft">{{ __('home.search_cta') }}</flux:button>
        </form>
    </flux:modal>

    {{-- ============ ANIMAL HOLIDAY ============ --}}
    <section id="holiday" class="{{ $px }} scroll-mt-20 py-20 max-lg:py-10">
        <div class="mb-10 flex items-end justify-between max-lg:mb-6">
            <div>
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-magenta">{{ __('home.holiday_kicker') }}</p>
                <h2 class="text-4xl font-extrabold max-lg:text-2xl max-lg:font-bold">{{ __('home.holiday_title') }}</h2>
                <p class="mt-3 max-w-xl text-lg text-gray-500 max-lg:text-[15px]">{{ __('home.holiday_subtitle') }}</p>
            </div>
        </div>
        {{-- Mobile (XD app): riga di card 280px a scroll orizzontale invece della griglia --}}
        <div class="grid grid-cols-3 gap-6 max-lg:-mx-4 max-lg:flex max-lg:snap-x max-lg:gap-4 max-lg:overflow-x-auto max-lg:px-4 max-lg:pb-2">
            @foreach ($regions as $region)
                <a href="{{ route('holiday.region', ['region' => $region->slug]) }}" wire:key="reg-{{ $region->id }}" class="group block rounded-[3px] border border-[#E9E9E9] bg-white p-[10px] max-lg:w-[280px] max-lg:shrink-0 max-lg:snap-start">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset('img/xd/'.$region->img.'.jpg') }}" alt="{{ __('catalog.region_title', ['region' => $region->name]) }}" class="h-80 w-full object-cover transition duration-500 group-hover:scale-105 max-lg:h-44">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
                        <flux:badge class="absolute right-4 top-4 !rounded-[3px] !bg-brand-magenta !text-white">{{ __('catalog.structures_count', ['count' => $region->structures_count]) }}</flux:badge>
                        <h3 class="absolute bottom-4 left-4 pr-4 text-[20px] font-bold text-white max-lg:text-[15px]">{{ __('catalog.region_title', ['region' => $region->name]) }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10 flex justify-center">
            <a href="{{ route('holiday') }}" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C] max-lg:py-2.5">{{ __('home.see_all') }}</a>
        </div>
    </section>

    {{-- ============ EVENTI ============ --}}
    <section id="eventi" class="scroll-mt-20">
        {{-- Banda immagine con gradiente XD: nero 60% a sx → trasparente a dx --}}
        <div class="relative isolate overflow-hidden">
            <img src="{{ asset('img/eventi-bg.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,#00000099_0%,#71717100_100%)]"></div>
            <div class="{{ $px }} flex min-h-[660px] flex-col justify-end pb-10 pt-24 max-lg:min-h-[420px] max-lg:pt-16">
                <h2 class="text-4xl font-extrabold text-white max-lg:text-2xl max-lg:font-bold">{{ __('home.events_title') }}</h2>
                <p class="mt-3 max-w-xl text-lg text-white/85 max-lg:text-[15px]">{{ __('home.events_subtitle') }}</p>
                <a href="{{ route('eventi') }}" class="mt-12 w-fit rounded-full bg-brand-cyan px-6 py-3 text-[15px] font-extrabold text-white transition hover:bg-[#68CDEB]">{{ __('home.events_cta') }}</a>
            </div>
        </div>
        <div class="{{ $px }} pb-16 pt-6">
            {{-- Mobile (XD app): card 280px a scroll orizzontale --}}
            <div class="grid grid-cols-5 gap-6 max-lg:-mx-4 max-lg:flex max-lg:snap-x max-lg:gap-4 max-lg:overflow-x-auto max-lg:px-4 max-lg:pb-2">
                @foreach ($events as $event)
                    <div wire:key="ev-{{ $event->id }}" class="group flex h-full flex-col rounded-[3px] border border-[#E9E9E9] bg-white p-2 max-lg:w-[280px] max-lg:shrink-0 max-lg:snap-start">
                        <div class="relative overflow-hidden">
                            <img src="{{ $event->imageUrl() }}" alt="{{ $event->title }}" class="max-h-[227px] w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="flex flex-1 flex-col p-2 pt-3">
                            <p class="flex items-center gap-1.5 text-[13px] text-brand-purple-soft">
                                <flux:icon.time class="h-4 w-4 shrink-0" />
                                {{ \App\Support\Format::eventTimeSentence($event->starts_at) }}
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-[13px] text-[#555555]">
                                <flux:icon.pin class="h-4 w-4 shrink-0 text-[#555555]" />
                                {{ $event->location }}
                            </p>
                            <h3 class="mt-[10px] text-[20px] font-semibold leading-snug text-black max-lg:text-[17px]">{{ $event->title }}</h3>
                            {{-- Blocco pulsante+prezzo: sempre in fondo (mt-auto) e su un solo rigo,
                                 pulsante e prezzo affiancati; 'A partire da' più piccolo per starci. --}}
                            <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                                <flux:button href="{{ $event->type === \App\Enums\ProductType::Activity ? route('eventi.activity', $event->slug) : route('eventi.detail', $event->slug) }}" size="sm" class="shrink-0 !rounded-full !border-0 !bg-[#E9E9E9] !px-4 !text-sm !text-[#0D171A] !shadow-none hover:!bg-brand-yellow">
                                    <flux:icon.check-1 class="h-4 w-4" />
                                    {{ __('home.join') }}
                                </flux:button>
                                @if ($event->price_cents !== null)
                                    <p class="shrink-0 whitespace-nowrap text-right text-[13px] font-normal text-[#627277]">{{ __('home.from_price_label') }} <span class="text-[15px] font-semibold text-[#0D171A]">€ {{ \App\Support\Format::amount($event->price_cents) }}</span></p>
                                @else
                                    <p class="shrink-0 whitespace-nowrap text-[15px] italic text-[#627277]">{{ __('format.free') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 flex justify-center">
                <a href="{{ route('eventi') }}" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C] max-lg:py-2.5">{{ __('home.see_all') }}</a>
            </div>
        </div>
    </section>

    {{-- ============ SMARTBOX ============ --}}
    <section id="smartbox" class="{{ $px }} scroll-mt-20 pb-20">
        {{-- Card group stile XD: immagine + card bianca attaccate, shadow 1px 1px 10px --}}
        {{-- Mobile: card impilata (foto sopra, testo sotto allineato a sinistra) --}}
        <div class="mx-20 grid min-h-[660px] grid-cols-2 shadow-[1px_1px_10px_#0000001A] max-lg:mx-0 max-lg:min-h-0 max-lg:grid-cols-1">
            <img src="{{ asset('img/smartbox.jpg') }}" alt="Smartbox" class="h-full min-h-[660px] w-full object-cover max-lg:h-56 max-lg:min-h-0">
            <div class="flex flex-col items-end justify-center bg-white p-16 text-right max-lg:items-start max-lg:p-6 max-lg:text-left">
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-magenta">{{ __('home.smartbox_kicker') }}</p>
                <h2 class="text-[36px] font-bold leading-tight text-black max-lg:text-2xl">{{ __('home.smartbox_title') }}</h2>
                <p class="mt-4 text-[18px] text-[#555555] max-lg:text-[15px]">{{ __('home.smartbox_subtitle') }}</p>
                <flux:button href="{{ route('smartbox') }}" class="mt-8 w-fit !rounded-full !border-0 !bg-brand-cyan !px-6 !py-3 !text-[15px] !font-extrabold !text-white !shadow-none hover:!bg-[#68CDEB]">{{ __('home.smartbox_cta') }}</flux:button>
            </div>
        </div>
    </section>

    {{-- ============ NEWS ============ --}}
    <section id="news" class="scroll-mt-20 bg-brand-cyan-bg py-8">
        <div class="{{ $px }}">
            <div class="text-center">
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-magenta">{{ __('home.news_kicker') }}</p>
                <h2 class="text-[36px] font-bold text-black max-lg:text-2xl">{{ __('home.news_title') }}</h2>
                <p class="mt-3 text-[18px] font-normal text-[#555555] max-lg:text-[15px]">{{ __('home.news_subtitle') }}</p>
            </div>
            {{-- Mobile (XD app): card 300px a scroll orizzontale --}}
            <div class="mt-10 grid grid-cols-3 gap-6 max-lg:-mx-4 max-lg:mt-6 max-lg:flex max-lg:snap-x max-lg:gap-4 max-lg:overflow-x-auto max-lg:px-4 max-lg:pb-2">
                @foreach ($news as $article)
                    <div wire:key="news-{{ $loop->index }}" class="rounded-[3px] bg-white px-[10px] py-2 max-lg:w-[300px] max-lg:shrink-0 max-lg:snap-start">
                        <img src="{{ asset('img/xd/'.$article['img'].'.jpg') }}" alt="{{ $article['title'] }}" class="h-[237px] w-full object-cover">
                        <div class="p-2">
                            <p class="flex items-center gap-1.5 font-[Roboto,sans-serif] text-sm text-[#959595]">
                                <flux:icon.calendar class="h-4 w-4 shrink-0 text-[#959595]" />
                                {{ $article['date'] }}
                            </p>
                            <h3 class="my-4 text-[20px] font-semibold text-black">{{ $article['title'] }}</h3>
                            <p class="mb-4 text-sm font-normal text-[#555555]">{{ $article['excerpt'] }}</p>
                            <div class="flex justify-center">
                                {{-- Le card home non hanno slug proprio: si risolve per immagine su News::ARTICLES (la card fuori elenco rimanda a /news) --}}
                                @php $newsSlug = collect(\App\Livewire\Content\News::ARTICLES)->firstWhere('img', $article['img'])['slug'] ?? null; @endphp
                                <flux:button variant="ghost" :href="$newsSlug ? route('news.detail', $newsSlug) : route('news')" class="!text-sm !font-normal !text-[#242C2C] hover:!bg-transparent">{{ __('home.news_read_more') }}</flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 flex justify-center">
                <a href="{{ route('news') }}" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C] max-lg:py-2.5">{{ __('home.see_all') }}</a>
            </div>
        </div>
    </section>

    {{-- ============ COMMUNITY ============ --}}
    <section id="community" class="scroll-mt-20">
        <div class="relative isolate overflow-hidden">
            <img src="{{ asset('img/footer-community.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">
            {{-- Gradiente XD: nero 60% a dx → trasparente a sx --}}
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(270deg,#00000099_0%,#71717100_100%)]"></div>
            <div class="{{ $px }} flex min-h-[660px] flex-col pb-[98px] max-lg:min-h-[520px] max-lg:pb-10">
                <div class="my-auto max-lg:pt-16">
                    <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-yellow">{{ __('home.community_kicker') }}</p>
                    <h2 class="text-4xl font-extrabold text-white max-lg:text-2xl max-lg:font-bold">{{ __('home.community_title') }}</h2>
                    <p class="mt-3 max-w-md text-lg text-white/85 max-lg:text-[15px]">{{ __('home.community_subtitle') }}</p>
                    <a href="{{ route('community') }}" class="mt-12 inline-block rounded-full bg-brand-cyan px-6 py-3 text-[15px] font-extrabold text-white transition hover:bg-[#68CDEB]">{{ __('home.community_cta') }}</a>
                </div>
                {{-- Box recensione XD: glass bianco su foto, blur 7px --}}
                <div class="max-w-xl self-end rounded-[4px] border border-gray-150 bg-white/10 p-4 backdrop-blur-[7px] max-lg:mt-8 max-lg:self-stretch">
                    <div class="flex items-center justify-between gap-6">
                        <p class="text-[13px] font-semibold text-brand-yellow">25/11/23</p>
                        <p class="text-[13px] font-semibold text-brand-yellow">{{ __('home.community_replies') }}</p>
                    </div>
                    <p class="mt-2 text-lg text-white mb-2">{{ __('home.community_quote') }}</p>
                    <p class="text-lg italic text-white">- Sofia</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    @include('partials.site-footer')
</div>
