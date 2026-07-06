{{-- Profilo – i miei ordini (XD, + variante "– 1" per il tab Passati): tab In programma/Passati + righe ordine --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'ordini'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 {{ $card }}">
                    <h1 class="text-2xl font-bold leading-none text-black">I miei ordini</h1>

                    {{-- Tab: stesso pattern dello stepper checkout (linea #DEDEDE, attivo ciano con sottolineatura 2.5px) --}}
                    <div class="relative mt-[50px]">
                        <div class="absolute inset-x-0 bottom-0 h-px bg-[#DEDEDE]" aria-hidden="true"></div>
                        <div class="relative flex gap-[50px]">
                            @foreach ($tabs as $key => $label)
                                <flux:button variant="ghost" wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}" class="relative !h-auto !rounded-none !p-0 !pb-3 !text-lg !font-medium !leading-none hover:!bg-transparent {{ $tab === $key ? '!text-[#68CDEB] after:absolute after:inset-x-0 after:bottom-0 after:h-[2.5px] after:bg-[#68CDEB]' : '!text-[#C8C8C8] hover:!text-[#C8C8C8]' }}">{{ $label }}</flux:button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Righe ordine (passo XD 122): conteggio + striscia miniature | data + totale + chevron --}}
                    <div class="divide-y divide-[#DEDEDE]">
                        @foreach ($orders as $order)
                            {{-- TODO: pagina riepilogo ordine (artboard "Profilo – i miei ordini – riepilogo") --}}
                            <a href="#" wire:key="order-{{ $order['id'] }}" class="flex items-start justify-between gap-6 py-6">
                                <div class="min-w-0">
                                    <p class="text-[13px] leading-none text-[#555555]">{{ $this->itemsLabel($order['items']) }}</p>
                                    <div class="mt-[15px] flex gap-[3px]">
                                        @foreach ($order['photos'] as $photo)
                                            <img src="{{ asset('img/xd/' . $photo) }}" alt="" class="h-[66px] w-[99px] rounded-[2px] object-cover" wire:key="photo-{{ $order['id'] }}-{{ $loop->index }}">
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end">
                                    <p class="text-[13px] leading-none text-[#555555]">{{ $order['date'] }}</p>
                                    <p class="mt-2 text-2xl font-semibold leading-none text-[#2B2B2B]">{{ $order['price'] }} €</p>
                                    <flux:icon.arrow-forward class="mt-3 h-[17px] w-[10px] text-[#2B2B2B]" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
