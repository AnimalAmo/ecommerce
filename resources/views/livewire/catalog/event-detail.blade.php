{{-- Evento – Dettaglio, sezione Informazioni (XD: "Evento - Dettaglio - informazioni").
     La tab "Discussione" dell'XD non è più renderizzata: i thread erano inventati e la
     funzione discussioni non esiste ancora; le sue FAQ sono passate a questa sezione. --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, Indietro, azioni, CTA galleria, tile data --}}
        <section class="relative h-[524px] w-full overflow-hidden">
            {{-- Foto per evento (XD: scatto festa per il gratuito, stock brunch per il pagamento) --}}
            <img src="{{ $event->heroImageUrl() }}" alt="{{ $event->title }}" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('eventi') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        {{ __('events.back') }}
                    </a>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="{{ __('events.share') }}" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    @include('partials.favorite-heart', ['type' => 'event', 'id' => $event->id, 'active' => $isFav])
                </div>

                {{-- CTA galleria (TODO: galleria foto evento) --}}
                <flux:button class="!absolute bottom-[22px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    {{ __('events.view_all_photos') }}
                </flux:button>

                {{-- Tile data: fascia lavanda + giorno (XD "Rettangolo 644/645"), derivata da starts_at (assente sugli eventi partner senza data) --}}
                @if ($event->starts_at)
                    <div class="absolute bottom-[14px] left-4 h-[89px] w-[95px] overflow-hidden rounded-[4px] bg-white lg:left-8">
                        <div class="h-6 w-full bg-brand-purple-soft" aria-hidden="true"></div>
                        <p class="flex h-[65px] items-center justify-center text-[20px] font-semibold text-brand-purple-soft">{{ \App\Support\Format::dateTile($event->starts_at) }}</p>
                    </div>
                @endif
            </div>
        </section>

        <div class="{{ $px }} pb-[120px] pt-10">
            {{-- 2. Testata: orario, titolo, prezzo --}}
            @if ($event->starts_at)
                <p class="flex items-center gap-2 text-[15px] font-medium leading-[21px] text-brand-purple-soft">
                    {{-- Icona nera come il pin/marker (il testo orario resta viola). --}}
                    <flux:icon.time class="h-[15px] w-[15px] shrink-0 text-[#0D171A]" />
                    {{ \App\Support\Format::eventTimeFull($event->starts_at) }}
                </p>
            @endif
            <h1 class="mt-[9px] text-[25px] font-bold leading-[30px] text-black">{{ $event->title }}</h1>
            @if ($isFree)
                {{-- "Gratis" in corsivo peso normale (XD Nunito-Italic 25px) al posto della riga prezzo Light --}}
                <p class="mt-[9px] text-[25px] italic leading-[34px] text-black">{{ __('format.free') }}</p>
            @else
                <p class="mt-[9px] text-[25px] font-light leading-[34px] text-black">{!! $event->price_cents !== null ? __('format.per_person', ['price' => '<span class="font-bold">'.e(\App\Support\Format::money($event->price_cents)).'</span>']) : __('format.from_price', ['price' => '<span class="font-bold">'.e(\App\Support\Format::money(0)).'</span>']) !!}</p>
            @endif

            {{-- 3. Intestazione di sezione + azioni Preferiti / Aggiungi al carrello.
                 Rimossa la tab "Discussione": i thread erano inventati (costante PHP) e la
                 funzione non esiste ancora, quindi resta una sola sezione — la barra non è
                 più navigazione ma l'etichetta della sezione, con lo stesso stile della tab attiva. --}}
            <div class="flex items-end justify-between gap-4 border-b border-[#DEDEDE]">
                <h2 class="relative pb-[11px] text-lg font-medium text-[#68CDEB]">
                    {{ __('events.tab_info') }}
                    <span class="absolute inset-x-0 bottom-0 h-[2.5px] translate-y-[1.25px] bg-[#68CDEB]" aria-hidden="true"></span>
                </h2>
                <div class="flex shrink-0 items-center gap-4 pb-[10px]">
                    {{-- Preferiti reali: attivo = "Mi interessa" su fondo brand-yellow, l'icona resta. --}}
                    <flux:button wire:click="toggleFavorite('event', {{ $event->id }})" class="!h-[39px] !min-w-[128px] !shrink-0 !gap-2 !rounded-full !border-0 !px-5 !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2 {{ $isFav ? '!bg-brand-yellow hover:!bg-brand-yellow' : '!bg-gray-150 hover:!bg-[#DEDEDE]' }}">
                        <flux:icon.heart class="h-4 w-4 shrink-0" />
                        {{ $isFav ? __('events.interested') : __('events.favorites') }}
                    </flux:button>
                    @if ($canJoin)
                        {{-- Pill "Partecipa" come nel listing (XD "Raggruppa 3155" 136x39, check + Nunito-Bold 14) al posto di "Aggiungi al carrello" --}}
                        {{-- [&>span]: con wire:click Flux avvolge lo slot in uno span display:block (swap spinner) che impilerebbe icona e testo --}}
                        <flux:button wire:click="joinEvent" class="!h-[39px] !w-[136px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 hover:!bg-[#DEDEDE] !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                            <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                            {{ __('events.join') }}
                        </flux:button>
                    @else
                        {{-- [&>span]: con wire:click Flux avvolge lo slot in uno span display:block (swap spinner) che impilerebbe icona e testo --}}
                        <flux:button wire:click="addToCart" class="!h-[39px] !w-[204px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-gray-150 hover:!bg-[#DEDEDE] !text-sm !font-bold !text-[#0D171A] !shadow-none [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                            <flux:icon.cart class="h-4 w-4 shrink-0" />
                            {{ __('events.add_to_cart') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-6">
                {{-- Colonna sinistra: descrizione, informazioni generali, cosa è incluso --}}
                <div class="min-w-0 flex-1 lg:max-w-[896px]">
                    {{-- 4a. Descrizione (nascosta senza copy) --}}
                    @if (filled($event->description))
                        <section>
                            <h2 class="text-[22px] font-bold leading-[30px] text-black">{{ __('events.description') }}</h2>
                            <p class="mt-3 text-[15px] leading-[22px] text-[#2B2B2B]">{{ $event->description }}</p>
                        </section>
                    @endif

                    {{-- 4b. Informazioni generali --}}
                    {{-- first:mt-0: con la descrizione nascosta questa è la prima sezione, il mt-10 raddoppierebbe il padding della colonna --}}
                    <section class="mt-10 first:mt-0">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">{{ __('events.general_info') }}</h2>
                        <ul class="mt-3 space-y-4">
                            @if ($event->starts_at && $event->ends_at)
                                <li class="flex items-start gap-4">
                                    <flux:icon.time class="mt-0.5 h-4 w-4 shrink-0 text-[#0D171A]" />
                                    <div>
                                        <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ \App\Support\Format::eventTimeRange($event->starts_at, $event->ends_at) }}</p>
                                        @if (filled($event->time_note))
                                            <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">{{ $event->time_note }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endif
                            <li class="flex items-start gap-4">
                                <flux:icon.pin class="mt-0.5 h-4 w-4 shrink-0 text-[#0D171A]" />
                                <div>
                                    {{-- venue_id nullable: guard sugli eventi senza venue --}}
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $event->venue?->address ?? $event->location }}</p>
                                    @if (filled($event->venue_note))
                                        <p class="mt-[7px] max-w-[613px] text-[15px] leading-[21px] text-[#555555]">{{ $event->venue_note }}</p>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </section>

                    {{-- 4c. Cosa è incluso (box bordato, check verdi / X rosa su due colonne) --}}
                    <section class="mt-8 min-h-[250px] w-full rounded-[4px] border border-[#DEDEDE] bg-white px-4 pb-6 pt-[22px]">
                        <h2 class="text-[22px] font-bold leading-[30px] text-black">{{ __('events.included') }}</h2>
                        <div class="mt-[13px] grid grid-cols-1 gap-x-6 gap-y-[7px] sm:grid-cols-2">
                            @foreach ($includedColumns as $column => $items)
                                <ul wire:key="included-col-{{ $column }}" class="space-y-[7px]">
                                    @foreach ($items as $item)
                                        <li wire:key="included-{{ $column }}-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] leading-[21px] text-[#0D171A]">
                                            @if ($item['included'])
                                                <flux:icon.check class="h-[18px] w-[18px] shrink-0 text-[#37C443]" />
                                            @else
                                                <flux:icon.close class="h-[18px] w-[18px] shrink-0 text-[#EA2E68]" />
                                            @endif
                                            {{ $item['label'] }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </div>
                    </section>
                </div>

                {{-- 5. Colonna destra: card mappa (nascosta senza venue) e card "Domande frequenti".
                     Le FAQ stavano nella colonna destra della tab Discussione: rimossa quella tab,
                     senza questo spostamento il contenuto reale del partner sparirebbe dalla pagina. --}}
                @if ($event->venue?->hasMap() || $faqs->isNotEmpty())
                    <aside class="w-full shrink-0 space-y-6 lg:w-[718px]">
                        @if ($event->venue?->hasMap())
                            <div class="rounded-[4px] border border-[#DEDEDE] bg-white p-5">
                                <div class="relative overflow-hidden rounded-[4px]">
                                    <x-google-map :query="$event->venue->mapQuery()" :fallback="$event->venue->mapFallbackUrl()" alt="Mappa della zona — {{ $event->venue->name }}" class="h-[576px]" />
                                    {{-- Pill puramente descrittiva: pointer-events-none per non rubare i click alla mappa --}}
                                    <flux:button class="!pointer-events-none !absolute !left-[310px] !top-[348px] !h-[38px] !gap-2 !rounded-full !border-0 !bg-brand-yellow !px-[18px] !text-[13px] !font-semibold !text-black !shadow-none">
                                        <flux:icon.pin class="h-[15px] w-3 shrink-0" />
                                        {{ $event->venue->name }}
                                    </flux:button>
                                </div>
                            </div>
                        @endif

                        {{-- Card "Domande frequenti" (XD Rettangolo 647 718x437; nascosta senza FAQ) --}}
                        @if ($faqs->isNotEmpty())
                            <div class="rounded-[4px] border border-[#DEDEDE] bg-white px-[22px] pb-[1px] pt-[29px]">
                                <h2 class="text-[22px] font-bold leading-[30px] text-[#68CDEB]">{{ __('events.faq') }}</h2>
                                {{-- Prima voce espansa (chevron verso l'alto + risposta visibile) — TODO: accordion --}}
                                <div class="mt-[31px] flex items-start justify-between gap-4">
                                    <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $faqs->first()->question }}</p>
                                    <flux:icon.chevron-up class="mt-1 !h-3.5 !w-3.5 shrink-0 text-[#1E2E33]" />
                                </div>
                                <p class="mt-[14px] text-[15px] leading-[21px] text-[#627277]">{{ $faqs->first()->answer }}</p>
                                {{-- Voci chiuse (chevron a destra) --}}
                                <div class="mt-[21px]">
                                    @foreach ($faqs->skip(1) as $faq)
                                        <div wire:key="faq-{{ $faq->id }}" class="flex items-center justify-between gap-4 border-t border-[#E2EAEB] pb-[14px] pt-[17px]">
                                            <p class="text-[15px] font-medium leading-[21px] text-[#0D171A]">{{ $faq->question }}</p>
                                            <flux:icon.chevron-right class="!h-3.5 !w-3.5 shrink-0 text-[#1E2E33]" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </aside>
                @endif
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Pop-up "Aggiunto al carrello" (XD: "Pop-up evento acquista") — stesso pattern del dettaglio struttura, con data e prezzo dell'evento.
         Solo eventi con CTA carrello (doppia cintura oltre alla guardia in addToCart). --}}
    @if (! $canJoin && $cartPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="{{ __('events.added_to_cart') }}" x-data @keydown.escape.window="$wire.closeCartPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeCartPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">{{ __('events.added_to_cart') }}</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeCartPopup" aria-label="{{ __('events.close') }}" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ $event->heroImageUrl() }}" alt="{{ $event->title }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $event->title }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                @if ($event->starts_at)
                                    <li class="flex items-center gap-[5px]">
                                        <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                        {{ \App\Support\Format::dateShort($event->starts_at) }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    {{-- Prezzo in basso a destra sopra il bottone (XD "25 €" @1724,250) --}}
                    <p class="absolute right-4 top-[118px] text-lg font-semibold leading-6 text-black">{{ $popupPrice }}</p>

                    <flux:button href="{{ route('carrello') }}" class="!ml-auto !mt-4 !flex !h-10 !w-[159px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">{{ __('events.go_to_cart') }}</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pop-up "Aggiunto agli eventi" (XD: "Pop-up evento partecipa") — gemello del pop-up carrello: stessa card 400x230 a destra, senza riga prezzo.
         Solo eventi con pill Partecipa (doppia cintura oltre alla guardia in joinEvent). --}}
    @if ($canJoin && $joinPopupOpen)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="{{ __('events.added_to_events') }}" x-data @keydown.escape.window="$wire.closeJoinPopup()">
            {{-- Overlay: click fuori dalla card chiude il pop-up --}}
            <div class="absolute inset-0 bg-black/30" wire:click="closeJoinPopup" aria-hidden="true"></div>

            <div class="{{ $px }} pointer-events-none relative">
                <div class="pointer-events-auto relative ml-auto mt-[116px] w-full max-w-[400px] rounded-[3px] border border-gray-150 bg-white p-4">
                    <h2 class="text-lg font-bold leading-6 text-brand-magenta">{{ __('events.added_to_events') }}</h2>

                    <flux:button variant="ghost" size="sm" square wire:click="closeJoinPopup" aria-label="{{ __('events.close') }}" class="!absolute !right-2 !top-2 !text-[#959595] hover:!bg-transparent hover:!text-ink">
                        <flux:icon.close class="h-[18px] w-[18px]" />
                    </flux:button>

                    <div class="mt-3 flex items-start gap-2.5">
                        <img src="{{ $event->heroImageUrl() }}" alt="{{ $event->title }}" class="h-[106px] w-[118px] shrink-0 rounded-[3px] object-cover">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-black">{{ $event->title }}</p>
                            <ul class="mt-4 space-y-1.5 text-[13px] font-semibold text-[#555555]">
                                @if ($event->starts_at)
                                    <li class="flex items-center gap-[5px]">
                                        <flux:icon.calendar class="h-[15px] w-[15px] shrink-0" />
                                        {{ \App\Support\Format::dateShort($event->starts_at) }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    {{-- TODO: pagina "I miei eventi" (l'XD punta a un artboard con la lista eventi dell'utente) --}}
                    <flux:button href="#" class="!ml-auto !mt-4 !flex !h-10 !w-[162px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#4FB9DB]">{{ __('events.go_to_events') }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
