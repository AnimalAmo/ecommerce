{{-- Profilo – Eventi a cui partecipo (XD): cornice Profilo, card "I miei interessi" con tab e box evento --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-4">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'eventi'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }}">
                    {{-- Titolo card XD ≠ voce sidebar: l'artboard usa "I miei interessi" (l'app tiene "Eventi a cui partecipo") --}}
                    @include('partials.profile-mobile-header', ['title' => __('profile.nav_events')])

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.interests_title') }}</h1>

                    {{-- Tab identiche a "I miei ordini" --}}
                    <div class="mt-[22px] lg:mt-[50px]">
                        @include('partials.profile-tabs')
                    </div>

                    @if ($events === [])
                        {{-- Nessun artboard XD per questo stato: stessa riga minimale dei preferiti --}}
                        <p class="mt-6 text-[15px] leading-[21px] text-[#959595] max-lg:mt-4">{{ __('profile.no_results') }}</p>
                    @else
                        {{-- Box evento 468x170 (variante evento del "Box preferiti"), 2 per riga --}}
                        <div class="mt-6 grid grid-cols-1 gap-x-[28px] gap-y-4 md:grid-cols-2 max-lg:mt-4">
                            @foreach ($events as $event)
                                {{-- App: card 343 in colonna — foto 117x78 + tag/titolo, poi orario, luogo e prezzo in corsivo --}}
                                <article wire:key="event-mobile-{{ $event['id'] }}" class="w-full rounded-[3px] border border-[#E9E9E9] bg-white p-3 lg:hidden">
                                    <div class="flex gap-[10px]">
                                        <img src="{{ asset('img/xd/' . $event['photo']) }}" alt="{{ $event['title'] }}" class="h-[78px] w-[84px] shrink-0 rounded-[2px] object-cover">

                                        <div class="flex min-w-0 flex-1 flex-col">
                                            <span class="flex h-[26px] w-fit items-center rounded-[3px] bg-brand-purple-soft px-[10px] text-[13px] font-medium text-white">{{ $event['tag'] }}</span>
                                            <h2 class="mt-[13px] truncate text-sm font-bold leading-none text-[#0D171A]">{{ $event['title'] }}</h2>
                                        </div>
                                    </div>

                                    <div class="mt-[1px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                    <div class="mt-4 flex items-center gap-[7px] text-brand-magenta">
                                        <flux:icon.time class="h-[11px] w-[11px] shrink-0" />
                                        <span class="truncate text-[11px] font-bold uppercase leading-none">{{ $event['time'] }}</span>
                                    </div>

                                    <div class="mt-[9px] flex items-center gap-[7px] text-[#0D171A]">
                                        <flux:icon.pin class="h-[11px] w-[11px] shrink-0" />
                                        <span class="truncate text-[13px] leading-none">{{ $event['location'] }}</span>
                                    </div>

                                    <div class="mt-[21px] h-px bg-[#E9E9E9]" aria-hidden="true"></div>

                                    <p class="mt-[7px] text-right text-[13px] italic leading-none text-[#0D171A]">{{ $event['price'] }}</p>
                                </article>

                                <article wire:key="event-{{ $event['id'] }}" class="relative flex h-[170px] w-full max-w-[468px] rounded-[3px] border border-[#E9E9E9] bg-white p-[6px] max-lg:hidden">
                                    <img src="{{ asset('img/xd/' . $event['photo']) }}" alt="{{ $event['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

                                    <span class="absolute left-[11px] top-[13px] flex h-[26px] items-center rounded-[3px] bg-brand-purple-soft px-[10px] text-[13px] font-medium text-white">{{ $event['tag'] }}</span>

                                    <div class="flex min-w-0 flex-1 flex-col pb-[5px] pl-1 pr-1 pt-[7px]">
                                        <h2 class="truncate text-base font-semibold leading-none text-black">{{ $event['title'] }}</h2>
                                        <div class="mr-[9px] mt-2 h-px shrink-0 bg-[#E9E9E9]" aria-hidden="true"></div>

                                        {{-- Riga orario: qui l'XD usa il magenta (non il viola della pagina Eventi) --}}
                                        <div class="mt-[6px] flex items-center gap-[6px] text-brand-magenta">
                                            <flux:icon.time class="h-[11px] w-[11px] shrink-0" />
                                            <span class="truncate text-[11px] font-bold uppercase leading-none">{{ $event['time'] }}</span>
                                        </div>

                                        <div class="mt-[9px] flex items-center gap-[6px] text-[#555555]">
                                            <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                            <span class="truncate text-[11px] font-semibold leading-none">{{ $event['location'] }}</span>
                                        </div>

                                        <div class="mt-auto flex items-end justify-between gap-2">
                                            {{-- TODO: azione Partecipa (stesso bottone della pagina Eventi) --}}
                                            <flux:button class="!h-[39px] !w-[136px] !shrink-0 !gap-2 !rounded-full !border-0 !bg-[#E9E9E9] !text-sm !font-bold !text-[#0D171A] !shadow-none">
                                                <flux:icon.check-1 class="h-4 w-4 shrink-0" />
                                                {{ __('profile.attend') }}
                                            </flux:button>
                                            <span class="pb-[3px] text-[11px] font-semibold leading-none text-[#0D171A]">{{ $event['price'] }}</span>
                                        </div>
                                    </div>
                                </article>
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
