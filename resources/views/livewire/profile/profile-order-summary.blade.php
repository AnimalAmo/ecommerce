{{-- Profilo – i miei ordini – riepilogo (XD): card a tutta larghezza senza sidebar, link Indietro + 3 box articolo --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px]">
            <div class="{{ $card }} p-6">
                <a href="{{ route('profilo.ordini') }}" class="inline-flex items-center gap-[5px] text-[13px] leading-none text-[#555555]">
                    <flux:icon.arrow-back class="h-[13px] w-[13px] shrink-0" />
                    {{ __('profile.back') }}
                </a>

                <h1 class="mt-[22px] text-2xl font-bold leading-none text-black">{{ __('profile.order_summary_title') }}</h1>

                {{-- Box articolo 468x170 (XD "Box preferiti" senza cuore/borsa), 3 per riga con gap 10.
                     Ordine passato (XD "– 1"): box 206 con divider sotto il titolo e "Scrivi una recensione" sotto la foto --}}
                <div class="mt-10 flex flex-wrap gap-[10px]">
                    @foreach ($items as $item)
                        <article wire:key="item-{{ $item['id'] }}" class="relative flex w-full max-w-[468px] rounded-[3px] border border-[#E9E9E9] bg-white p-[6px] {{ $past ? 'h-[206px] flex-col' : 'h-[170px]' }}">
                            <span class="absolute left-[11px] top-[13px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>

                            <div class="flex min-h-0 w-full {{ $past ? 'h-[158px]' : 'h-full' }}">
                                <img src="{{ $item['photo'] }}" alt="{{ $item['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

                                <div class="flex min-w-0 flex-1 flex-col pb-2 pl-1 pr-1 pt-[7px] border-b border-[#E9E9E9]">
                                    <h2 class="truncate text-base font-semibold leading-none text-black">{{ $item['title'] }}</h2>

                                    @if ($past)
                                        <div class="mr-[9px] mt-[14px] h-px shrink-0 bg-[#E9E9E9]" aria-hidden="true"></div>
                                    @endif

                                    {{-- Righe meta 13px a passo 24 (pin/calendar/ospiti+cane come il riepilogo checkout); le righe assenti fanno salire le successive --}}
                                    <div class="{{ $past ? 'mt-[10px]' : 'mt-[17px]' }} space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
                                        @if ($item['location'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                                <span class="truncate">{{ $item['location'] }}</span>
                                            </div>
                                        @endif
                                        @if ($item['dates'] !== null)
                                            <div class="flex items-center gap-2">
                                                <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                <span>{{ $item['dates'] }}</span>
                                            </div>
                                        @endif
                                        @if ($item['guests'] !== null || $item['animals'] !== null)
                                            <div class="flex items-center">
                                                @if ($item['guests'] !== null)
                                                    <div class="flex w-[112px] items-center gap-2">
                                                        <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                        <span>{{ $item['guests'] }}</span>
                                                    </div>
                                                @endif
                                                @if ($item['animals'] !== null)
                                                    <div class="flex items-center gap-2">
                                                        <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                                        <span>{{ $item['animals'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <p class="mt-auto text-[11px] font-semibold leading-none text-[#0D171A]">{{ $item['price'] }}</p>
                                </div>
                            </div>

                            @if ($past)
                                {{-- Hover ciano: seconda variante colore del simbolo XD "scrivi recensione".
                                     icon prop: matita e testo centrati nativamente dal flex del bottone --}}
                                <flux:button variant="ghost" icon="pencil" icon:variant="outline" wire:click="openReview({{ $item['id'] }})" class="!h-auto !w-full flex-1 !gap-[5px] !p-0 !text-sm !font-medium !text-[#2B2B2B] transition-colors hover:!bg-transparent hover:!text-[#68CDEB] [&_svg]:!size-[14px]">{{ __('profile.write_review') }}</flux:button>
                            @endif
                        </article>
                    @endforeach
                </div>

                {{-- Dedica e messaggio dalle options.gift delle righe regalo (stesso stile del riepilogo checkout regalo) --}}
                @foreach ($items as $item)
                    @if ($item['giftDedication'] !== null || $item['giftMessage'] !== null)
                        <div wire:key="gift-{{ $item['id'] }}" class="mt-6 text-[15px] font-normal text-[#0D171A]">
                            @if ($item['giftDedication'] !== null)
                                <p class="leading-none">{{ __('profile.gift_dedicated_to', ['name' => $item['giftDedication']]) }}</p>
                            @endif
                            @if ($item['giftMessage'] !== null)
                                <p class="mt-4 leading-[21px]">{{ __('profile.gift_message', ['message' => $item['giftMessage']]) }}</p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')

    {{-- Pop-up scrivi recensione (XD 1020x512): box articolo centrato + campi titolo/recensione, Annulla/Conferma --}}
    <flux:modal name="scrivi-recensione" :closable="false" class="w-full !max-w-[1020px] !rounded-[3px] !border !border-[#E9E9E9] bg-white !p-6 backdrop:!bg-black/30">
        @if ($reviewItem !== null)
            <flux:heading level="2" class="!text-center !text-2xl !font-bold !leading-none !text-[#0D171A]">{{ __('profile.write_review') }}</flux:heading>

            {{-- Box articolo 467x172 con bordo sottile #C8C8C8 (niente prezzo né azioni) --}}
            <div class="relative mx-auto flex h-[172px] w-full max-w-[467px] rounded-[3px] border border-[#C8C8C8]/70 bg-white p-[7px]">
                <span class="absolute left-[12px] top-[13px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $reviewItem['tagColor'] }}">{{ $reviewItem['tag'] }}</span>

                <img src="{{ $reviewItem['photo'] }}" alt="{{ $reviewItem['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

                <div class="flex min-w-0 flex-1 flex-col pl-1">
                    <h3 class="truncate text-base font-semibold leading-none text-black">{{ $reviewItem['title'] }}</h3>

                    <div class="mr-[9px] mt-[14px] h-px shrink-0 bg-[#E9E9E9]" aria-hidden="true"></div>

                    <div class="mt-[10px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
                        @if ($reviewItem['location'] !== null)
                            <div class="flex items-center gap-2">
                                <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                <span class="truncate">{{ $reviewItem['location'] }}</span>
                            </div>
                        @endif
                        @if ($reviewItem['dates'] !== null)
                            <div class="flex items-center gap-2">
                                <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                <span>{{ $reviewItem['dates'] }}</span>
                            </div>
                        @endif
                        @if ($reviewItem['guests'] !== null || $reviewItem['animals'] !== null)
                            <div class="flex items-center">
                                @if ($reviewItem['guests'] !== null)
                                    <div class="flex w-[112px] items-center gap-2">
                                        <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                        <span>{{ $reviewItem['guests'] }}</span>
                                    </div>
                                @endif
                                @if ($reviewItem['animals'] !== null)
                                    <div class="flex items-center gap-2">
                                        <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                        <span>{{ $reviewItem['animals'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Campi come la dedica del carrello: placeholder #0D171A non corsivo --}}
            <div class="mt-6">
                <flux:input wire:model="reviewTitle" placeholder="{{ __('profile.review_title_placeholder') }}" class="!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#0D171A]" />

                <flux:textarea wire:model="reviewText" placeholder="{{ __('profile.review_text_placeholder') }}" rows="3" resize="none" class="mt-4 !h-[100px] !w-full !rounded-[3px] !border !border-[#C8C8C8]/70 !bg-white !px-[15px] !py-[11px] !text-[15px] !text-[#0D171A] !shadow-none !ring-0 placeholder:!text-[#0D171A]" />
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeReview" class="!h-auto !p-0 !text-lg !font-medium !text-[#959595] hover:!bg-transparent hover:!text-[#959595]">{{ __('profile.cancel') }}</flux:button>
                <flux:button wire:click="confirmReview" class="!h-10 !w-[134px] !rounded-full !border-0 !bg-[#68CDEB] !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#68CDEB]">{{ __('profile.confirm') }}</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
