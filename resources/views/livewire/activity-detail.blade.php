{{-- Attività – Dettaglio, tab Informazioni + Discussione (XD: "Attività - Dettaglio - informazioni" / "Attività - Dettaglio - discussione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, Indietro, azioni, CTA galleria, tile durata --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/activity-detail-hero.jpg') }}" alt="{{ $activity['title'] }}" class="absolute inset-0 h-full w-full object-cover">
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

                {{-- CTA galleria (TODO: galleria foto attività) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    Vedere tutte le foto
                </flux:button>

                {{-- Tile durata: fascia viola + giorni (XD "Rettangolo 644/645" + "3 gg" #8E53E6) --}}
                <div class="absolute bottom-[14px] left-4 h-[89px] w-[95px] overflow-hidden rounded-[4px] bg-white lg:left-8">
                    <div class="h-6 w-full bg-[#8E53E6]" aria-hidden="true"></div>
                    <p class="flex h-[65px] items-center justify-center text-[25px] font-semibold text-[#8E53E6]">{{ $durationDays }} gg</p>
                </div>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-[65px]">
            {{-- 2. Testata: titolo (il prezzo vive nel box prenotazione a destra, come da XD) --}}
            <h1 class="text-[25px] font-bold leading-[30px] text-black">{{ $activity['title'] }}</h1>

            {{-- 3. Tab bar (switch Livewire Informazioni / Discussione) + azione Preferiti --}}
            <div class="mt-[13px] flex items-end justify-between gap-4 border-b border-[#DEDEDE]">
                <nav class="flex items-end gap-[39px]" aria-label="Sezioni attività">
                    <flux:button variant="ghost" wire:click="switchTab('informazioni')" :aria-current="$tab === 'informazioni' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'informazioni' ? '!text-[#68CDEB] hover:!text-[#68CDEB]' : '!text-[#C8C8C8]' }}">
                        Informazioni
                        @if ($tab === 'informazioni')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#68CDEB]" aria-hidden="true"></span>
                        @endif
                    </flux:button>
                    <flux:button variant="ghost" wire:click="switchTab('discussione')" :aria-current="$tab === 'discussione' ? 'page' : null" class="relative !h-auto !rounded-none !p-0 !pb-[11px] !text-lg !font-medium hover:!bg-transparent {{ $tab === 'discussione' ? '!text-[#68CDEB] hover:!text-[#68CDEB]' : '!text-[#C8C8C8]' }}">
                        Discussione
                        @if ($tab === 'discussione')
                            <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#68CDEB]" aria-hidden="true"></span>
                        @endif
                    </flux:button>
                </nav>
                <div class="flex shrink-0 items-center gap-4 pb-[10px]">
                    {{-- TODO: azione Preferiti --}}
                    <flux:button class="!h-[39px] !w-[128px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none">
                        <flux:icon.heart class="h-4 w-4 shrink-0" />
                        Preferiti
                    </flux:button>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                @if ($tab === 'informazioni')
                {{-- Colonna sinistra: descrizione, informazioni generali, attività, cosa è incluso --}}
                <div wire:key="tab-informazioni" class="min-w-0 flex-1 lg:max-w-[896px] lg:pt-[22px]">
                    {{-- 4a. Descrizione --}}
                    <section>
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Descrizione</h2>
                        <p class="mt-3 text-[15px] leading-[22px] text-[#2B2B2B]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata.</p>
                    </section>

                    {{-- 4b. Informazioni generali: durata, località, ritrovo --}}
                    <section class="mt-10">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Informazioni generali</h2>
                        <ul class="mt-3 space-y-4">
                            <li class="flex items-start gap-4">
                                <flux:icon.time class="mt-0.5 h-[15px] w-[15px] shrink-0 text-[#0D171A]" />
                                <div>
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $durationLabel }}</p>
                                    <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <flux:icon.pin class="mt-0.5 h-[15px] w-3 shrink-0 text-[#0D171A]" />
                                <div>
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $activity['location'] }}</p>
                                    <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                {{-- XD: coppia di figure "noun-user" — resa con l'icona team --}}
                                <flux:icon.team class="mt-0.5 h-[15px] w-[15px] shrink-0 text-[#0D171A]" />
                                <div>
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">Ritrovo: Hotel Miramare, {{ $activity['location'] }}</p>
                                    <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">Via Roma 63, 30057, Viareggio, Italia</p>
                                </div>
                            </li>
                        </ul>
                    </section>

                    {{-- 4c. Attività --}}
                    <section class="mt-10">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Attività</h2>
                        <p class="mt-3 text-[15px] leading-[22px] text-[#2B2B2B]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata.</p>
                    </section>

                    {{-- 4d. Cosa è incluso (box bordato, check verdi / X rosa su due colonne) --}}
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
                @else
                {{-- Colonna sinistra: fai una domanda + attività recenti (XD: "Attività - Dettaglio - discussione") --}}
                <div wire:key="tab-discussione" class="min-w-0 flex-1 lg:max-w-[896px] lg:pt-[22px]">
                    {{-- 5a. Fai una domanda --}}
                    <section>
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Fai una domanda</h2>
                        {{-- TODO: invio domanda --}}
                        <flux:input type="text" placeholder="Scrivi qualcosa …" class="mt-[13px] !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-[61px] [&_input]:!rounded-[4px] [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!px-[11px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                    </section>

                    {{-- 5b. Attività recenti: card thread con risposta in linea --}}
                    <section class="mt-[31px]">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Attività recenti</h2>
                        <div class="mt-[25px] space-y-6">
                            @foreach ($threads as $thread)
                                <article wire:key="thread-{{ $loop->index }}" class="rounded-[4px] bg-white pb-6 pl-[17px] pr-6 pt-6 shadow-[1px_1px_2.5px_rgba(0,0,0,0.10)]">
                                    @foreach ($thread['messages'] as $message)
                                        <div wire:key="thread-{{ $loop->parent->index }}-msg-{{ $loop->index }}" @class(['mt-4' => ! $loop->first])>
                                            <p class="text-[15px] font-bold leading-[21px] text-[#68CDEB]">{{ $message['author'] }}</p>
                                            <p class="mt-[7px] text-[15px] leading-[21px] text-[#2B2B2B]">{{ $message['body'] }}</p>
                                        </div>
                                    @endforeach
                                    {{-- Riga risposta: input pill con bottone "Rispondi" sovrapposto al bordo destro (XD x887 su input 159→1014) --}}
                                    <div class="relative mt-[23px]">
                                        {{-- TODO: invio risposta --}}
                                        <flux:input type="text" placeholder="Scrivi qualcosa …" class="!border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!rounded-full [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!pl-3 [&_input]:!pr-[135px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                                        <flux:button class="!absolute !right-0 !top-0 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#68CDEB] !text-[15px] !font-bold !text-white !shadow-none">Rispondi</flux:button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        {{-- TODO: caricamento di altri thread --}}
                        <flux:button class="!mx-auto !mt-[34px] !flex !h-10 !w-[145px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-black">Carica altro</flux:button>
                    </section>
                </div>
                @endif

                {{-- 6. Colonna destra (entrambe le tab): box prenotazione (XD "Raggruppa 3019") --}}
                <aside class="w-full shrink-0 lg:w-[453px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white px-[21px] pb-6 pt-[21px]">
                        @if ($isFree)
                            {{-- TODO: conferma design attività gratuita --}}
                            <p class="text-[28px] italic leading-[38px] text-[#2B2B2B]">Gratis</p>
                        @else
                            <p class="text-[28px] font-light leading-[38px] text-[#2B2B2B]">{{ $priceHeadline }}</p>
                        @endif

                        {{-- Selettore date / ospiti / animali (statico come da XD, nessuna interazione definita) --}}
                        <div class="mt-4 rounded-[4px] border border-[#DEDEDE]">
                            <div class="grid grid-cols-2 divide-x divide-[#DEDEDE]">
                                <div class="px-[15px] pb-[14px] pt-3">
                                    <p class="text-[17px] font-medium leading-[23px] text-[#2B2B2B]">Check-in</p>
                                    <p class="mt-[3px] text-[17px] font-light leading-[23px] text-[#2B2B2B]">17/12/2023</p>
                                </div>
                                <div class="pb-[14px] pl-[18px] pr-[15px] pt-3">
                                    <p class="text-[17px] font-medium leading-[23px] text-[#2B2B2B]">Check-out</p>
                                    <p class="mt-[3px] text-[17px] font-light leading-[23px] text-[#2B2B2B]">19/12/2023</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-[#DEDEDE] px-[15px] pb-[14px] pt-3">
                                <div>
                                    <p class="text-[17px] font-medium leading-[23px] text-[#2B2B2B]">Ospiti</p>
                                    <p class="mt-[1px] text-[17px] font-light leading-[23px] text-[#2B2B2B]">2 adulti</p>
                                </div>
                                <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black" />
                            </div>
                            <div class="flex items-center justify-between border-t border-[#DEDEDE] px-[15px] pb-[14px] pt-3">
                                <div>
                                    <p class="text-[17px] font-medium leading-[23px] text-[#2B2B2B]">Animali</p>
                                    <p class="mt-[1px] text-[17px] font-light leading-[23px] text-[#2B2B2B]">1 cani</p>
                                </div>
                                <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black" />
                            </div>
                        </div>

                        @if ($isFree)
                            {{-- [&>span]: con wire:click Flux avvolge lo slot in uno span display:block (swap spinner) che impilerebbe icona e testo --}}
                            <flux:button wire:click="joinEvent" class="!mt-[26px] !flex !h-[39px] !w-full !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:justify-center [&>span]:gap-2">
                                <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                                Partecipa
                            </flux:button>
                        @else
                            {{-- TODO: azione Aggiungi al carrello (nessun pop-up XD dedicato alle attività) --}}
                            <flux:button class="!mt-[26px] !flex !h-[39px] !w-full !rounded-full !border-0 !bg-brand-yellow !text-sm !font-bold !text-[#0D171A] !shadow-none">Aggiungi al carrello</flux:button>

                            <div class="mt-[25px] flex items-center justify-between text-[17px] leading-[23px] text-[#2B2B2B]">
                                <p>{{ $pricePerTwo }}</p>
                                <p>{{ $totalPrice }}</p>
                            </div>
                            <div class="mt-4 border-t border-[#DEDEDE]" aria-hidden="true"></div>
                            <div class="mt-4 flex items-center justify-between text-[17px] font-bold leading-[23px] text-[#2B2B2B]">
                                <p>Totale</p>
                                <p>{{ $totalPrice }}</p>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>

            @if ($tab === 'informazioni')
            {{-- 7. Dove siamo: banda mappa a tutta larghezza con pill località (XD "Gruppo di maschere 7" 1638x389 + "Raggruppa 842") --}}
            <section wire:key="dove-siamo" class="mt-[57px]">
                <h2 class="text-[25px] font-bold leading-[30px] text-black">Dove siamo</h2>
                <div class="relative mt-4 h-[389px] w-full overflow-hidden rounded-[4px]">
                    {{-- TODO: screenshot placeholder dall'XD — sostituire con una mappa embedded reale --}}
                    <img src="{{ asset('img/xd/event-detail-map.jpg') }}" alt="Mappa della zona — Hotel Miramare" class="h-full w-full object-cover">
                    {{-- TODO: apertura mappa (nessuna interazione definita nell'XD) --}}
                    <flux:button class="!absolute !left-[715px] !top-[125px] !h-[38px] !gap-2 !rounded-full !border-0 !bg-brand-yellow !px-[18px] !text-[13px] !font-semibold !text-black !shadow-none">
                        <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                        Hotel Miramare
                    </flux:button>
                </div>
            </section>
            @else
            {{-- 8. Domande frequenti a tutta larghezza sotto entrambe le colonne (XD "Raggruppa 3017" + "Linea 43") --}}
            <section wire:key="faq" class="mt-[60px] border-t border-[#DEDEDE]">
                <div class="pl-[79px] pr-[71px] pt-10">
                    <h2 class="text-[22px] font-bold leading-[30px] text-[#68CDEB]">Domande frequenti</h2>
                    {{-- Prima voce espansa (chevron verso l'alto + risposta visibile) — TODO: accordion --}}
                    <div class="mt-[31px] flex items-start justify-between gap-4">
                        <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">Lorem ipsum dolor sit amet, consetetur sadipscing</p>
                        <flux:icon.chevron-up class="mt-1 !h-3.5 !w-3.5 shrink-0 text-[#1E2E33]" />
                    </div>
                    <p class="mt-[14px] text-[15px] leading-[21px] text-[#627277]">Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</p>
                    {{-- Voci chiuse (chevron a destra) --}}
                    <div class="mt-[21px]">
                        @for ($i = 0; $i < 4; $i++)
                            <div wire:key="faq-{{ $i }}" class="flex items-center justify-between gap-4 border-t border-[#E2EAEB] pb-[14px] pt-[17px]">
                                <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">Lorem ipsum dolor sit amet, consetetur sadipscing</p>
                                <flux:icon.chevron-right class="!h-3.5 !w-3.5 shrink-0 text-[#1E2E33]" />
                            </div>
                        @endfor
                        <div class="border-t border-[#E2EAEB]" aria-hidden="true"></div>
                    </div>
                </div>
            </section>
            @endif
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />

    {{-- Pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa") — duplicato dal dettaglio evento come i pop-up fratelli tra le pagine;
         dati della pagina: foto hero attività e data check-in. Solo attività gratuite (doppia cintura oltre alla guardia in joinEvent). --}}
    @if ($isFree && $joinPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Aggiunto agli eventi" x-data @keydown.escape.window="$wire.closeJoinPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeJoinPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">Aggiunto agli eventi</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeJoinPopup" aria-label="Chiudi" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ asset('img/xd/activity-detail-hero.jpg') }}" alt="{{ $activity['title'] }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $activity['title'] }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    17/12/2023
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- TODO: pagina "I miei eventi" (l'XD punta a un artboard con la lista eventi dell'utente) --}}
                    <flux:button href="#" class="!ml-auto !mt-4 !flex !h-10 !w-[162px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">Vai agli eventi</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
