{{-- Profilo – i miei ordini – riepilogo (XD): card a tutta larghezza senza sidebar, link Indietro + 3 box articolo --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-4">
            {{-- Sull'app "scrivi recensione" è un artboard a sé (header + tabbar), non un pop-up:
                 su mobile prende il posto del riepilogo, su desktop resta la modale in fondo. --}}
            @if ($reviewItem !== null)
                <div class="lg:hidden">
                    <flux:button variant="ghost" wire:click="closeReview" class="!h-auto !gap-2 !p-0 !text-sm !font-normal !leading-none !text-[#959595] hover:!bg-transparent hover:!text-[#959595] [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                        <flux:icon.arrow-back class="h-4 w-4" />
                        {{ __('profile.back') }}
                    </flux:button>

                    <h1 class="mt-4 text-lg font-semibold leading-none text-[#0D171A]">{{ $reviewItem['reviewed'] ? __('profile.view_review') : __('profile.write_review') }}</h1>

                    <div class="mt-[18px]">
                        @include('partials.profile-item-card-mobile', ['item' => $reviewItem, 'review' => false])
                    </div>

                    {{-- Campi XD "inserimento campi": etichetta 12px sopra il box r5 bordo #C8C8C8 --}}
                    <div class="mt-[18px]">
                        <flux:field class="[&>*+*]:!mt-[5px]">
                            <flux:label class="!mb-0 !mt-0 !ml-[7px] !block !text-xs !font-normal !text-[#555555]">{{ __('profile.review_title_label') }}</flux:label>
                            <flux:input wire:model="reviewTitle" class="!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[5px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-4 [&_input]:!text-sm [&_input]:!font-medium [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0" />
                        </flux:field>

                        <flux:field class="!mt-[15px] [&>*+*]:!mt-[5px]">
                            <flux:label class="!mb-0 !mt-0 !ml-[7px] !block !text-xs !font-normal !text-[#555555]">{{ __('profile.review_text_label') }}</flux:label>
                            <flux:textarea wire:model="reviewText" rows="8" resize="none" class="!h-[160px] !w-full !rounded-[5px] !border !border-[#C8C8C8]/70 !bg-white !px-4 !py-[15px] !text-sm !font-medium !text-[#0D171A] !shadow-none !ring-0" />
                        </flux:field>
                    </div>

                    <flux:button wire:click="confirmReview" class="!mt-4 !h-[39px] !w-full !rounded-full !border-0 !bg-[#68CDEB] !text-[15px] !font-semibold !text-white !shadow-none hover:!bg-[#68CDEB]">{{ __('profile.review_share') }}</flux:button>
                </div>
            @endif

            <div class="{{ $card }} p-6 max-lg:p-0 {{ $reviewItem !== null ? 'max-lg:hidden' : '' }}">
                @include('partials.profile-mobile-header', [
                    'title' => __('profile.order_summary_title'),
                    'backHref' => route('profilo.ordini'),
                ])

                <a href="{{ route('profilo.ordini') }}" class="inline-flex items-center gap-[5px] text-[13px] leading-none text-[#555555] max-lg:hidden">
                    <flux:icon.arrow-back class="h-[13px] w-[13px] shrink-0" />
                    {{ __('profile.back') }}
                </a>

                <h1 class="mt-[22px] text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.order_summary_title') }}</h1>

                {{-- App: testata conteggio | data | totale sopra le card --}}
                <div class="mt-[22px] flex items-center gap-[10px] text-[13px] leading-none text-[#0D171A] lg:hidden">
                    <span>{{ $header['itemsLabel'] }}</span>
                    <span class="h-[13px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                    <span>{{ $header['date'] }}</span>
                    <span class="h-[13px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                    <span>{{ $header['price'] }}</span>
                </div>

                {{-- Box articolo 468x170 (XD "Box preferiti" senza cuore/borsa), 3 per riga con gap 10.
                     Ordine passato (XD "– 1"): box 206 con divider sotto il titolo e "Scrivi una recensione" sotto la foto --}}
                <div class="mt-10 flex flex-wrap gap-[10px] max-lg:mt-[18px] max-lg:gap-[15px]">
                    @foreach ($items as $item)
                        {{-- L'artboard app del riepilogo tiene la pillola recensione su OGNI card,
                             anche in programma: il prototipo ci arriva proprio da "i miei ordini - in programma". --}}
                        <div wire:key="item-mobile-{{ $item['id'] }}" class="w-full lg:hidden">
                            @include('partials.profile-item-card-mobile', ['item' => $item, 'review' => true])
                        </div>

                        <article wire:key="item-{{ $item['id'] }}" class="relative flex w-full max-w-[468px] rounded-[3px] border border-[#E9E9E9] bg-white p-[6px] max-lg:hidden {{ $past ? 'h-[206px] flex-col' : 'h-[170px]' }}">
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

    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>

    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>

    {{-- Pop-up scrivi recensione (XD 1020x512): box articolo centrato + campi titolo/recensione, Annulla/Conferma.
         Solo desktop: sull'app la recensione è la schermata inline sopra. --}}
    <div class="max-lg:hidden">
    <flux:modal name="scrivi-recensione" :closable="false" class="w-full !max-w-[1020px] !rounded-[3px] !border !border-[#E9E9E9] bg-white !p-6 backdrop:!bg-black/30">
        @if ($reviewItem !== null)
            <flux:heading level="2" class="!text-center !text-2xl !font-bold !leading-none !text-[#0D171A]">{{ $reviewItem['reviewed'] ? __('profile.view_review') : __('profile.write_review') }}</flux:heading>

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

    {{-- Sweet alert recensione (XD app 346x338): card verde con la stessa card articolo, si chiude con la X --}}
    @if ($reviewDoneItem !== null)
        <div class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('profile.review_shared') }}" x-data @keydown.escape.window="$wire.dismissReviewDone()">
            <div class="absolute inset-0 bg-black/40" wire:click="dismissReviewDone" aria-hidden="true"></div>

            <div class="pointer-events-none absolute inset-0 flex items-center justify-center px-4">
                <div class="pointer-events-auto relative w-full max-w-[346px] rounded-[4px] border border-[#43BA6C]/50 bg-[#EAFFF1] p-4">
                    <flux:button variant="ghost" size="sm" square wire:click="dismissReviewDone" aria-label="{{ __('profile.cancel') }}" class="!absolute !right-1 !top-1 !text-[#0D171A] hover:!bg-transparent">
                        <flux:icon.close class="h-4 w-4" />
                    </flux:button>

                    <flux:icon.check-circle class="mx-auto mt-1 h-10 w-10 text-[#43BA6C]" stroke-width="3" />

                    {{-- XD manda a capo dopo "condivisa": larghezza limitata invece di un <br> nella traduzione --}}
                    <p class="mx-auto mt-2 max-w-[240px] text-center text-lg font-bold leading-[26px] text-[#43BA6C]">{{ __('profile.review_shared') }}</p>

                    <div class="mt-[18px]">
                        @include('partials.profile-item-card-mobile', ['item' => $reviewDoneItem, 'review' => false, 'accent' => '#43BA6C80', 'plain' => true])
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
