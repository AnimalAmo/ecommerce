{{-- Evento – Dettaglio, tab informazioni (XD: "Evento - Dettaglio - informazioni") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, Indietro, azioni, CTA galleria, tile data --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/event-detail-hero.jpg') }}" alt="{{ $event['title'] }}" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('eventi') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        Indietro
                    </a>
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

                {{-- CTA galleria (TODO: galleria foto evento) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    Vedere tutte le foto
                </flux:button>

                {{-- Tile data: fascia lavanda + giorno (XD "Rettangolo 644/645" + "5 Gen") --}}
                <div class="absolute bottom-[14px] left-4 h-[89px] w-[95px] overflow-hidden rounded-[4px] bg-white lg:left-8">
                    <div class="h-6 w-full bg-brand-purple-soft" aria-hidden="true"></div>
                    <p class="flex h-[65px] items-center justify-center text-[25px] font-semibold text-brand-purple-soft">5 Gen</p>
                </div>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            {{-- 2. Testata: orario, titolo, prezzo --}}
            <p class="flex items-center gap-2 text-[15px] font-medium leading-[21px] text-brand-purple-soft">
                <flux:icon.time class="h-[15px] w-[15px] shrink-0" />
                Oggi alle ore 13:30
            </p>
            <h1 class="mt-[9px] text-[25px] font-bold leading-[30px] text-black">{{ $event['title'] }}</h1>
            <p class="mt-[9px] text-[25px] font-light leading-[34px] text-black">{{ $event['price'] ?? 'A partire da 0,00 €' }}</p>

            {{-- 3. Tab bar (Informazioni attiva) + azioni Preferiti / Aggiungi al carrello --}}
            <div class="flex items-end justify-between gap-4 border-b border-[#DEDEDE]">
                <nav class="flex items-end gap-[39px]" aria-label="Sezioni evento">
                    <flux:button variant="ghost" aria-current="page" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">
                        Informazioni
                        <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#68CDEB]" aria-hidden="true"></span>
                    </flux:button>
                    {{-- TODO: tab Discussione --}}
                    <flux:button variant="ghost" class="!h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium !text-[#C8C8C8] hover:!bg-transparent">Discussione</flux:button>
                </nav>
                <div class="flex shrink-0 items-center gap-4 pb-[10px]">
                    {{-- TODO: azione Preferiti --}}
                    <flux:button class="!h-[39px] !w-[128px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none">
                        <flux:icon.heart class="h-4 w-4 shrink-0" />
                        Preferiti
                    </flux:button>
                    {{-- TODO: azione Aggiungi al carrello --}}
                    <flux:button class="!h-[39px] !w-[204px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none">
                        <flux:icon.cart class="h-4 w-4 shrink-0" />
                        Aggiungi al carrello
                    </flux:button>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-6">
                {{-- Colonna sinistra: descrizione, informazioni generali, cosa è incluso --}}
                <div class="min-w-0 flex-1 lg:max-w-[896px]">
                    {{-- 4a. Descrizione --}}
                    <section>
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Descrizione</h2>
                        <p class="mt-3 text-[15px] leading-[22px] text-[#2B2B2B]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata.</p>
                    </section>

                    {{-- 4b. Informazioni generali --}}
                    <section class="mt-10">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Informazioni generali</h2>
                        <ul class="mt-3 space-y-4">
                            <li class="flex items-start gap-4">
                                <flux:icon.time class="mt-0.5 h-[15px] w-[15px] shrink-0 text-[#0D171A]" />
                                <div>
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">Oggi dalle 13:30 alle 16:30</p>
                                    <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <flux:icon.pin class="mt-0.5 h-[15px] w-3 shrink-0 text-[#0D171A]" />
                                <div>
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">Dario Boario Terme (BS), Italia</p>
                                    <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.</p>
                                </div>
                            </li>
                        </ul>
                    </section>

                    {{-- 4c. Cosa è incluso (box bordato, check verdi / X rosa su due colonne) --}}
                    <section class="mt-8 min-h-[250px] w-full rounded-[4px] border border-[#DEDEDE] bg-white px-4 pb-6 pt-[22px]">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Cosa è incluso</h2>
                        <div class="mt-[13px] grid grid-cols-1 gap-x-6 gap-y-[7px] sm:grid-cols-2">
                            @foreach ($includedColumns as $column => $items)
                                <ul wire:key="included-col-{{ $column }}" class="space-y-[7px]">
                                    @foreach ($items as $item)
                                        <li wire:key="included-{{ $column }}-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] leading-[21px] text-[#0D171A]">
                                            @if ($item['included'])
                                                <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                            @else
                                                <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                            @endif
                                            {{ $item['label'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>
                    </section>
                </div>

                {{-- 5. Colonna destra: card mappa con pill località --}}
                <aside class="w-full shrink-0 lg:w-[718px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white p-5">
                        <div class="relative overflow-hidden rounded-[4px]">
                            {{-- TODO: screenshot placeholder dall'XD — sostituire con una mappa embedded reale --}}
                            <img src="{{ asset('img/xd/event-detail-map.jpg') }}" alt="Mappa della zona — Cascina Brescia" class="h-[576px] w-full object-cover">
                            {{-- TODO: apertura mappa (nessuna interazione definita nell'XD) --}}
                            <flux:button class="!absolute !left-[310px] !top-[348px] !h-[38px] !gap-2 !rounded-full !border-0 !bg-brand-yellow !px-[18px] !text-[13px] !font-semibold !text-black !shadow-none">
                                <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                                Cascina Brescia
                            </flux:button>
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
</div>
