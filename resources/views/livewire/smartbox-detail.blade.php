{{-- Smartbox – Dettaglio (XD: "Smartbox - dettaglio") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        {{-- 1. Hero foto full-bleed: scrim a sinistra, titolo, azioni, CTA galleria --}}
        <section class="relative h-[590px] w-full overflow-hidden">
            <img src="{{ asset('img/xd/'.$box->hero_img.'.jpg') }}" alt="{{ $box->title }}" class="absolute inset-0 h-full w-full object-cover object-[center_68%]">
            <div class="absolute inset-y-0 left-0 w-[53%] bg-gradient-to-r from-black/60 to-transparent" aria-hidden="true"></div>

            <div class="{{ $px }} relative h-full">
                <div class="pt-[74px]">
                    <a href="{{ route('smartbox') }}" class="inline-flex items-center gap-2 text-[13px] leading-6 text-white">
                        <flux:icon.arrow-back class="h-3 w-3 shrink-0" />
                        Indietro
                    </a>
                    <h1 class="mt-7 text-[25px] font-bold leading-[30px] text-brand-yellow">{{ $box->title }}</h1>
                    <p class="mt-2.5 flex items-center gap-2 text-[13px] font-semibold text-white">
                        <flux:icon.profile class="h-[14px] w-[14px] shrink-0" />
                        {{ $box->audience }} - {{ $box->audience_people }} persone
                    </p>
                </div>

                {{-- Condividi + Preferiti (toggle: cerchio brand-yellow quando attivo) --}}
                <div class="absolute right-4 top-[125px] flex items-center gap-4 lg:right-8">
                    <flux:button square aria-label="Condividi" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-white !text-ink !shadow-none">
                        {{-- TODO: condivisione (nessuna interazione definita nell'XD) --}}
                        <flux:icon.share class="h-[15px] w-[15px]" />
                    </flux:button>
                    {{-- Base bianca come !bg-[#fff] (non !bg-white): nel CSS compilato i valori arbitrari precedono !bg-brand-yellow, così il toggle vince --}}
                    <flux:button square x-data="{ fav: false }" @click="fav = !fav" ::class="fav && '!bg-brand-yellow'" ::aria-pressed="fav" aria-label="Aggiungi ai preferiti" class="!h-[30px] !w-[30px] !rounded-full !border-0 !bg-[#fff] !text-black !shadow-none">
                        <flux:icon.heart class="h-4 w-4" />
                    </flux:button>
                </div>

                {{-- CTA galleria (TODO: galleria foto cofanetto) --}}
                <flux:button class="!absolute bottom-[88px] right-4 h-10 !gap-2.5 !rounded-full !border-0 !bg-brand-cyan !px-7 !text-[15px] !font-bold !text-white !shadow-none lg:right-8">
                    <flux:icon.eye class="h-[19px] w-[19px] shrink-0" />
                    Vedere tutte le foto
                </flux:button>
            </div>
        </section>

        <div class="{{ $px }} pb-[120px]">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-[100px]">

                {{-- Colonna sinistra: contenuti cofanetto --}}
                <div class="min-w-0 max-w-[1032px] flex-1 pt-10">

                    {{-- 2a. Descrizione breve --}}
                    <section>
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Descrizione breve</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $box->description }}</p>
                    </section>

                    {{-- 2b. Informazioni generali --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Informazioni generali</h2>
                        @include('partials.general-info', ['rows' => $box->general_info])
                    </section>

                    {{-- 3. Cosa troverai --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Cosa troverai</h2>
                        @include('partials.feature-cards', ['features' => $box->features])
                    </section>

                    {{-- 4. Il tuo weekend --}}
                    <section class="mt-10">
                        <h2 class="text-[25px] font-bold leading-[30px] text-black">Il tuo weekend</h2>
                        <p class="mt-4 max-w-[1032px] text-[15px] leading-[22px] text-[#2B2B2B]">{{ $box->extended_description }}</p>
                    </section>

                    {{-- 5. Servizi Hotel / Servizi Animali --}}
                    <section class="mt-8 flex flex-wrap gap-3">
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">Servizi Hotel</h2>
                            <ul class="mt-2 space-y-[7px]">
                                @foreach ($hotelServices as $service)
                                    <li wire:key="srv-hotel-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] text-[#0D171A]">
                                        @if ($service['included'])
                                            <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                        @else
                                            <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                        @endif
                                        {{ $service['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="min-h-[250px] w-[442px] max-w-full rounded-[4px] border border-[#DEDEDE] bg-white p-4 lg:px-[22px]">
                            <h2 class="text-[25px] font-bold leading-[30px] text-black">Servizi Animali</h2>
                            <ul class="mt-2 space-y-[7px]">
                                @foreach ($animalServices as $service)
                                    <li wire:key="srv-animal-{{ $loop->index }}" class="flex items-center gap-3 text-[15px] text-[#0D171A]">
                                        @if ($service['included'])
                                            <flux:icon.check class="h-3.5 w-3.5 shrink-0 text-[#37C443]" />
                                        @else
                                            <flux:icon.close class="h-3.5 w-3.5 shrink-0 text-[#EA2E68]" />
                                        @endif
                                        {{ $service['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                </div>

                {{-- 2c. Card "Box acquista" (sovrappone il bordo inferiore dell'hero su desktop) --}}
                <aside class="relative z-10 w-full max-w-[453px] shrink-0 lg:-mt-[26px] lg:w-[453px]">
                    <div class="rounded-[4px] border border-[#DEDEDE] bg-white" x-data="{ mode: 'acquista' }">
                        {{-- Acquista / Regala (toggle solo visivo) --}}
                        {{-- TODO: flusso regalo --}}
                        <div class="flex h-[57px] items-stretch">
                            <flux:button variant="ghost" @click="mode = 'acquista'" ::aria-pressed="(mode === 'acquista').toString()" class="!h-full !flex-1 !justify-center !gap-2.5 !rounded-none !px-0 !text-[17px] !font-normal !text-[#2B2B2B] hover:!bg-transparent">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full" :class="mode === 'acquista' ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white'">
                                    <flux:icon.check x-show="mode === 'acquista'" class="h-2.5 w-2.5 text-black" />
                                </span>
                                Acquista
                            </flux:button>
                            <span class="my-[9px] w-px shrink-0 bg-[#DEDEDE]" aria-hidden="true"></span>
                            <flux:button variant="ghost" @click="mode = 'regala'" ::aria-pressed="(mode === 'regala').toString()" class="!h-full !flex-1 !justify-center !gap-2.5 !rounded-none !px-0 !text-[17px] !font-normal !text-[#2B2B2B] hover:!bg-transparent">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full" :class="mode === 'regala' ? 'bg-brand-yellow' : 'border border-[#C8C8C8] bg-white'">
                                    <flux:icon.check x-show="mode === 'regala'" x-cloak class="h-2.5 w-2.5 text-black" />
                                </span>
                                Regala
                            </flux:button>
                        </div>
                        <div class="mx-[22px] border-b border-[#DEDEDE]" aria-hidden="true"></div>

                        <p class="mt-[22px] text-center text-[28px] font-bold leading-[38px] text-[#2B2B2B]">{{ \App\Support\Format::money($box->price_cents) }}</p>

                        <div class="mx-[22px] mt-4 rounded-[4px] border border-[#DEDEDE]">
                            <div class="grid grid-cols-2 divide-x divide-[#DEDEDE] border-b border-[#DEDEDE]">
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">Valido dal</p>
                                    {{-- Data campione XD: il "valido dal" reale dipenderà dall'acquisto (step 3-4) --}}
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">17/01/2024</p>
                                </div>
                                <div class="px-[15px] py-3">
                                    <p class="text-[17px] font-medium text-[#2B2B2B]">Valido per</p>
                                    <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">{{ \App\Support\Format::validity($box->validity_months) }}</p>
                                </div>
                            </div>
                            <div class="border-b border-[#DEDEDE] px-[15px] py-3">
                                <p class="text-[17px] font-medium text-[#2B2B2B]">Ospiti</p>
                                <p class="mt-1 text-[17px] font-light text-[#2B2B2B]">2 adulti</p>
                            </div>
                            <div class="flex items-center justify-between px-[15px] py-3">
                                <span>
                                    <span class="block text-[17px] font-medium text-[#2B2B2B]">Animali</span>
                                    <span class="mt-1 block text-[17px] font-light text-[#2B2B2B]">1 cani</span>
                                </span>
                                {{-- XD "Tracciato 619": chevron nero verso il basso (stessa forma di arrow-down) — TODO: selezione animali --}}
                                <flux:icon.arrow-down class="h-3 w-3 shrink-0 text-black" />
                            </div>
                        </div>

                        <div class="mt-[26px] px-[22px] pb-6">
                            {{-- TODO: aggiunta al carrello --}}
                            <flux:button class="!flex !h-[39px] w-full items-center justify-center !rounded-full !border-0 !bg-brand-yellow !px-0 text-sm !font-bold !text-[#0D171A] !shadow-none">Aggiungi al carrello</flux:button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
