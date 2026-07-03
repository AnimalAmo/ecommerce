{{-- Evento – Dettaglio, tab Informazioni + Discussione (XD: "Evento - Dettaglio - informazioni" / "Evento - Dettaglio - discussione") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, Indietro, azioni, CTA galleria, tile data --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            {{-- Foto campione per artboard: "Evento gratis - Dettaglio" usa lo scatto Unsplash della festa, quello a pagamento lo stock del brunch --}}
            <img src="{{ asset($isFree ? 'img/xd/free-event-detail-hero.jpg' : 'img/xd/event-detail-hero.jpg') }}" alt="{{ $event['title'] }}" class="absolute inset-0 h-full w-full object-cover">
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

                {{-- Tile data: fascia lavanda + giorno (XD "Rettangolo 644/645"; campione "5 Gen" a pagamento, "8 Gen" gratuito) --}}
                <div class="absolute bottom-[14px] left-4 h-[89px] w-[95px] overflow-hidden rounded-[4px] bg-white lg:left-8">
                    <div class="h-6 w-full bg-brand-purple-soft" aria-hidden="true"></div>
                    <p class="flex h-[65px] items-center justify-center text-[25px] font-semibold text-brand-purple-soft">{{ $isFree ? '8 Gen' : '5 Gen' }}</p>
                </div>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            {{-- 2. Testata: orario, titolo, prezzo --}}
            <p class="flex items-center gap-2 text-[15px] font-medium leading-[21px] text-brand-purple-soft">
                <flux:icon.time class="h-[15px] w-[15px] shrink-0" />
                {{ $isFree ? 'Lunedì 8 Gennaio alle ore 19:30' : 'Oggi alle ore 13:30' }}
            </p>
            <h1 class="mt-[9px] text-[25px] font-bold leading-[30px] text-black">{{ $event['title'] }}</h1>
            @if ($isFree)
                {{-- "Gratis" in corsivo peso normale (XD Nunito-Italic 25px) al posto della riga prezzo Light --}}
                <p class="mt-[9px] text-[25px] italic leading-[34px] text-black">Gratis</p>
            @else
                <p class="mt-[9px] text-[25px] font-light leading-[34px] text-black">{{ $event['price'] ?? 'A partire da 0,00 €' }}</p>
            @endif

            {{-- 3. Tab bar (switch Livewire Informazioni / Discussione) + azioni Preferiti / Aggiungi al carrello --}}
            <div class="flex items-end justify-between gap-4 border-b border-[#DEDEDE]">
                <nav class="flex items-end gap-[39px]" aria-label="Sezioni evento">
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
                    @if ($isFree)
                        {{-- Pill "Partecipa" come nel listing (XD "Raggruppa 3155" 136x39, check + Nunito-Bold 14) al posto di "Aggiungi al carrello"; senza wire:click non serve il fix [&>span] --}}
                        {{-- TODO: partecipa (il pop-up "Pop-up evento partecipa" è fuori scope) --}}
                        <flux:button class="!h-[39px] !w-[136px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none">
                            <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                            Partecipa
                        </flux:button>
                    @else
                        {{-- [&>span]: con wire:click Flux avvolge lo slot in uno span display:block (swap spinner) che impilerebbe icona e testo --}}
                        <flux:button wire:click="addToCart" class="!h-[39px] !w-[204px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                            <flux:icon.cart class="h-4 w-4 shrink-0" />
                            Aggiungi al carrello
                        </flux:button>
                    @endif
                </div>
            </div>

            @if ($tab === 'informazioni')
            <div wire:key="tab-informazioni" class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-6">
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
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $isFree ? 'Lunedì 8 Gennaio dalle ore 19:30 alle 21:30' : 'Oggi dalle 13:30 alle 16:30' }}</p>
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
            @else
            {{-- Contenuto tab Discussione (XD: "Evento - Dettaglio - discussione") --}}
            <div wire:key="tab-discussione" class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-6">
                {{-- Colonna sinistra: fai una domanda + attività recenti --}}
                <div class="min-w-0 flex-1 lg:max-w-[896px]">
                    {{-- 6a. Fai una domanda --}}
                    <section>
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">Fai una domanda</h2>
                        {{-- TODO: invio domanda --}}
                        <flux:input type="text" placeholder="Scrivi qualcosa …" class="mt-[13px] !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-[61px] [&_input]:!rounded-[4px] [&_input]:!border [&_input]:!border-[#E9E9E9] [&_input]:!bg-white [&_input]:!px-[11px] [&_input]:!text-[15px] [&_input]:!text-ink [&_input]:!shadow-none [&_input]:!ring-0 [&_input]:placeholder:italic [&_input]:placeholder:text-[#959595]" />
                    </section>

                    {{-- 6b. Attività recenti: card thread con risposta in linea --}}
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
                    </section>
                </div>

                {{-- 7. Colonna destra: card "Domande frequenti" (XD Rettangolo 647 718x437 — su questa tab l'XD sostituisce la mappa con le FAQ) --}}
                <aside class="w-full shrink-0 lg:w-[718px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white px-[22px] pb-[1px] pt-[29px]">
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
                        </div>
                    </div>
                </aside>
            </div>
            @endif
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />

    {{-- Pop-up "Aggiunto al carrello" (XD: "Pop-up evento acquista") — stesso pattern del dettaglio struttura, con data e prezzo dell'evento.
         Solo eventi a pagamento: la variante gratuita non ha trigger d'acquisto (doppia cintura oltre alla guardia in addToCart). --}}
    @if (! $isFree && $cartPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Aggiunto al carrello" x-data @keydown.escape.window="$wire.closeCartPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeCartPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">Aggiunto al carrello</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="Chiudi" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ asset('img/xd/event-detail-hero.jpg') }}" alt="{{ $event['title'] }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $event['title'] }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                <li class="flex items-center gap-[5px]">
                                    <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                    07/02/2024
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Prezzo in basso a destra sopra il bottone (XD "25 €" @1724,250) --}}
                    <p class="absolute right-4 top-[118px] text-lg font-semibold leading-6 text-black">{{ $popupPrice }}</p>

                    {{-- TODO: pagina Carrello --}}
                    <flux:button href="#" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">Vai al carrello</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
