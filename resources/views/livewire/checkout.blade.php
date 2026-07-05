{{-- Checkout — funnel a 3 step interni (XD: "Checkout" / "Checkout – 1" / "Checkout – 2"): stepper + card dati/pagamento + riepilogo ordine --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    // Contenitore card (direttiva "Rettangolo 399/796": bianco pieno, non l'op=0.5 del dump)
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
    // Input 606/295 x40 con bordo sottile #C8C8C8 (0.5px XD → 1px al 70%) e spazio a destra per la spunta
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!pr-10 [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- Stepper: linea 1px #DEDEDE a tutta larghezza (XD 142..1780) con i 3 tab sopra, centrati come gruppo --}}
        <div class="mx-auto w-full max-w-[1638px] pt-[70px]">
            <div class="relative">
                <div class="absolute inset-x-0 bottom-0 h-px bg-[#DEDEDE]" aria-hidden="true"></div>
                <div class="relative flex justify-center gap-[63px]">
                    {{-- Tab statici (si avanza solo con le CTA): attivo #68CDEB con sottolineatura 2.5px sovrapposta alla linea, gli altri #C8C8C8 --}}
                    @foreach ($steps as $n => $label)
                        <span wire:key="step-tab-{{ $n }}" class="relative pb-[10px] text-lg font-medium leading-none {{ $step === $n ? 'text-[#68CDEB] after:absolute after:inset-x-0 after:bottom-0 after:h-[2.5px] after:bg-[#68CDEB]' : 'text-[#C8C8C8]' }}">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="{{ $px }} pb-[140px]">
            @if ($step < 3)
                {{-- Step 1 e 2: blocco a due card (654 + 40 + 575 = 1269) centrato --}}
                <div class="mx-auto mt-[85px] flex w-full max-w-[1269px] flex-col gap-10 min-[87.5rem]:flex-row min-[87.5rem]:items-start">
                    <div class="w-full min-w-0 min-[87.5rem]:w-[654px] min-[87.5rem]:shrink-0">
                        @if ($step === 1)
                            {{-- Card "Verifica i tuoi dati personali": 5 campi precompilati con spunta ciano quando pieni --}}
                            <div class="{{ $card }} p-6 pb-8">
                                <h1 class="mt-6 text-2xl font-bold leading-none text-[#0D171A]">Verifica i tuoi dati personali</h1>

                                <div class="mt-1 space-y-4">
                                    @foreach ([
                                        ['model' => 'nome', 'label' => 'Nome *', 'type' => 'text'],
                                        ['model' => 'cognome', 'label' => 'Cognome *', 'type' => 'text'],
                                        ['model' => 'email', 'label' => 'Email *', 'type' => 'email'],
                                        ['model' => 'paese', 'label' => 'Paese', 'type' => 'text'],
                                        ['model' => 'cellulare', 'label' => 'Cellulare *', 'type' => 'tel'],
                                    ] as $field)
                                        <div wire:key="field-{{ $field['model'] }}">
                                            <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                            <div class="relative mt-[11px]">
                                                <flux:input type="{{ $field['type'] }}" wire:model.live="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                                @if ($this->{$field['model']} !== '')
                                                    <flux:icon.check class="pointer-events-none absolute right-5 top-1/2 !h-[14px] !w-[14px] -translate-y-1/2 text-[#68CDEB]" />
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-[21px] text-[11px] leading-[15px] text-[#959595]">Ti contatteremo solo in caso di aggiornamenti importanti o modifiche alla tua prenotazione</p>

                                <flux:button wire:click="goToStep(2)" class="mt-4 !h-10 !w-[197px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Prosegui l’acquisto</flux:button>
                            </div>
                        @else
                            {{-- Card "Seleziona un metodo di pagamento": opzione carta con sub-form + metodi alternativi --}}
                            <div class="{{ $card }} p-6">
                                <h1 class="mt-6 text-2xl font-bold leading-none text-[#0D171A]">Seleziona un metodo di pagamento</h1>

                                {{-- Opzione 1: riga-bottone; selezionata mostra il sub-form carta (nessun glifo radio in XD) --}}
                                {{-- Riga metodo: cerchio "check" XD r8 — selezionato #EDFF00 con check nero, altrimenti bianco bordo #C8C8C8 --}}
                                <flux:button variant="ghost" wire:click="selectPayment('carta')" class="mt-5 !h-auto !w-full !justify-start !p-0 !text-base !font-normal !text-[#0D171A] hover:!bg-transparent hover:!text-[#0D171A] [&>span]:flex [&>span]:items-center [&>span]:gap-[10px]">
                                    <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $paymentMethod === 'carta' ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white' }}">
                                        @if ($paymentMethod === 'carta')
                                            <flux:icon.check class="h-[10px] w-[10px] text-black" />
                                        @endif
                                    </span>
                                    Carta di credito o di debito
                                </flux:button>

                                @if ($paymentMethod === 'carta')
                                    <div class="mt-1 space-y-4">
                                        @foreach ([
                                            ['model' => 'cardHolder', 'label' => 'Titolare della carta *'],
                                            ['model' => 'cardNumber', 'label' => 'Numero della carta *'],
                                        ] as $field)
                                            <div wire:key="field-{{ $field['model'] }}">
                                                <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                                <div class="relative mt-[11px]">
                                                    <flux:input type="text" wire:model.live="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                                    @if ($this->{$field['model']} !== '')
                                                        <flux:icon.check class="pointer-events-none absolute right-5 top-1/2 !h-[14px] !w-[14px] -translate-y-1/2 text-[#68CDEB]" />
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach

                                        {{-- Scadenza + CVV affiancati (295x40, gap 16) --}}
                                        <div class="flex flex-col gap-4 sm:flex-row">
                                            @foreach ([
                                                ['model' => 'cardExpiry', 'label' => 'Data di scadenza *'],
                                                ['model' => 'cardCvv', 'label' => 'Codice di sicurezza *'],
                                            ] as $field)
                                                <div class="w-full sm:w-[295px]" wire:key="field-{{ $field['model'] }}">
                                                    <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                                    <div class="relative mt-[11px]">
                                                        <flux:input type="text" wire:model.live="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                                        @if ($this->{$field['model']} !== '')
                                                            <flux:icon.check class="pointer-events-none absolute right-5 top-1/2 !h-[14px] !w-[14px] -translate-y-1/2 text-[#68CDEB]" />
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <flux:button wire:click="goToStep(3)" class="mt-4 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Paga ora</flux:button>
                                @endif

                                <div class="mt-[15px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                {{-- Metodi alternativi (righe 15px, passo 36): cliccarne uno lo seleziona e nasconde il sub-form carta --}}
                                <div class="mt-1.5">
                                    @foreach ($altMethods as $method => $label)
                                        <flux:button variant="ghost" wire:click="selectPayment('{{ $method }}')" class="!h-9 !w-full !justify-start !p-0 !text-[15px] !font-normal !text-[#0D171A] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:gap-[10px]" wire:key="method-{{ $method }}">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $paymentMethod === $method ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white' }}">
                                                @if ($paymentMethod === $method)
                                                    <flux:icon.check class="h-[10px] w-[10px] text-black" />
                                                @endif
                                            </span>
                                            {{ $label }}
                                        </flux:button>
                                    @endforeach
                                </div>

                                @if ($paymentMethod !== 'carta')
                                    <p class="mt-3 text-[13px] leading-none text-[#959595]">Verrai reindirizzato al provider per completare il pagamento</p>
                                    {{-- TODO: integrazione Stripe/provider reale --}}
                                    <flux:button wire:click="goToStep(3)" class="mb-1 mt-4 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Paga ora</flux:button>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Card "Riepilogo dell'ordine" (575, condivisa dagli step 1 e 2): stessi dati del carrello --}}
                    <aside class="w-full min-w-0 min-[87.5rem]:w-[575px] min-[87.5rem]:shrink-0">
                        <div class="{{ $card }} pb-6">
                            <h2 class="px-6 pt-12 text-2xl font-bold leading-none text-[#0D171A]">Riepilogo dell’ordine</h2>

                            <div class="divide-y divide-[#E9E9E9] px-5">
                                @foreach ($items as $item)
                                    <div wire:key="summary-item-{{ $item['id'] }}" class="flex gap-5 py-[10px] first:pt-px last:pb-[13px]">
                                        {{-- Foto 167x111 con chip tipologia sovrapposta (dump: offset 36/45 dentro la foto) --}}
                                        <div class="relative shrink-0">
                                            <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[111px] w-[167px] rounded-[2px] object-cover">
                                            <span class="absolute left-9 top-[45px] flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>
                                        </div>

                                        {{-- Blocco info: titolo + righe meta come nel carrello (item 3 senza riga date; adulti + cane sulla stessa riga) --}}
                                        <div class="min-w-0 flex-1 pt-5">
                                            <h3 class="truncate text-base font-bold leading-none text-[#0D171A]">{{ $item['title'] }}</h3>
                                            <div class="mt-[9px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
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
                                                <div class="flex items-center">
                                                    <div class="flex w-[112px] items-center gap-2">
                                                        <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                        <span>{{ $this->guestsLabel($item['guests']) }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                        <span>{{ $this->dogsLabel($item['dogs']) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mx-4 h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                            {{-- Riga totale: 476 € a destra con nota tasse --}}
                            <div class="flex items-start justify-between px-6 pt-[46px]">
                                <span class="text-2xl font-bold leading-none text-[#0D171A]">Totale</span>
                                <div class="text-right">
                                    <div class="text-2xl font-bold leading-none text-[#0D171A]">{{ $total }} €</div>
                                    <div class="mt-[2px] text-xs leading-none text-[#627277]">Tasse e commissioni comprese</div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                {{-- Step 3 "Fatto!": heading ciano sopra la card riepilogo compatta --}}
                <h1 class="mt-[109px] text-center text-2xl font-bold leading-none text-[#68CDEB]">Grazie del tuo acquisto!</h1>

                <div class="mx-auto mt-2 w-full max-w-[575px] {{ $card }} pb-[30px]">
                    <p class="px-6 pt-11 text-xl font-normal leading-none text-[#555555]">Ecco il riepilogo, controlla l’email</p>

                    <div class="mt-1 divide-y divide-[#E9E9E9] px-5">
                        @foreach ($items as $item)
                            <div wire:key="done-item-{{ $item['id'] }}" class="relative flex gap-5 py-[10px] first:pt-1 last:pb-[13px]">
                                <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[111px] w-[167px] shrink-0 rounded-[2px] object-cover">

                                <div class="min-w-0 flex-1 pt-5">
                                    <h2 class="truncate pr-[100px] text-base font-bold leading-none text-[#0D171A]">{{ $item['title'] }}</h2>
                                    <div class="mt-[9px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
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
                                        <div class="flex items-center">
                                            <div class="flex w-[112px] items-center gap-2">
                                                <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                <span>{{ $this->guestsLabel($item['guests']) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                <span>{{ $this->dogsLabel($item['dogs']) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Chip tipologia: nello step 3 al bordo destro della riga, allineata al titolo --}}
                                <span class="absolute right-[11px] top-[30px] flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mx-4 h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                    {{-- CTA finali: coppia centrata (gap 24) — Home nera + acquisti brand-cyan #6CD1EF --}}
                    <div class="mt-[33px] flex flex-col items-center justify-center gap-6 sm:flex-row">
                        <flux:button href="{{ route('home') }}" class="!h-10 !w-[178px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">Torna alla Home</flux:button>
                        {{-- TODO: pagina acquisti profilo non ancora costruita --}}
                        <flux:button href="#" class="!h-10 !w-[192px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">Vai ai tuoi acquisti</flux:button>
                    </div>
                </div>
            @endif
        </div>
    </main>

    {{-- Footer slim (direttiva utente: pagine funnel carrello/checkout) --}}
    @include('partials.footer-minimal')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
