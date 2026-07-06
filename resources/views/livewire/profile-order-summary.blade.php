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
                    Indietro
                </a>

                <h1 class="mt-[22px] text-2xl font-bold leading-none text-black">Riepilogo ordine</h1>

                {{-- Box articolo 468x170 (XD "Box preferiti" senza cuore/borsa), 3 per riga con gap 10 --}}
                <div class="mt-10 flex flex-wrap gap-[10px]">
                    @foreach ($items as $item)
                        <article wire:key="item-{{ $item['id'] }}" class="relative flex h-[170px] w-full max-w-[468px] rounded-[3px] border border-[#E9E9E9] bg-white p-[6px]">
                            <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

                            <span class="absolute left-[11px] top-[13px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $item['tagColor'] }}">{{ $item['tag'] }}</span>

                            <div class="flex min-w-0 flex-1 flex-col pb-[5px] pl-1 pr-1 pt-[7px]">
                                <h2 class="truncate text-base font-semibold leading-none text-black">{{ $item['title'] }}</h2>

                                {{-- Righe meta 13px a passo 24 (pin/calendar/ospiti+cane come il riepilogo checkout) --}}
                                <div class="mt-[17px] space-y-[11px] text-[13px] font-semibold leading-[13px] text-[#555555]">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                        <span class="truncate">{{ $item['location'] }}</span>
                                    </div>
                                    @if ($item['dates'] !== null)
                                        <div class="flex items-center gap-2">
                                            <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                            <span>{{ $item['dates'] }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center">
                                        <div class="flex w-[112px] items-center gap-2">
                                            <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                            <span>{{ $item['guests'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <flux:icon.animal class="h-[11px] w-[11px] shrink-0" />
                                            <span>{{ $item['dogs'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-auto text-[11px] font-semibold leading-none text-[#0D171A]">{{ $item['price'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
