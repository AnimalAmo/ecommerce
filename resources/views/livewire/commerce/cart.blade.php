{{-- Carrello — colonna card articoli + box riepilogo + pop-up "Modifica prenotazione" (XD: "Carrello" / symbol "Modifica prenotazione") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Su mobile la pagina finisce sopra la barra CTA fissa + tabbar (XD app "Carrello - click 'procedi'") --}}
        <div class="{{ $px }} pb-[140px] pt-14 max-lg:pb-[70px] max-lg:pt-5">
            <div class="mx-auto w-full max-w-[1496px]">
                @if ($items === [])
                    {{-- Titolo stato vuoto: "Carrello" Nunito Bold 36 nero (artboard "Carrello vuoto", niente conteggio).
                         L'artboard app apre direttamente sulla card: su mobile il titolo non c'è. --}}
                    <h1 class="text-4xl font-bold leading-none text-black max-lg:hidden">{{ __('cart.ui.title') }}</h1>

                    {{-- Card vuota 865x305 centrata (stesso look dello stato vuoto preferiti).
                         Il doodle ciano a mano libera (Tracciato 654/655) è SALTATO: il Tracciato 655 arriva
                         con offset master corrotti nel .xd — stessa decisione presa su /preferiti. --}}
                    <div class="mx-auto mt-[37px] flex min-h-[305px] w-full max-w-[865px] flex-col items-center rounded-[3px] border border-[#E9E9E9] bg-white px-6 pt-14 text-center shadow-[0px_1px_10px_#0000001A] max-lg:hidden">
                        <h2 class="text-2xl font-bold leading-none text-[#68CDEB]">{{ __('cart.ui.empty_heading') }}</h2>
                        <p class="mt-10 text-lg font-medium leading-none text-black">{{ __('cart.ui.empty_text') }}</p>
                        {{-- route() e non url(): mcamara antepone il prefisso del locale, un href fisso lo perde
                             e il visitatore inglese finisce sulla pagina italiana. --}}
                        <flux:button href="{{ route('eventi') }}" class="mt-[63px] !h-10 !w-[244px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">{{ __('cart.ui.empty_cta') }}</flux:button>
                        {{-- Seconda CTA: finché i partner non pubblicano, /eventi è vuoto quanto il carrello,
                             e uno stato vuoto che rimanda a un altro stato vuoto non aiuta nessuno.
                             Animal Times ha articoli reali: è l'unica destinazione con contenuto. --}}
                        <flux:button variant="ghost" href="{{ route('news') }}" class="mt-4 !h-auto !p-0 !text-[15px] !font-bold !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">{{ __('cart.ui.empty_cta_news') }}</flux:button>
                    </div>

                    {{-- Stato vuoto mobile (artboard "Carrello vuoto"): card 343x267 allineata a sinistra
                         e freccia disegnata a mano, gemella di quella dei preferiti.
                         Anche qui la CTA dell'artboard ("Aggiungi al carrello") è l'etichetta del symbol
                         da cui è copiata: resta quella della pagina. --}}
                    <div class="relative min-h-[267px] rounded-[3px] border border-[#E9E9E9] bg-white px-[17px] pb-[66px] pt-8 shadow-[0px_1px_5px_#0000001A] lg:hidden">
                        <h2 class="text-xl font-bold leading-[27px] text-[#68CDEB]">{{ __('cart.ui.empty_heading') }}</h2>
                        <p class="mt-[15px] text-[15px] font-medium leading-[22px] text-[#0D171A]">{{ __('cart.ui.empty_text') }}</p>
                        <flux:button href="{{ route('eventi') }}" class="relative !z-[1] !mt-[22px] !h-[39px] !rounded-full !border-0 !bg-brand-cyan !px-[33px] !text-sm !font-bold !text-white !shadow-[0px_1px_5px_#0000001A] hover:!bg-brand-cyan">{{ __('cart.ui.empty_cta') }}</flux:button>
                        {{-- Wrapper: flux:button è inline-flex e senza blocco proprio starebbe in riga con la pill.
                             z-[1] come la CTA sopra — il doodle è decorativo ma copre la stessa area. --}}
                        <div class="relative z-[1] mt-3">
                            <flux:button variant="ghost" href="{{ route('news') }}" class="!h-auto !p-0 !text-sm !font-bold !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">{{ __('cart.ui.empty_cta_news') }}</flux:button>
                        </div>

                        @include('partials.cart-empty-doodle')
                    </div>

                    {{-- "Le attività più amate": UNA sola guardia per mobile e desktop.
                         A catalogo vuoto topFavorited() torna [] e il blocco desktop, che stava
                         fuori dalla guardia, disegnava titolo, riga, frecce e puntini sopra zero
                         card: la cornice di un carosello che non esiste. --}}
                    @if ($suggestions !== [])
                        {{-- Mobile: heading 15 bold e carosello orizzontale di card 280x200 --}}
                        <section class="lg:hidden">
                            <h2 class="mt-8 text-[15px] font-bold leading-none text-[#0D171A]">{{ __('cart.ui.most_loved') }}</h2>

                            {{-- Scorre fino al bordo schermo: -mr-4 annulla il padding del container --}}
                            <div class="-mr-4 mt-[17px] flex snap-x gap-4 overflow-x-auto pb-1">
                                @foreach ($suggestions as $item)
                                    @include('partials.most-loved-card-mobile', [
                                        'item' => $item,
                                        'heartActive' => in_array($item['id'], $suggestFavorites, true),
                                        'heartAction' => "toggleSuggestionFavorite('" . $item['id'] . "')",
                                    ])
                                @endforeach
                            </div>
                        </section>

                        {{-- Sezione "Le attività più amate": heading + linea a tutta larghezza + card reali (top prodotti per numero di preferiti) --}}
                        <h2 class="mt-[120px] text-2xl font-medium leading-none text-black max-lg:hidden">{{ __('cart.ui.most_loved') }}</h2>
                        <div class="mt-[22px] h-px w-full bg-[#E9E9E9] max-lg:hidden" aria-hidden="true"></div>

                        <div class="relative mt-[18px] max-lg:hidden">
                            {{-- TODO: carosello reale — frecce e puntini per ora solo visivi come gli altri TODO --}}
                            {{-- !absolute obbligatorio: flux:button porta già "relative" e in cascata vincerebbe su "absolute" --}}
                            <flux:button variant="ghost" square aria-label="{{ __('cart.ui.prev_cards') }}" class="!absolute -left-[17px] top-1/2 !h-8 !w-8 !min-w-0 -translate-y-1/2 !p-0 hover:!bg-transparent max-[87.5rem]:hidden [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                <flux:icon.chevron-left class="!h-5 !w-5 text-[#2B2B2B]" />
                            </flux:button>
                            <flux:button variant="ghost" square aria-label="{{ __('cart.ui.next_cards') }}" class="!absolute -right-[9px] top-1/2 !h-8 !w-8 !min-w-0 -translate-y-1/2 !p-0 hover:!bg-transparent max-[87.5rem]:hidden [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                <flux:icon.chevron-right class="!h-5 !w-5 text-[#2B2B2B]" />
                            </flux:button>

                            {{-- Fino a 3 card = top preferiti reali (stesso passo 490/card 468 di /preferiti, ma appoggiate direttamente sulla pagina) --}}
                            <div class="grid grid-cols-1 gap-x-[22px] gap-y-4 md:grid-cols-2 min-[87.5rem]:mx-6 min-[87.5rem]:grid-cols-3">
                                @foreach ($suggestions as $item)
                                    {{-- Cuore bianco che diventa giallo al click (stato locale, TODO backend reale) --}}
                                    @include('partials.favorite-card', [
                                        'item' => $item,
                                        'wireKey' => 'suggestion-' . $item['id'],
                                        'heartActive' => in_array($item['id'], $suggestFavorites, true),
                                        'heartAction' => "toggleSuggestionFavorite('" . $item['id'] . "')",
                                        'bagActive' => in_array($item['id'], $suggestInCart, true),
                                        'bagAction' => "toggleSuggestionCart('" . $item['id'] . "')",
                                    ])
                                @endforeach
                            </div>
                        </div>

                        {{-- Puntini paginazione 9px (uno per card reale): attivo #2B2B2B, inattivi #DEDEDE --}}
                        <div class="mt-6 flex items-center justify-center gap-[10px] max-lg:hidden">
                            @foreach ($suggestions as $index => $suggested)
                                <flux:button variant="ghost" square wire:key="dot-{{ $index }}" aria-label="{{ __('cart.ui.page_number', ['number' => $index + 1]) }}" class="!h-[9px] !w-[9px] !min-w-0 !rounded-full !p-0 {{ $index === 0 ? '!bg-[#2B2B2B] hover:!bg-[#2B2B2B]' : '!bg-[#DEDEDE] hover:!bg-[#DEDEDE]' }}"></flux:button>
                            @endforeach
                        </div>
                    @endif
                @else
                    {{-- Titolo 20px regular grigio (non un H1 bold) con conteggio dinamico e singolare/plurale;
                         su mobile scende a 14px (XD app: "Carrello (2 prodotto)") --}}
                    {{-- Su mobile "Carrello" è 18 semibold e il conteggio fra parentesi resta 14 --}}
                    <h1 class="text-xl font-normal leading-none text-[#555555] max-lg:text-lg max-lg:font-semibold">{{ __('cart.ui.title') }} <span class="max-lg:text-sm max-lg:font-normal">({{ $count }} {{ $count === 1 ? __('cart.ui.item_one') : __('cart.ui.item_many') }})</span></h1>

                    <div class="mt-[54px] flex flex-col gap-[35px] min-[87.5rem]:flex-row min-[87.5rem]:items-start max-lg:mt-5">
                        {{-- Colonna sinistra: card articoli 1062, gap verticale 20 --}}
                        <div class="min-w-0 flex-1 space-y-5 max-lg:space-y-0">
                            @foreach ($items as $item)
                                {{-- Card articolo ~1062x223 ("Rettangolo 399": bg bianco pieno, non l'op=0.5 del dump);
                                     nel flusso regalo la card cresce in basso con pill validità + campi dedica (XD y251..~721) --}}
                                <article wire:key="cart-item-{{ $item['id'] }}" class="min-h-[223px] w-full rounded-[3px] border border-[#E9E9E9] bg-white p-[18px] shadow-[0px_1px_10px_#0000001A] max-lg:hidden">
                                    <div class="flex gap-[19px]">
                                    {{-- Foto 278x185 con chip tipologia sovrapposta (16px dentro la foto); chip = ProductType REALE del prodotto --}}
                                    <div class="relative hidden shrink-0 sm:block">
                                        <img src="{{ $item['photoUrl'] }}" alt="{{ $item['title'] }}" class="h-[185px] w-[278px] rounded-[2px] object-cover">
                                        @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
                                        <span class="absolute left-4 top-4 flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>
                                    </div>

                                    {{-- Blocco info: titolo + righe meta (le righe assenti — date, ospiti — fanno salire le successive, come nell'item 3 XD) --}}
                                    <div class="min-w-0 flex-1 pt-[30px]">
                                        <h2 class="truncate text-xl font-semibold leading-[27px] text-black">{{ $item['title'] }}</h2>
                                        <div class="mt-[14px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
                                            {{-- Vuota per le smartbox partner (nessuna audience): riga nascosta --}}
                                            @if ($item['location'] !== '')
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                                    <span class="truncate">{{ $item['location'] }}</span>
                                                </div>
                                            @endif
                                            @if ($item['dates'] !== null)
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                    {{-- checkOut null (evento a data singola): solo il check-in --}}
                                                    <span>{{ $item['dates']['checkIn'] }}@if ($item['dates']['checkOut'] !== null) - {{ $item['dates']['checkOut'] }}@endif</span>
                                                </div>
                                            @elseif ($item['serviceSlot'] !== null)
                                                {{-- Riga giorno + orario del servizio ('17/12/2023, 10:00 - 16:00') al posto della riga date --}}
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                    <span>{{ $item['serviceSlot'] }}</span>
                                                </div>
                                            @endif
                                            @if ($item['guests'] !== null)
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                    <span>{{ \App\Support\Format::guests($item['guests']) }}</span>
                                                </div>
                                            @endif
                                            @if ($item['animals'] !== null)
                                                {{-- Icona cane: nessuna heroicon a tema, uso la zampa custom del set XD --}}
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                    <span>{{ \App\Support\Format::animals($item['animals']) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Zona destra: 3 divisori verticali (x 720/820/920 dal bordo card) con prezzo / Modifica / Elimina centrati fra loro --}}
                                    <div class="mt-[71px] flex h-[50px] shrink-0 self-start">
                                        <div class="flex w-[100px] items-center justify-center border-l border-[#E9E9E9]">
                                            <span class="text-2xl font-bold leading-none text-[#0D171A]">{{ \App\Support\Format::money($item['price']) }}</span>
                                        </div>
                                        <div class="flex w-[100px] items-center justify-center border-l border-[#E9E9E9]">
                                            {{-- Eventi: data fissa e 1 partecipante, nessuna Modifica --}}
                                            @if ($item['family'] !== 'event')
                                                <flux:button variant="ghost" wire:click="openEdit('{{ $item['id'] }}')" class="!h-auto !p-0 !text-sm !font-medium !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">{{ __('cart.ui.edit') }}</flux:button>
                                            @endif
                                        </div>
                                        <div class="flex w-[124px] items-center justify-center border-l border-[#E9E9E9]">
                                            <flux:button variant="ghost" wire:click="removeItem('{{ $item['id'] }}')" class="!h-auto !p-0 !text-sm !font-medium !text-[#EA2E68] hover:!bg-transparent hover:!text-[#EA2E68]">{{ __('cart.ui.remove') }}</flux:button>
                                        </div>
                                    </div>
                                    </div>

                                    @if ($item['gift'])
                                        {{-- Estensione regalo smartbox sotto l'area info (offset 6: pill/campi a x=24 dal bordo card come in XD) --}}
                                        <div class="ml-[6px] mt-6">
                                            {{-- Pill validità 409x47 --}}
                                            <div class="flex h-[47px] w-full max-w-[409px] items-center justify-center gap-[6px] rounded-[4px] border border-[#DEDEDE] px-4">
                                                <flux:icon.gift class="h-5 w-5 shrink-0 text-[#2B2B2B]" />
                                                <span class="truncate text-[17px] font-medium leading-none text-[#2B2B2B]">{{ $item['giftValidity'] }}</span>
                                            </div>

                                            {{-- Campi dedica: stile input checkout ma vuoti (placeholder #0D171A non corsivo);
                                                 persistiti sulla riga (options.gift) alla CTA "Vai al checkout" --}}
                                            <flux:input wire:model="giftDedication.{{ $item['id'] }}" placeholder="{{ __('cart.ui.gift_dedication_placeholder') }}" class="mt-4 !min-w-0 !max-w-[972px] !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#0D171A]" />
                                            {{-- flux:textarea NON ha wrapper (la radice È la textarea stessa): classi dirette, niente selettori [&_textarea] --}}
                                            <flux:textarea wire:model="giftMessage.{{ $item['id'] }}" placeholder="{{ __('cart.ui.gift_message_placeholder') }}" rows="3" resize="none" class="mt-4 !h-[100px] !w-full !max-w-[972px] !rounded-[3px] !border !border-[#C8C8C8]/70 !bg-white !px-[15px] !py-[11px] !text-[15px] !text-[#0D171A] !shadow-none !ring-0 placeholder:!text-[#0D171A]" />
                                        </div>
                                    @endif
                                </article>

                                {{-- Card mobile (XD app "Carrello - click 'procedi'"): foto a tutta larghezza,
                                     striscia scura col prezzo e colonna Elimina rossa appoggiate sul fondo della
                                     foto, righe meta sotto. Niente "Modifica": l'XD app non la prevede. --}}
                                @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
                                <article wire:key="cart-item-mobile-{{ $item['id'] }}" class="w-full border-b border-[#E9E9E9] py-6 first:pt-0 lg:hidden">
                                    {{-- XD: box 308x104 (x16..324) con la foto clippata dentro dalla maschera, velo
                                         scuro #232A2C 40%, tag in alto a sinistra e prezzo al centro; di fianco la
                                         colonna Elimina 30x104 (x328..358). --}}
                                    <div class="flex items-stretch gap-1">
                                        <div class="relative h-[104px] min-w-0 flex-1 overflow-hidden rounded-[4px]">
                                            <img src="{{ $item['photoUrl'] }}" alt="{{ $item['title'] }}" class="absolute inset-0 h-full w-full object-cover">
                                            <div class="absolute inset-0 bg-[#232A2C]/40" aria-hidden="true"></div>
                                            <span class="absolute left-2 top-3 flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>
                                            <span class="absolute inset-0 flex items-center justify-center text-xl font-bold text-white">{{ \App\Support\Format::money($item['price']) }}</span>
                                        </div>
                                        <flux:button wire:click="removeItem('{{ $item['id'] }}')" square aria-label="{{ __('cart.ui.remove') }}" class="!h-[104px] !w-[30px] !shrink-0 !rounded-[3px] !border !border-[#E8E8E8] !bg-[#FAE9E7] !p-0 !text-[#DC5A48] !shadow-none [&>span]:!flex [&>span]:!h-full [&>span]:!items-center [&>span]:!justify-center">
                                            <flux:icon.trash class="h-4 w-4" />
                                        </flux:button>
                                    </div>

                                    {{-- Righe meta: titolo bold 14 col pin, poi date/ospiti/animali 13 regular --}}
                                    <div class="mt-[15px] space-y-[13px] text-[13px] font-normal leading-none text-[#0D171A]">
                                        <div class="flex items-center gap-2 text-sm font-bold">
                                            <flux:icon.pin class="h-[13px] w-[13px] shrink-0" />
                                            <span class="truncate">{{ $item['title'] }}</span>
                                        </div>
                                        @if ($item['dates'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.calendar class="h-[13px] w-[13px] shrink-0" />
                                                <span>{{ $item['dates']['checkIn'] }}@if ($item['dates']['checkOut'] !== null) - {{ $item['dates']['checkOut'] }}@endif</span>
                                            </div>
                                        @elseif ($item['serviceSlot'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.calendar class="h-[13px] w-[13px] shrink-0" />
                                                <span>{{ $item['serviceSlot'] }}</span>
                                            </div>
                                        @endif
                                        @if ($item['guests'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.user class="!h-[13px] !w-[13px] shrink-0" />
                                                <span>{{ \App\Support\Format::guests($item['guests']) }}</span>
                                            </div>
                                        @endif
                                        @if ($item['animals'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.animal class="h-[13px] w-[13px] shrink-0" />
                                                <span>{{ \App\Support\Format::animals($item['animals']) }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($item['gift'])
                                        {{-- Estensione regalo: l'XD app non la disegna, riuso i campi desktop impilati --}}
                                        <div class="mt-5">
                                            <div class="flex h-[47px] w-full items-center justify-center gap-[6px] rounded-[4px] border border-[#DEDEDE] px-4">
                                                <flux:icon.gift class="h-5 w-5 shrink-0 text-[#2B2B2B]" />
                                                <span class="truncate text-[15px] font-medium leading-none text-[#2B2B2B]">{{ $item['giftValidity'] }}</span>
                                            </div>
                                            <flux:input wire:model="giftDedication.{{ $item['id'] }}" placeholder="{{ __('cart.ui.gift_dedication_placeholder') }}" class="mt-4 !min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#0D171A]" />
                                            <flux:textarea wire:model="giftMessage.{{ $item['id'] }}" placeholder="{{ __('cart.ui.gift_message_placeholder') }}" rows="3" resize="none" class="mt-4 !h-[100px] !w-full !rounded-[3px] !border !border-[#C8C8C8]/70 !bg-white !px-[15px] !py-[11px] !text-[15px] !text-[#0D171A] !shadow-none !ring-0 placeholder:!text-[#0D171A]" />
                                        </div>
                                    @endif
                                </article>
                            @endforeach

                        </div>

                        {{-- Box riepilogo 399 (stesso stile contenitore delle card); in modalità regalo è 399x269: niente blocco "Cancellazione gratuita".
                             Su mobile non esiste: l'XD app porta il totale nel checkout e lascia solo la CTA fissa. --}}
                        <aside class="w-full shrink-0 min-[87.5rem]:w-[399px] max-lg:hidden">
                            <div class="rounded-[3px] border border-[#E9E9E9] bg-white {{ $gift ? 'pb-[23px]' : 'pb-[34px]' }} shadow-[0px_1px_10px_#0000001A]">
                                <div class="flex items-start justify-between px-6 pt-10">
                                    <span class="text-sm leading-none text-[#555555]">{{ __('cart.ui.total') }} ({{ $count }} {{ $count === 1 ? __('cart.ui.item_one') : __('cart.ui.item_many') }})</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold leading-none text-[#0D171A]">{{ \App\Support\Format::money($total) }}</div>
                                        <div class="mt-[2px] text-xs leading-none text-[#627277]">{{ __('cart.ui.taxes_included') }}</div>
                                    </div>
                                </div>

                                <div class="mx-[17px] mt-[5px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                {{-- TODO: campo inserimento coupon non in scope — per ora solo trigger visivo --}}
                                <flux:button variant="ghost" class="!h-[54px] !w-full !justify-start !rounded-none !px-[18px] !text-sm !font-bold !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB] [&>span]:flex [&>span]:items-center [&>span]:gap-[9px]">
                                    <flux:icon.coupon-code class="!h-[30px] !w-[30px] shrink-0" />
                                    {{ __('cart.ui.promo_code') }}
                                </flux:button>

                                <div class="mx-[17px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                <div class="px-[15px] {{ $gift ? 'pt-[10px]' : 'pt-5' }}">
                                    <div class="flex items-center gap-5">
                                        <flux:icon.lock class="h-5 w-5 shrink-0 text-[#1E2E33]" />
                                        <span class="text-sm leading-none text-[#0D171A]">{{ __('cart.ui.secure_payment') }}</span>
                                    </div>
                                    @unless ($gift)
                                        <div class="mt-5 flex items-start gap-5">
                                            <flux:icon.calendar-return class="h-5 w-5 shrink-0 text-[#1E2E33]" />
                                            <span class="text-sm leading-[19px] text-[#627277]">{{ __('cart.ui.free_cancellation') }}<br>{{ __('cart.ui.free_cancellation_note') }}</span>
                                        </div>
                                    @endunless
                                </div>

                                <div class="mx-[17px] {{ $gift ? 'mt-[17px]' : 'mt-5' }} h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                <div class="{{ $gift ? 'mt-[23px]' : 'mt-[31px]' }} flex justify-center">
                                    @if ($gift)
                                        {{-- In modalità regalo la CTA persiste dedica/messaggio sulle righe e apre il checkout regalo (?regalo=1) --}}
                                        <flux:button wire:click="goToCheckout" class="!h-10 !w-[170px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('cart.ui.go_to_checkout') }}</flux:button>
                                    @else
                                        <flux:button href="{{ route('checkout') }}" class="!h-10 !w-[170px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('cart.ui.go_to_checkout') }}</flux:button>
                                    @endif
                                </div>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
        </div>
    </main>

    {{-- Barra fissa sopra la tabbar, solo mobile: Totale (Nunito Bold 20) e CTA
         "Button azzurro" 343x39 r19 #6CD1EF (XD app "Carrello - click 'procedi'") --}}
    @if ($items !== [])
        <div class="fixed inset-x-0 bottom-20 z-30 bg-white px-4 pb-3 pt-4 shadow-[0px_-3px_6px_#00000029] lg:hidden">
            {{-- Codice promozionale (XD app: box 343x32 r4 #F2F2F2, icona coupon e testo 14 #959595).
                 TODO: applicazione del coupon non in scope, come sul desktop. --}}
            <div class="mb-4 flex h-8 w-full items-center gap-2 rounded-[4px] bg-[#F2F2F2] px-3">
                <flux:icon.coupon-ticket class="h-5 w-[13px] shrink-0 text-[#959595]" />
                <span class="truncate text-sm tracking-[0.025em] text-[#959595]">{{ __('cart.ui.promo_code') }}</span>
            </div>
            <div class="flex items-center justify-between text-xl font-bold text-black">
                <span>{{ __('cart.ui.total') }}</span>
                <span>{{ \App\Support\Format::money($total) }}</span>
            </div>
            @if ($gift)
                <flux:button wire:click="goToCheckout" class="mt-3 !h-[39px] !w-full !rounded-[19px] !border-0 !bg-brand-cyan !text-[15px] !font-semibold !text-white !shadow-none hover:!bg-brand-cyan">{{ __('cart.ui.proceed_checkout') }}</flux:button>
            @else
                <flux:button href="{{ route('checkout') }}" class="mt-3 !h-[39px] !w-full !rounded-[19px] !border-0 !bg-brand-cyan !text-[15px] !font-semibold !text-white !shadow-none hover:!bg-brand-cyan">{{ __('cart.ui.proceed_checkout') }}</flux:button>
            @endif
        </div>
    @endif

    {{-- Footer slim (direttiva utente: pagine funnel carrello/checkout); su mobile al suo posto c'è la tabbar --}}
    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>

    {{-- Tabbar mobile: il carrello usa il footer minimal, che non la include --}}
    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>

    {{-- Pop-up "Modifica prenotazione" (symbol XD): uno solo, accordion per famiglia della riga selezionata
         (structure = date/ospiti/animali, service = giorno/orari/animali, activity = ospiti/animali, smartbox = animali) --}}
    <flux:modal name="edit-booking" :closable="false" class="w-full !max-w-[491px] !rounded-[3px] !border !border-[#E9E9E9] bg-white !p-6 backdrop:!bg-black/30">
        @if ($editingItem !== null)
            <flux:heading level="2" class="!text-2xl !font-bold !text-[#0D171A]">{{ __('cart.ui.edit_booking') }}</flux:heading>

            <div class="mt-4 space-y-4">
                @if ($editingFamily === 'structure')
                    {{-- Campo date (range check-in/check-out) --}}
                    <div class="rounded-[4px] border border-[#DEDEDE]">
                        <flux:button variant="ghost" wire:click="toggleField('date')" class="!h-[67px] !w-full !rounded-[4px] !p-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-stretch">
                            <span class="flex w-[222px] flex-col justify-center gap-[7px] pl-[15px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.check_in') }}</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckIn }}</span>
                            </span>
                            <span class="w-px self-stretch bg-[#DEDEDE]" aria-hidden="true"></span>
                            <span class="flex flex-1 flex-col justify-center gap-[7px] pl-4">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.check_out') }}</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckOut ?? '—' }}</span>
                            </span>
                        </flux:button>

                        @if ($expandedField === 'date')
                            {{-- Calendario inline condiviso (giorni chiusi della struttura + passati disabilitati) --}}
                            <div class="px-[10px] pb-4">
                                @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                            </div>
                        @endif
                    </div>
                @endif

                @if ($editingFamily === 'service')
                    {{-- Campo Giorno (calendario a giorno singolo, stesso stile del campo date) --}}
                    <div class="rounded-[4px] border border-[#DEDEDE]">
                        <flux:button variant="ghost" wire:click="toggleField('date')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                            <span class="flex flex-col gap-[7px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.day') }}</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckIn }}</span>
                            </span>
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'date' ? 'rotate-180' : '' }}" />
                        </flux:button>

                        @if ($expandedField === 'date')
                            <div class="px-[10px] pb-4">
                                @include('partials.booking.calendar', ['calendar' => $calendar, 'calendarLabel' => $calendarLabel])
                            </div>
                        @endif
                    </div>

                    {{-- Campo Orari (nuovo accordion: l'XD non definisce l'editing del servizio — estrapolazione ratificata) --}}
                    <div class="rounded-[4px] border border-[#DEDEDE]">
                        <flux:button variant="ghost" wire:click="toggleField('orari')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                            <span class="flex flex-col gap-[7px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.times') }}</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editTimeFrom }} - {{ $editTimeTo }}</span>
                            </span>
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'orari' ? 'rotate-180' : '' }}" />
                        </flux:button>

                        @if ($expandedField === 'orari')
                            <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                            <div class="flex gap-4 px-6 pb-4 pt-[10px]">
                                <div class="flex-1">
                                    <div class="text-sm leading-none text-[#627277]">{{ __('cart.ui.from') }}</div>
                                    <flux:select wire:model="editTimeFrom" class="mt-2">
                                        @foreach ($bookingHours as $hour)
                                            <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm leading-none text-[#627277]">{{ __('cart.ui.to') }}</div>
                                    <flux:select wire:model="editTimeTo" class="mt-2">
                                        @foreach ($bookingHours as $hour)
                                            <flux:select.option value="{{ $hour }}">{{ $hour }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (in_array($editingFamily, ['structure', 'activity'], true))
                    {{-- Campo Ospiti --}}
                    <div class="rounded-[4px] border border-[#DEDEDE]">
                        <flux:button variant="ghost" wire:click="toggleField('ospiti')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                                <span class="flex flex-col gap-[7px]">
                                    <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.guests') }}</span>
                                    <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ \App\Support\Format::guests($editGuests) }}</span>
                                </span>
                                <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'ospiti' ? 'rotate-180' : '' }}" />
                        </flux:button>

                        @if ($expandedField === 'ospiti')
                            <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                            <div class="px-6 pb-3 pt-[10px]">
                                @include('partials.booking.guest-steppers', ['guests' => $editGuests, 'guestsAtMax' => $guestsAtMax])
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Campo Animali (tutte le famiglie modificabili; nessun master XD per lo stato aperto — derivato dallo stato ospiti) --}}
                <div class="rounded-[4px] border border-[#DEDEDE]">
                    <flux:button variant="ghost" wire:click="toggleField('animali')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                            <span class="flex flex-col gap-[7px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">{{ __('cart.ui.animals') }}</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ \App\Support\Format::animals($editAnimals) }}</span>
                            </span>
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'animali' ? 'rotate-180' : '' }}" />
                    </flux:button>

                    @if ($expandedField === 'animali')
                        <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                        <div class="px-6 pb-3 pt-[10px]">
                            @include('partials.booking.animal-stepper', ['animals' => $editAnimals, 'animalsAtMax' => $animalsAtMax])
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer: Annulla scarta le copie di lavoro, Conferma le riversa nella riga (rivalidazione+riprezzo server) --}}
            <div class="mt-8 flex items-center justify-end gap-6">
                <flux:button variant="ghost" wire:click="closeEdit" class="!h-auto !p-0 !text-lg !font-medium !text-[#959595] hover:!bg-transparent hover:!text-[#959595]">{{ __('cart.ui.cancel') }}</flux:button>
                <flux:button wire:click="confirmEdit" class="!h-10 !w-[134px] !rounded-full !border-0 !bg-[#68CDEB] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#68CDEB]">{{ __('cart.ui.confirm') }}</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
