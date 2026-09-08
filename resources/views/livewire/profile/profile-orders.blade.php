{{-- Profilo – i miei ordini (XD, + variante "– 1" per il tab Passati): tab In programma/Passati + righe ordine --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-4">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'ordini'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }}">
                    @include('partials.profile-mobile-header', ['title' => __('profile.orders_title')])

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.orders_title') }}</h1>

                    {{-- App: pillole; desktop: tab sottolineate (stesso flux:tabs) --}}
                    <div class="mt-[22px] lg:mt-[50px]">
                        @include('partials.profile-tabs')
                    </div>

                    @if ($orders === [])
                        {{-- Nessun artboard XD per questo stato: stessa riga minimale della gemella
                             "I miei interessi" (profile-events), più una CTA. Senza, la pagina di OGNI
                             utente appena registrato finiva sulle tab e poi il nulla, come se il
                             caricamento si fosse interrotto.
                             La CTA non manda al catalogo: finché i partner non pubblicano non c'è niente
                             da comprare, mentre gli articoli di Animal Times esistono davvero. --}}
                        <p class="mt-6 text-[15px] leading-[21px] text-[#959595] max-lg:mt-4">{{ __('profile.orders_empty') }}</p>
                        <flux:button variant="ghost" href="{{ route('news') }}" class="mt-4 !h-auto !p-0 !text-[15px] !font-bold !text-[#68CDEB] hover:!bg-transparent hover:!text-[#68CDEB]">{{ __('profile.orders_empty_cta') }}</flux:button>
                    @else
                        {{-- Righe ordine (passo XD 122): conteggio + striscia miniature | data + totale + chevron --}}
                        <div class="divide-y divide-[#DEDEDE] max-lg:divide-y-0">
                            @foreach ($orders as $order)
                                {{-- App: conteggio | data | totale su una riga sopra le miniature, chevron centrato --}}
                                <a href="{{ route('profilo.ordini.riepilogo', $order['number']) }}" wire:key="order-mobile-{{ $order['number'] }}" class="flex items-center justify-between gap-3 border-b border-[#DEDEDE] py-[18px] lg:hidden">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-[10px] text-[13px] leading-none text-[#0D171A]">
                                            <span>{{ $order['itemsLabel'] }}</span>
                                            <span class="h-[13px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                                            <span>{{ $order['date'] }}</span>
                                            <span class="h-[13px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                                            <span>{{ $order['price'] }}</span>
                                        </div>

                                        <div class="mt-[17px] flex gap-2 overflow-hidden">
                                            @foreach ($order['photos'] as $photo)
                                                <img src="{{ $photo }}" alt="" class="h-[66px] w-[99px] shrink-0 rounded-[2px] object-cover" wire:key="photo-mobile-{{ $order['number'] }}-{{ $loop->index }}">
                                            @endforeach
                                        </div>
                                    </div>

                                    <flux:icon.arrow-forward class="h-[17px] w-[10px] shrink-0 text-[#2B2B2B]" />
                                </a>

                                <a href="{{ route('profilo.ordini.riepilogo', $order['number']) }}" wire:key="order-{{ $order['number'] }}" class="flex items-start justify-between gap-6 py-6 max-lg:hidden">
                                    <div class="min-w-0">
                                        <p class="text-[13px] leading-none text-[#555555]">{{ $order['itemsLabel'] }}</p>
                                        <div class="mt-[15px] flex gap-[3px]">
                                            {{-- Strip miniature = photo_url snapshot delle righe ordine --}}
                                            @foreach ($order['photos'] as $photo)
                                                <img src="{{ $photo }}" alt="" class="h-[66px] w-[99px] rounded-[2px] object-cover" wire:key="photo-{{ $order['number'] }}-{{ $loop->index }}">
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end">
                                        <p class="text-[13px] leading-none text-[#555555]">{{ $order['date'] }}</p>
                                        <p class="mt-2 text-2xl font-semibold leading-none text-[#2B2B2B]">{{ $order['price'] }}</p>
                                        <flux:icon.arrow-forward class="mt-3 h-[17px] w-[10px] text-[#2B2B2B]" />
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>

    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
