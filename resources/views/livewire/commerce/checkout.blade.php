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
                            <div class="{{ $card }} px-6 pb-8">
                                <h1 class="my-6 text-2xl font-bold leading-none text-[#0D171A]">{{ __('checkout.ui.verify_personal_data') }}</h1>

                                <div class="mt-1 space-y-4">
                                    @foreach ([
                                        ['model' => 'firstName', 'label' => __('checkout.ui.field_first_name'), 'type' => 'text'],
                                        ['model' => 'lastName', 'label' => __('checkout.ui.field_last_name'), 'type' => 'text'],
                                        ['model' => 'email', 'label' => __('checkout.ui.field_email'), 'type' => 'email'],
                                        ['model' => 'country', 'label' => __('checkout.ui.field_country'), 'type' => 'text'],
                                        ['model' => 'phone', 'label' => __('checkout.ui.field_phone'), 'type' => 'tel'],
                                        {{-- Campo solo flusso regalo: email a cui inviare la smartbox (assente in XD, necessario per l'invio reale) --}}
                                        ...($gift ? [['model' => 'recipientEmail', 'label' => __('checkout.ui.field_recipient_email'), 'type' => 'email']] : []),
                                    ] as $field)
                                        <div wire:key="field-{{ $field['model'] }}">
                                            <flux:label class="!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555]">{{ $field['label'] }}</flux:label>
                                            <div class="relative mt-[11px]">
                                                <flux:input type="{{ $field['type'] }}" wire:model.live="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                                @if ($this->{$field['model']} !== '')
                                                    <flux:icon.check class="pointer-events-none absolute right-5 top-1/2 !h-[14px] !w-[14px] -translate-y-1/2 text-[#68CDEB]" />
                                                @endif
                                            </div>
                                            <flux:error name="{{ $field['model'] }}" class="!mt-1 !pl-[15px] !text-xs" />
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-[21px] text-[11px] leading-[15px] text-[#959595]">{{ __('checkout.ui.contact_note') }}</p>

                                <flux:button wire:click="goToStep(2)" class="mt-4 !h-10 !w-[197px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('checkout.ui.continue_purchase') }}</flux:button>
                            </div>
                        @else
                            {{-- Card "Seleziona un metodo di pagamento": righe metodo (solo gateway abilitati) + element/bottoni del provider.
                                 Il watchdog sblocca "Paga ora" se il dispatch process-payment non trova alcun element montato --}}
                            <div class="{{ $card }} p-6" x-data="paymentWatchdog()" x-on:process-payment.window="start()">
                                <h1 class="mt-6 text-2xl font-bold leading-none text-[#0D171A]">{{ __('checkout.ui.select_payment_method') }}</h1>

                                @if (! $hasCardMethod && $altMethods === [])
                                    {{-- Nessun gateway abilitato/configurato: box cortese al posto delle righe, nessun crash --}}
                                    <div class="mb-2 mt-5 rounded-[3px] border border-[#E9E9E9] bg-[#F4F4F4] px-[15px] py-3 text-[13px] leading-5 text-[#555555]">{{ __('checkout.payment_unavailable') }}</div>
                                @endif

                                @if ($hasCardMethod)
                                    {{-- Opzione 1: riga-bottone carta; selezionata mostra il Payment Element (ex sub-form mock: obbligo PCI) --}}
                                    {{-- Riga metodo: cerchio "check" XD r8 — selezionato #EDFF00 con check nero, altrimenti bianco bordo #C8C8C8 --}}
                                    <flux:button variant="ghost" wire:click="selectPayment('card')" class="mt-5 !h-auto !w-full !justify-start !p-0 !text-base !font-normal !text-[#0D171A] hover:!bg-transparent hover:!text-[#0D171A] [&>span]:flex [&>span]:items-center [&>span]:gap-[10px]">
                                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $paymentMethod === 'card' ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white' }}">
                                            @if ($paymentMethod === 'card')
                                                <flux:icon.check class="h-[10px] w-[10px] text-black" />
                                            @endif
                                        </span>
                                        {{ \App\Enums\PaymentMethod::Card->label() }}
                                    </flux:button>

                                    @if ($paymentMethod === 'card')
                                        @if ($paymentUnavailable || $clientSecret === null)
                                            <div class="mt-4 rounded-[3px] border border-[#E9E9E9] bg-[#F4F4F4] px-[15px] py-3 text-[13px] leading-5 text-[#555555]">{{ __('checkout.payment_unavailable') }}</div>
                                        @else
                                            {{-- Payment Element (solo card), Appearance API allineata agli input Flux della pagina --}}
                                            <div wire:ignore wire:key="stripe-card-{{ $clientSecret }}" class="mt-4"
                                                x-data="stripePayment(@js($clientSecret), @js($stripeKey), { method: 'card', returnUrl: @js($returnUrl), incompleteMessage: @js(__('checkout.payment_incomplete')) })">
                                                <div x-ref="element"></div>
                                            </div>

                                            {{-- "Paga ora" spento finché l'Element non è montato (markElementReady dal JS): mai un click nel vuoto --}}
                                            <flux:button wire:click="processPayment" :disabled="$processing || ! $elementReady" class="mt-4 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A] disabled:!opacity-60">{{ __('checkout.ui.pay_now') }}</flux:button>
                                        @endif
                                    @endif

                                    <div class="mt-[15px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>
                                @endif

                                {{-- Metodi alternativi (righe 15px, passo 36): il container del provider appare sotto la riga selezionata --}}
                                <div class="mt-1.5">
                                    @foreach ($altMethods as $method)
                                        <flux:button variant="ghost" wire:click="selectPayment('{{ $method->value }}')" class="!h-9 !w-full !justify-start !p-0 !text-[15px] !font-normal !text-[#0D171A] hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:gap-[10px]" wire:key="method-{{ $method->value }}">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full {{ $paymentMethod === $method->value ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white' }}">
                                                @if ($paymentMethod === $method->value)
                                                    <flux:icon.check class="h-[10px] w-[10px] text-black" />
                                                @endif
                                            </span>
                                            {{ $method->label() }}
                                        </flux:button>

                                        @if ($paymentMethod === $method->value)
                                            @php $sessionReady = $method->gatewayCode() === 'stripe' ? $clientSecret !== null : $paypalOrderId !== null; @endphp

                                            @if ($paymentUnavailable || ! $sessionReady)
                                                <div wire:key="unavailable-{{ $method->value }}" class="my-3 rounded-[3px] border border-[#E9E9E9] bg-[#F4F4F4] px-[15px] py-3 text-[13px] leading-5 text-[#555555]">{{ __('checkout.payment_unavailable') }}</div>
                                            @elseif ($method->usesExpressCheckout())
                                                {{-- Apple/Google Pay: Express Checkout Element (bottone brand del wallet; fallback se il device non lo supporta) --}}
                                                <div wire:ignore wire:key="ece-{{ $method->value }}-{{ $clientSecret }}" class="my-3 max-w-[295px]"
                                                    x-data="stripeExpressCheckout(@js($clientSecret), @js($stripeKey), { wallet: @js($method === \App\Enums\PaymentMethod::ApplePay ? 'applePay' : 'googlePay'), returnUrl: @js($returnUrl), incompleteMessage: @js(__('checkout.payment_incomplete')) })">
                                                    <div x-ref="element"></div>
                                                    <p x-show="walletUnavailable" style="display: none;" class="text-[13px] leading-5 text-[#959595]">{{ __('payment.errors.wallet_unavailable') }}</p>
                                                </div>
                                            @elseif ($method === \App\Enums\PaymentMethod::Klarna)
                                                <p class="mt-2 text-[13px] leading-none text-[#959595]">{{ __('checkout.ui.redirect_note') }}</p>
                                                {{-- Payment Element (solo klarna): la conferma reindirizza a Klarna e torna sul return_url --}}
                                                <div wire:ignore wire:key="stripe-klarna-{{ $clientSecret }}" class="mt-3"
                                                    x-data="stripePayment(@js($clientSecret), @js($stripeKey), { method: 'klarna', returnUrl: @js($returnUrl), incompleteMessage: @js(__('checkout.payment_incomplete')) })">
                                                    <div x-ref="element"></div>
                                                </div>
                                                {{-- "Paga ora" spento finché l'Element non è montato (markElementReady dal JS): mai un click nel vuoto --}}
                                                <flux:button wire:click="processPayment" :disabled="$processing || ! $elementReady" class="mb-1 mt-4 !h-10 !w-[127px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A] disabled:!opacity-60">{{ __('checkout.ui.pay_now') }}</flux:button>
                                            @else
                                                {{-- PayPal: bottoni dell'SDK classico (la CTA del provider sostituisce "Paga ora") --}}
                                                <div wire:ignore wire:key="paypal-{{ $paypalOrderId }}" class="my-3 max-w-[295px]"
                                                    x-data="paypalButtons(@js($paypalOrderId), @js($paypalClientId), { errorMessage: @js(__('checkout.paypal_error')) })">
                                                    <div x-ref="element"></div>
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Card "Riepilogo dell'ordine" (575, condivisa dagli step 1 e 2): stessi dati del carrello --}}
                    <aside class="w-full min-w-0 min-[87.5rem]:w-[575px] min-[87.5rem]:shrink-0">
                        <div class="{{ $card }} pb-6">
                            <h2 class="px-6 pt-12 text-2xl font-bold leading-none text-[#0D171A]">{{ __('checkout.ui.order_summary') }}</h2>

                            <div class="divide-y divide-[#E9E9E9] px-5">
                                @foreach ($items as $item)
                                    <div wire:key="summary-item-{{ $item['id'] }}" class="flex gap-5 py-[10px] first:pt-px last:pb-[13px]">
                                        {{-- Foto 167x111 con chip tipologia sovrapposta (dump: offset 36/45 dentro la foto); chip = ProductType REALE del prodotto --}}
                                        <div class="relative shrink-0">
                                            <img src="{{ $item['photoUrl'] }}" alt="{{ $item['title'] }}" class="h-[111px] w-[167px] rounded-[2px] object-cover">
                                            @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
                                            <span class="absolute left-9 top-[45px] flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>
                                        </div>

                                        {{-- Blocco info: titolo + righe meta come nel carrello (le righe assenti fanno salire le successive) --}}
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
                                                        {{-- checkOut null (evento a data singola): solo il check-in --}}
                                                        <span>{{ $item['dates']['checkIn'] }}@if ($item['dates']['checkOut'] !== null) - {{ $item['dates']['checkOut'] }}@endif</span>
                                                    </div>
                                                @elseif ($item['serviceSlot'] !== null)
                                                    {{-- Riga giorno + orario del servizio al posto della riga date (come nel carrello) --}}
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                        <span>{{ $item['serviceSlot'] }}</span>
                                                    </div>
                                                @endif
                                                @if ($item['guests'] !== null || $item['animals'] !== null)
                                                    <div class="flex items-center">
                                                        @if ($item['guests'] !== null)
                                                            <div class="flex w-[112px] items-center gap-2">
                                                                <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                                <span>{{ \App\Support\Format::guests($item['guests']) }}</span>
                                                            </div>
                                                        @endif
                                                        @if ($item['animals'] !== null)
                                                            <div class="flex items-center gap-2">
                                                                <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                                <span>{{ \App\Support\Format::animals($item['animals']) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($gift)
                                {{-- Dedica e messaggio dalle options della riga regalo (XD y481/512, x=24 dal bordo card); righe mostrate solo se valorizzate --}}
                                @foreach ($items as $item)
                                    @if ($item['gift'] && ($item['giftDedication'] !== null || $item['giftMessage'] !== null))
                                        <div wire:key="summary-gift-{{ $item['id'] }}" class="px-6 pb-6 text-[15px] font-normal text-[#0D171A]">
                                            @if ($item['giftDedication'] !== null)
                                                <p class="leading-none">{{ __('checkout.ui.dedicated_to', ['name' => $item['giftDedication']]) }}</p>
                                            @endif
                                            @if ($item['giftMessage'] !== null)
                                                <p class="mt-4 leading-[21px]">{{ __('checkout.ui.message', ['message' => $item['giftMessage']]) }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            <div class="mx-4 h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                            {{-- Riga totale a destra con nota tasse (il "476 €" ripetuto nel mock XD regalo è un refuso del designer: somma reale) --}}
                            <div class="flex items-start justify-between px-6 pt-[46px]">
                                <span class="text-2xl font-bold leading-none text-[#0D171A]">{{ __('checkout.ui.total') }}</span>
                                <div class="text-right">
                                    <div class="text-2xl font-bold leading-none text-[#0D171A]">{{ \App\Support\Format::money($total) }}</div>
                                    <div class="mt-[2px] text-xs leading-none text-[#627277]">{{ __('checkout.ui.taxes_included') }}</div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            @else
                {{-- Step 3 "Fatto!": heading ciano sopra la card riepilogo compatta --}}
                <h1 class="mt-[109px] text-center text-2xl font-bold leading-none text-[#68CDEB]">{{ __('checkout.ui.thank_you') }}</h1>

                <div class="mx-auto mt-2 w-full max-w-[575px] {{ $card }} pb-[30px]">
                    @if ($gift)
                        {{-- Copy regalo (XD "Checkout – flusso regalo smartbox 3": 18px nero su due righe); email dalle options della riga regalo --}}
                        <p class="px-6 pt-11 text-lg font-normal leading-6 text-black">{{ __('checkout.ui.gift_sent', ['email' => $giftRecipientEmail]) }}<br>{{ __('checkout.ui.gift_sent_summary') }}</p>
                    @else
                        <p class="px-6 pt-11 text-xl font-normal leading-none text-[#555555]">{{ __('checkout.ui.check_email') }}</p>
                    @endif

                    <div class="mt-1 divide-y divide-[#E9E9E9] px-5">
                        @foreach ($items as $item)
                            <div wire:key="done-item-{{ $item['id'] }}" class="relative flex gap-5 py-[10px] first:pt-1 last:pb-[13px]">
                                <img src="{{ $item['photoUrl'] }}" alt="{{ $item['title'] }}" class="h-[111px] w-[167px] shrink-0 rounded-[2px] object-cover">

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
                                                {{-- checkOut null (evento a data singola): solo il check-in --}}
                                                <span>{{ $item['dates']['checkIn'] }}@if ($item['dates']['checkOut'] !== null) - {{ $item['dates']['checkOut'] }}@endif</span>
                                            </div>
                                        @elseif ($item['serviceSlot'] !== null)
                                            {{-- Riga giorno + orario del servizio al posto della riga date (come nel carrello) --}}
                                            <div class="flex items-center gap-2">
                                                <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                <span>{{ $item['serviceSlot'] }}</span>
                                            </div>
                                        @endif
                                        @if ($item['guests'] !== null || $item['animals'] !== null)
                                            <div class="flex items-center">
                                                @if ($item['guests'] !== null)
                                                    <div class="flex w-[112px] items-center gap-2">
                                                        <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                        <span>{{ \App\Support\Format::guests($item['guests']) }}</span>
                                                    </div>
                                                @endif
                                                @if ($item['animals'] !== null)
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                        <span>{{ \App\Support\Format::animals($item['animals']) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Chip tipologia: nello step 3 al bordo destro della riga, allineata al titolo --}}
                                @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
                                <span class="absolute right-[11px] top-[30px] flex h-[27px] items-center rounded-[3px] px-[10px] text-sm font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($gift)
                        {{-- Dedica e messaggio dalle options della riga regalo (XD y532/563, x=24 dal bordo card); righe mostrate solo se valorizzate --}}
                        @foreach ($items as $item)
                            @if ($item['gift'] && ($item['giftDedication'] !== null || $item['giftMessage'] !== null))
                                <div wire:key="done-gift-{{ $item['id'] }}" class="px-6 pb-6 text-[15px] font-normal text-[#0D171A]">
                                    @if ($item['giftDedication'] !== null)
                                        <p class="leading-none">Dedicato a: {{ $item['giftDedication'] }}</p>
                                    @endif
                                    @if ($item['giftMessage'] !== null)
                                        <p class="mt-4 leading-[21px]">Messaggio: {{ $item['giftMessage'] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    @endif

                    <div class="mx-4 h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                    {{-- CTA finali: coppia centrata (gap 24) — Home nera + acquisti brand-cyan #6CD1EF --}}
                    <div class="mt-[33px] flex flex-col items-center justify-center gap-6 sm:flex-row">
                        <flux:button href="{{ route('home') }}" class="!h-10 !w-[178px] !rounded-full !border-0 !bg-[#0D171A] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#0D171A]">{{ __('checkout.ui.back_home') }}</flux:button>
                        <flux:button href="{{ route('profilo.ordini') }}" class="!h-10 !w-[192px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">{{ __('checkout.ui.go_to_purchases') }}</flux:button>
                    </div>
                </div>
            @endif
        </div>
    </main>

    {{-- Footer slim (direttiva utente: pagine funnel carrello/checkout) --}}
    @include('partials.footer-minimal')
</div>
