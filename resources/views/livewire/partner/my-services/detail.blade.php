{{-- Dettaglio servizio (XD "Dettaglio prenotazione strutture – dettagli"): card con
     Indietro + tag tipologia + titolo, e le voci come righe accordion (label + caret + divider).
     Dati preparati da PartnerServiceDetail::rows(), una riga per step del wizard della famiglia
     (chiavi-opzione già localizzate): ogni riga ha un paragrafo (`text`) e/o delle righe sotto
     (`details`), oppure il corpo proprio di stanze e foto. --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="mx-auto w-full max-w-[910px] rounded-[10px] border border-gray-150 bg-white px-8 py-7">

                {{-- Indietro (XD: link con freccia, in alto nella card) --}}
                <flux:link href="{{ route('partner.services') }}" class="!inline-flex !items-center !gap-2 !text-[15px] !font-bold !text-[#959595] !no-underline hover:!text-ink">
                    <flux:icon.arrow-back class="h-4 w-4" />
                    {{ __('partner.services.back') }}
                </flux:link>

                {{-- Tag tipologia + titolo --}}
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <flux:badge class="!rounded-[3px] !bg-brand-magenta !text-white">{{ $tag }}</flux:badge>
                    @if ($draft->isAwaitingPublication())
                        @php
                            // Stessa diagnosi del badge in "I miei servizi": per una
                            // smartbox di chi non incassa online il collegamento Stripe
                            // non è la cosa da fare (27/09/2026), e scriverlo comunque
                            // è la diagnosi falsa della segnalazione del 29/09/2026.
                            // La causa, non il gate (difetto F3, 28/09/2026): a chi è
                            // online e deve solo finire Stripe va detto proprio Stripe.
                            $paymentBlocked = auth()->user()?->partnerProfile?->needsOnlinePaymentFor($draft->family()) === true;
                        @endphp
                        <flux:badge class="!rounded-[3px] !bg-brand-yellow !text-ink">{{ __($paymentBlocked ? 'partner.my_services.awaiting_payment_method' : 'partner.my_services.awaiting_stripe') }}</flux:badge>
                    @endif
                </div>
                <h1 class="mt-3 text-[25px] font-bold text-[#0D171A]">{{ $draft->name ?: __('partner.services.not_provided') }}</h1>

                {{-- Righe accordion (XD: label Nunito-Medium 15 #2B2B2B + caret dx + divider #E2EAEB) --}}
                <div class="mt-6">
                    @foreach ($rows as $row)
                        <div x-data="{ open: false }" class="border-b border-[#E2EAEB]">
                            <button type="button" x-on:click="open = !open" class="flex w-full items-center justify-between px-4 py-[17px] text-left">
                                <span class="text-[15px] font-medium text-[#2B2B2B]">{{ $row['label'] }}</span>
                                <flux:icon.chevron-right class="h-4 w-4 shrink-0 text-[#0D171A] transition-transform" x-bind:class="open && 'rotate-90'" />
                            </button>
                            <div x-show="open" x-collapse style="display: none" class="px-4 pb-4">
                                @if (filled($row['photos'] ?? null))
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($row['photos'] as $photo)
                                            <img src="{{ $photo }}" alt="" class="h-[90px] w-[120px] object-cover">
                                        @endforeach
                                    </div>
                                @elseif (filled($row['rooms'] ?? null))
                                    <ul class="space-y-1 text-[15px] text-[#627277]">
                                        @foreach ($row['rooms'] as $room)
                                            <li>{{ $room['name'] }} ({{ $room['type'] }}) — {{ $room['units'] }} {{ __('partner.services.rooms_count') }} · €{{ $room['price'] }} {{ __('partner.services.rooms_price') }}</li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2 text-sm text-[#959595]">{{ __('partner.services.checkin') }}: {{ $row['checkin'][0] ?: '—' }}–{{ $row['checkin'][1] ?: '—' }} · {{ __('partner.services.checkout') }}: {{ $row['checkout'][0] ?: '—' }}–{{ $row['checkout'][1] ?: '—' }}</p>
                                @else
                                    @if (filled($row['text'] ?? null))
                                        <p class="text-[15px] leading-relaxed text-[#627277]">{{ $row['text'] }}</p>
                                    @endif
                                    {{-- Dettagli della voce (difetto F7, 28/09/2026): date, orari, posti, zona,
                                         testo libero di «Altro»… una riga ciascuno, come nella scheda pubblica. --}}
                                    @if (filled($row['details'] ?? []))
                                        <ul @class(['space-y-1 text-[15px] leading-relaxed text-[#627277]', 'mt-2' => filled($row['text'] ?? null)])>
                                            @foreach ($row['details'] as $detail)
                                                <li>{{ $detail }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
