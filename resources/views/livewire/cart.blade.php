{{-- Carrello — colonna card articoli + box riepilogo + pop-up "Modifica prenotazione" (XD: "Carrello" / symbol "Modifica prenotazione") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pb-[140px] pt-14">
            <div class="mx-auto w-full max-w-[1496px]">
                @if ($items === [])
                    {{-- Titolo stato vuoto: "Carrello" Nunito Bold 36 nero (artboard "Carrello vuoto", niente conteggio) --}}
                    <h1 class="text-4xl font-bold leading-none text-black">Carrello</h1>

                    {{-- Card vuota 865x305 centrata (stesso look dello stato vuoto preferiti).
                         Il doodle ciano a mano libera (Tracciato 654/655) è SALTATO: il Tracciato 655 arriva
                         con offset master corrotti nel .xd — stessa decisione presa su /preferiti. --}}
                    <div class="mx-auto mt-[37px] flex min-h-[305px] w-full max-w-[865px] flex-col items-center rounded-[3px] border border-[#E9E9E9] bg-white px-6 pt-14 text-center shadow-[0px_1px_10px_#0000001A]">
                        <h2 class="text-2xl font-bold leading-none text-[#68CDEB]">Non hai ancora prenotato attività</h2>
                        <p class="mt-10 text-lg font-medium leading-none text-black">Prepara i tuoi amici a quattro zampe: organizza la vostra prossima avventura.</p>
                        <flux:button href="{{ url('/eventi') }}" class="mt-[63px] !h-10 !w-[244px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">Esperienze pensate per te</flux:button>
                    </div>

                    {{-- Sezione "Le attività più amate": heading + linea a tutta larghezza + fila di 3 card preferiti (senza box contenitore) --}}
                    <h2 class="mt-[120px] text-2xl font-medium leading-none text-black">Le attività più amate su Animal-amo</h2>
                    <div class="mt-[22px] h-px w-full bg-[#E9E9E9]" aria-hidden="true"></div>

                    <div class="relative mt-[18px]">
                        {{-- TODO: carosello reale — frecce e puntini per ora solo visivi come gli altri TODO --}}
                        {{-- !absolute obbligatorio: flux:button porta già "relative" e in cascata vincerebbe su "absolute" --}}
                        <flux:button variant="ghost" square aria-label="Card precedenti" class="!absolute -left-[17px] top-1/2 !h-8 !w-8 !min-w-0 -translate-y-1/2 !p-0 hover:!bg-transparent max-[87.5rem]:hidden [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                            <flux:icon.chevron-left class="!h-5 !w-5 text-[#2B2B2B]" />
                        </flux:button>
                        <flux:button variant="ghost" square aria-label="Card successive" class="!absolute -right-[9px] top-1/2 !h-8 !w-8 !min-w-0 -translate-y-1/2 !p-0 hover:!bg-transparent max-[87.5rem]:hidden [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                            <flux:icon.chevron-right class="!h-5 !w-5 text-[#2B2B2B]" />
                        </flux:button>

                        {{-- 3 card = preferiti 1-3 (stesso passo 490/card 468 di /preferiti, ma appoggiate direttamente sulla pagina) --}}
                        <div class="grid grid-cols-1 gap-x-[22px] gap-y-4 md:grid-cols-2 min-[87.5rem]:mx-6 min-[87.5rem]:grid-cols-3">
                            @foreach ($suggestions as $item)
                                {{-- Cuore bianco che diventa giallo al click (stato locale, TODO backend reale) --}}
                                @include('partials.favorite-card', [
                                    'item' => $item,
                                    'wireKey' => 'suggestion-' . $item['id'],
                                    'heartActive' => in_array($item['id'], $suggestFavorites, true),
                                    'heartAction' => 'toggleSuggestionFavorite(' . $item['id'] . ')',
                                    'bagActive' => in_array($item['id'], $suggestInCart, true),
                                    'bagAction' => 'toggleSuggestionCart(' . $item['id'] . ')',
                                ])
                            @endforeach
                        </div>
                    </div>

                    {{-- Puntini paginazione 9px: attivo #2B2B2B, inattivi #DEDEDE --}}
                    <div class="mt-6 flex items-center justify-center gap-[10px]">
                        @foreach ([true, false, false] as $index => $active)
                            <flux:button variant="ghost" square wire:key="dot-{{ $index }}" aria-label="Pagina {{ $index + 1 }}" class="!h-[9px] !w-[9px] !min-w-0 !rounded-full !p-0 {{ $active ? '!bg-[#2B2B2B] hover:!bg-[#2B2B2B]' : '!bg-[#DEDEDE] hover:!bg-[#DEDEDE]' }}"></flux:button>
                        @endforeach
                    </div>
                @else
                    {{-- Titolo 20px regular grigio (non un H1 bold) con conteggio dinamico e singolare/plurale --}}
                    <h1 class="text-xl font-normal leading-none text-[#555555]">Carrello ({{ $count }} {{ $count === 1 ? 'articolo' : 'articoli' }})</h1>

                    <div class="mt-[54px] flex flex-col gap-[35px] min-[87.5rem]:flex-row min-[87.5rem]:items-start">
                        {{-- Colonna sinistra: card articoli 1062, gap verticale 20 --}}
                        <div class="min-w-0 flex-1 space-y-5">
                            @foreach ($items as $item)
                                {{-- Card articolo ~1062x223 ("Rettangolo 399": bg bianco pieno, non l'op=0.5 del dump);
                                     nel flusso regalo la card cresce in basso con pill validità + campi dedica (XD y251..~721) --}}
                                <article wire:key="cart-item-{{ $item['id'] }}" class="min-h-[223px] w-full rounded-[3px] border border-[#E9E9E9] bg-white p-[18px] shadow-[0px_1px_10px_#0000001A]">
                                    <div class="flex gap-[19px]">
                                    {{-- Foto 278x185 con chip tipologia sovrapposta (16px dentro la foto) --}}
                                    <div class="relative hidden shrink-0 sm:block">
                                        <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[185px] w-[278px] rounded-[2px] object-cover">
                                        @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
                                        <span class="absolute left-4 top-4 flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>
                                    </div>

                                    {{-- Blocco info: titolo + righe meta (la riga date manca quando 'dates' è null e le successive salgono, come nell'item 3 XD) --}}
                                    <div class="min-w-0 flex-1 pt-[30px]">
                                        <h2 class="truncate text-xl font-semibold leading-[27px] text-black">{{ $item['title'] }}</h2>
                                        <div class="mt-[14px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
                                            <div class="flex items-center gap-2">
                                                <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                                <span class="truncate">{{ $item['location'] }}</span>
                                            </div>
                                            @if ($item['dates'] !== null)
                                                <div class="flex items-center gap-2">
                                                    <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                    <span>{{ $item['dates']['checkIn'] }} - {{ $item['dates']['checkOut'] }}</span>
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-2">
                                                <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                <span>{{ $this->guestsLabel($item['guests']) }}</span>
                                            </div>
                                            {{-- Icona cane: nessuna heroicon a tema, uso la zampa custom del set XD --}}
                                            <div class="flex items-center gap-2">
                                                <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                <span>{{ $this->dogsLabel($item['dogs']) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Zona destra: 3 divisori verticali (x 720/820/920 dal bordo card) con prezzo / Modifica / Elimina centrati fra loro --}}
                                    <div class="mt-[71px] flex h-[50px] shrink-0 self-start">
                                        <div class="flex w-[100px] items-center justify-center border-l border-[#E9E9E9]">
                                            <span class="text-2xl font-bold leading-none text-[#0D171A]">{{ $item['price'] }} €</span>
                                        </div>
                                        <div class="flex w-[100px] items-center justify-center border-l border-[#E9E9E9]">
                                            <flux:button variant="ghost" wire:click="openEdit({{ $item['id'] }})" class="!h-auto !p-0 !text-sm !font-medium !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">Modifica</flux:button>
                                        </div>
                                        <div class="flex w-[124px] items-center justify-center border-l border-[#E9E9E9]">
                                            <flux:button variant="ghost" wire:click="removeItem({{ $item['id'] }})" class="!h-auto !p-0 !text-sm !font-medium !text-[#EA2E68] hover:!bg-transparent hover:!text-[#EA2E68]">Elimina</flux:button>
                                        </div>
                                    </div>
                                    </div>

                                    @if ($item['gift'] ?? false)
                                        {{-- Estensione regalo smartbox sotto l'area info (offset 6: pill/campi a x=24 dal bordo card come in XD) --}}
                                        <div class="ml-[6px] mt-6">
                                            {{-- Pill validità 409x47 --}}
                                            <div class="flex h-[47px] w-full max-w-[409px] items-center justify-center gap-[6px] rounded-[4px] border border-[#DEDEDE] px-4">
                                                <flux:icon.gift class="h-5 w-5 shrink-0 text-[#2B2B2B]" />
                                                <span class="truncate text-[17px] font-medium leading-none text-[#2B2B2B]">{{ $item['giftValidity'] }}</span>
                                            </div>

                                            {{-- Campi dedica: stile input checkout ma vuoti (placeholder #0D171A non corsivo) --}}
                                            {{-- TODO: persistenza regalo backend — dedica e messaggio vivono solo nello stato del componente --}}
                                            <flux:input wire:model="giftDedication.{{ $item['id'] }}" placeholder="Dedicato a" class="mt-4 !min-w-0 !max-w-[972px] !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#0D171A]" />
                                            {{-- flux:textarea NON ha wrapper (la radice È la textarea stessa): classi dirette, niente selettori [&_textarea] --}}
                                            <flux:textarea wire:model="giftMessage.{{ $item['id'] }}" placeholder="Messaggio" rows="3" resize="none" class="mt-4 !h-[100px] !w-full !max-w-[972px] !rounded-[3px] !border !border-[#C8C8C8]/70 !bg-white !px-[15px] !py-[11px] !text-[15px] !text-[#0D171A] !shadow-none !ring-0 placeholder:!text-[#0D171A]" />
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>

                        {{-- Box riepilogo 399 (stesso stile contenitore delle card); in modalità regalo è 399x269: niente blocco "Cancellazione gratuita" --}}
                        <aside class="w-full shrink-0 min-[87.5rem]:w-[399px]">
                            <div class="rounded-[3px] border border-[#E9E9E9] bg-white {{ $gift ? 'pb-[23px]' : 'pb-[34px]' }} shadow-[0px_1px_10px_#0000001A]">
                                <div class="flex items-start justify-between px-6 pt-10">
                                    <span class="text-sm leading-none text-[#555555]">Totale ({{ $count }} {{ $count === 1 ? 'articolo' : 'articoli' }})</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold leading-none text-[#0D171A]">{{ $total }} €</div>
                                        <div class="mt-[2px] text-xs leading-none text-[#627277]">Tasse e commissioni comprese</div>
                                    </div>
                                </div>

                                <div class="mx-[17px] mt-[5px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                {{-- TODO: campo inserimento coupon non in scope — per ora solo trigger visivo --}}
                                <flux:button variant="ghost" class="!h-[54px] !w-full !justify-start !rounded-none !px-[18px] !text-sm !font-bold !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB] [&>span]:flex [&>span]:items-center [&>span]:gap-[9px]">
                                    <flux:icon.coupon-code class="!h-[30px] !w-[30px] shrink-0" />
                                    Inserisci codice promozionale
                                </flux:button>

                                <div class="mx-[17px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                <div class="px-[15px] {{ $gift ? 'pt-[10px]' : 'pt-5' }}">
                                    <div class="flex items-center gap-5">
                                        <flux:icon.lock class="h-5 w-5 shrink-0 text-[#1E2E33]" />
                                        <span class="text-sm leading-none text-[#0D171A]">Metodo di pagamento sicuro</span>
                                    </div>
                                    @unless ($gift)
                                        <div class="mt-5 flex items-start gap-5">
                                            <flux:icon.calendar-return class="h-5 w-5 shrink-0 text-[#1E2E33]" />
                                            <span class="text-sm leading-[19px] text-[#627277]">Cancellazione gratuita<br>(Non oltre 2 settimane prima dell’evento)</span>
                                        </div>
                                    @endunless
                                </div>

                                <div class="mx-[17px] {{ $gift ? 'mt-[17px]' : 'mt-5' }} h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                <div class="{{ $gift ? 'mt-[23px]' : 'mt-[31px]' }} flex justify-center">
                                    @if ($gift)
                                        {{-- In modalità regalo la CTA salva dedica/messaggio in sessione e apre il checkout regalo (?regalo=1) --}}
                                        <flux:button wire:click="goToCheckout" class="!h-10 !w-[170px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Vai al checkout</flux:button>
                                    @else
                                        <flux:button href="{{ route('checkout') }}" class="!h-10 !w-[170px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Vai al checkout</flux:button>
                                    @endif
                                </div>
                            </div>
                        </aside>
                    </div>
                @endif
            </div>
        </div>
    </main>

    {{-- Footer slim (direttiva utente: pagine funnel carrello/checkout) --}}
    @include('partials.footer-minimal')

    {{-- Pop-up "Modifica prenotazione" (symbol XD, 3 stati): uno solo, alimentato dall'articolo selezionato --}}
    <flux:modal name="edit-booking" :closable="false" class="w-full !max-w-[491px] !rounded-[3px] !border !border-[#E9E9E9] bg-white !p-6 backdrop:!bg-black/30">
        @if ($editingItem !== null)
            <flux:heading level="2" class="!text-2xl !font-bold !text-[#0D171A]">Modifica prenotazione</flux:heading>

            <div class="mt-4 space-y-4">
                {{-- Campo date: solo per gli articoli con date (l'item 3 apre con soli Ospiti + Animali) --}}
                @if ($editCheckIn !== null)
                    <div class="rounded-[4px] border border-[#DEDEDE]">
                        <flux:button variant="ghost" wire:click="toggleField('date')" class="!h-[67px] !w-full !rounded-[4px] !p-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-stretch">
                            <span class="flex w-[222px] flex-col justify-center gap-[7px] pl-[15px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Check-in</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckIn }}</span>
                            </span>
                            <span class="w-px self-stretch bg-[#DEDEDE]" aria-hidden="true"></span>
                            <span class="flex flex-1 flex-col justify-center gap-[7px] pl-4">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Check-out</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $editCheckOut ?? '—' }}</span>
                            </span>
                        </flux:button>

                        @if ($expandedField === 'date')
                            {{-- Calendario inline (stato "date"): mese reale generato in PHP dal mese del check-in --}}
                            <div class="px-[10px] pb-4">
                                <div class="mt-2 flex items-center justify-center gap-6">
                                    <flux:button variant="ghost" square wire:click="previousMonth" aria-label="Mese precedente" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                        <flux:icon.chevron-left class="!h-[14px] !w-[14px] text-black" />
                                    </flux:button>
                                    <span class="w-[110px] text-center text-[15px] font-semibold leading-none text-black">{{ $calendarLabel }}</span>
                                    <flux:button variant="ghost" square wire:click="nextMonth" aria-label="Mese successivo" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                        <flux:icon.chevron-right class="!h-[14px] !w-[14px] text-black" />
                                    </flux:button>
                                </div>

                                <div class="mt-5 grid grid-cols-7 text-center text-[15px] leading-none text-[#555555]">
                                    @foreach (['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'] as $dow)
                                        <span wire:key="dow-{{ $dow }}">{{ $dow }}</span>
                                    @endforeach
                                </div>

                                {{-- Giorni: click → check-in, secondo click successivo → check-out; i giorni nell'intervallo hanno il cerchio giallo 28px --}}
                                <div class="mt-2">
                                    @foreach ($calendar as $weekIndex => $week)
                                        <div class="grid grid-cols-7" wire:key="week-{{ $weekIndex }}">
                                            @foreach ($week as $day)
                                                <div class="flex h-10 items-center justify-center" wire:key="day-{{ $day['date'] }}">
                                                    <flux:button variant="ghost" square wire:click="selectDay('{{ $day['date'] }}')" class="!h-7 !w-7 !min-w-0 !rounded-full !p-0 !text-[15px] !font-normal {{ $day['inRange'] ? '!bg-brand-yellow hover:!bg-brand-yellow' : 'hover:!bg-gray-100' }} {{ $day['inMonth'] ? '!text-[#2B2B2B]' : '!text-[#959595]' }} [&>span]:flex [&>span]:items-center [&>span]:justify-center">{{ $day['day'] }}</flux:button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Campo Ospiti --}}
                <div class="rounded-[4px] border border-[#DEDEDE]">
                    <flux:button variant="ghost" wire:click="toggleField('ospiti')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                            <span class="flex flex-col gap-[7px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Ospiti</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $this->guestsLabel($editGuests) }}</span>
                            </span>
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'ospiti' ? 'rotate-180' : '' }}" />
                    </flux:button>

                    @if ($expandedField === 'ospiti')
                        <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                        <div class="px-6 pb-3 pt-[10px]">
                            @foreach ([
                                ['key' => 'adulti', 'name' => 'Adulto', 'hint' => 'Età 17 - 99'],
                                ['key' => 'ragazzi', 'name' => 'Ragazzi', 'hint' => 'Età 8 - 16'],
                                ['key' => 'bambini', 'name' => 'Bambini', 'hint' => 'Fino a 7 anni'],
                            ] as $row)
                                <div class="flex h-[57px] items-center justify-between" wire:key="guest-row-{{ $row['key'] }}">
                                    <div>
                                        <div class="text-base leading-none text-[#0D171A]">{{ $row['name'] }}</div>
                                        <div class="mt-[6px] text-sm leading-none text-[#627277]">{{ $row['hint'] }}</div>
                                    </div>
                                    <div class="flex items-center">
                                        <flux:button variant="ghost" square wire:click="decrementGuest('{{ $row['key'] }}')" aria-label="Diminuisci {{ $row['name'] }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                            <flux:icon.minus class="!h-3 !w-3 text-black" />
                                        </flux:button>
                                        <span class="w-[42px] text-center text-base leading-none text-[#1E2E33]">{{ $editGuests[$row['key']] }}</span>
                                        <flux:button variant="ghost" square wire:click="incrementGuest('{{ $row['key'] }}')" aria-label="Aumenta {{ $row['name'] }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                            <flux:icon.plus class="!h-3 !w-3 text-black" />
                                        </flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Campo Animali (nessun master XD per lo stato aperto — derivato dallo stato ospiti) --}}
                <div class="rounded-[4px] border border-[#DEDEDE]">
                    <flux:button variant="ghost" wire:click="toggleField('animali')" class="!h-[67px] !w-full !rounded-[4px] !px-[15px] !py-0 !text-left hover:!bg-transparent [&>span]:flex [&>span]:h-full [&>span]:w-full [&>span]:items-center [&>span]:justify-between">
                            <span class="flex flex-col gap-[7px]">
                                <span class="text-[17px] font-medium leading-none text-[#2B2B2B]">Animali</span>
                                <span class="text-[17px] font-light leading-none text-[#2B2B2B]">{{ $this->dogsLabel($editDogs) }}</span>
                            </span>
                            <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-black {{ $expandedField === 'animali' ? 'rotate-180' : '' }}" />
                    </flux:button>

                    @if ($expandedField === 'animali')
                        <div class="h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                        <div class="px-6 pb-3 pt-[10px]">
                            <div class="flex h-[57px] items-center justify-between">
                                <div class="text-base leading-none text-[#0D171A]">Cani</div>
                                <div class="flex items-center">
                                    <flux:button variant="ghost" square wire:click="decrementDogs" aria-label="Diminuisci cani" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                        <flux:icon.minus class="!h-3 !w-3 text-black" />
                                    </flux:button>
                                    <span class="w-[42px] text-center text-base leading-none text-[#1E2E33]">{{ $editDogs }}</span>
                                    <flux:button variant="ghost" square wire:click="incrementDogs" aria-label="Aumenta cani" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                        <flux:icon.plus class="!h-3 !w-3 text-black" />
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer: Annulla scarta le copie di lavoro, Conferma le riversa nell'articolo --}}
            <div class="mt-8 flex items-center justify-end gap-6">
                <flux:button variant="ghost" wire:click="closeEdit" class="!h-auto !p-0 !text-lg !font-medium !text-[#959595] hover:!bg-transparent hover:!text-[#959595]">Annulla</flux:button>
                <flux:button wire:click="confirmEdit" class="!h-10 !w-[134px] !rounded-full !border-0 !bg-[#68CDEB] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#68CDEB]">Conferma</flux:button>
            </div>
        @endif
    </flux:modal>

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
