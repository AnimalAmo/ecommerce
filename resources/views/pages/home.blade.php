<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('AnimalAmo — Viaggi e servizi pet-friendly')] class extends Component
{
    // Data mirrors XD "Homepage – 4" (real copy from the artboard; body text was lorem placeholder).
    public array $regioni = [
        ['img' => 'holiday-liguria',  'nome' => 'Hotel e servizi in Liguria',            'strutture' => 10],
        ['img' => 'holiday-veneto',   'nome' => 'Hotel e servizi in Veneto',             'strutture' => 5],
        ['img' => 'holiday-trentino', 'nome' => 'Hotel e servizi in Trentino-Alto Adige', 'strutture' => 7],
    ];

    public array $eventi = [
        ['img' => 'event-cavallo',  'titolo' => 'Passeggiata a cavallo',                'luogo' => 'Genova, Italia',               'data' => 'Oggi alle ore 12:30',        'prezzo' => null],
        ['img' => 'event-mare',     'titolo' => 'Weekend al mare',                      'luogo' => 'Fiesole (FI), Toscana',        'data' => 'Lun, 8 Gen alle ore 19:30',  'prezzo' => '25,00'],
        ['img' => 'event-asini',    'titolo' => 'Esperienza con gli asini in fattoria', 'luogo' => 'Manciano (GR), Toscana',       'data' => 'Oggi alle ore 15:00',        'prezzo' => '18,00'],
        ['img' => 'event-maneggio', 'titolo' => 'Weekend in maneggio',                  'luogo' => 'Massa Lubrense (NA), Campania','data' => 'Ven, 18 Gen alle ore 15:00', 'prezzo' => '35,00'],
        ['img' => 'event-cavallo',  'titolo' => 'Trekking al lago',                     'luogo' => 'Molveno (TN), Trentino',       'data' => 'Sab, 20 Gen alle ore 09:00', 'prezzo' => '12,00'],
    ];

    public array $news = [
        ['img' => 'news-trenitalia', 'data' => '20 Ottobre 2023', 'titolo' => 'Novità Trenitalia trasporto animali'],
        ['img' => 'news-easyjet',    'data' => '3 Ottobre 2023',  'titolo' => 'Novità EasyJet trasporto animali'],
    ];
};
?>

@php $px = 'px-[140px]'; @endphp

<div class="min-h-screen bg-white font-sans text-ink antialiased">

    {{-- ============ HEADER ============ --}}
    <header class="sticky top-0 z-50 border-b border-gray-150 bg-white/95 backdrop-blur">
        <div class="{{ $px }} flex h-20 items-center justify-between">
            <div class="flex items-center gap-10">
                <a href="/" class="shrink-0">
                    <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="w-[90px] h-auto">
                </a>
                <nav class="hidden items-center gap-9 text-sm font-normal text-black lg:flex">
                    <a href="#holiday"   class="hover:font-bold">Holiday</a>
                    <a href="#eventi"    class="hover:font-bold">Attività ed Eventi</a>
                    <a href="#smartbox"  class="hover:font-bold">Smartbox</a>
                    <a href="#news"      class="hover:font-bold">News</a>
                    <a href="#community" class="hover:font-bold">Community</a>
                    <a href="#chi-siamo" class="hover:font-bold">Chi siamo</a>
                    <a href="#partner"   class="hover:font-bold">Diventa Partner</a>
                </nav>
            </div>
            <div class="flex items-center gap-5">
                <flux:dropdown>
                    <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" class="!text-sm !font-normal !text-black font-sans">ITA / EUR</flux:button>
                    <flux:menu>
                        <flux:menu.group heading="Lingua">
                            <flux:menu.item>Italiano</flux:menu.item>
                            <flux:menu.item>English</flux:menu.item>
                        </flux:menu.group>
                        <flux:menu.group heading="Valuta">
                            <flux:menu.item>EUR &euro;</flux:menu.item>
                            <flux:menu.item>USD $</flux:menu.item>
                        </flux:menu.group>
                    </flux:menu>
                </flux:dropdown>

                @guest
                    <flux:button class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">Accedi / Registrati</flux:button>
                @endguest

                <flux:button variant="ghost" size="sm" square aria-label="Preferiti" class="!text-ink hover:!text-brand-magenta">
                    <flux:icon.heart class="h-5 w-5" />
                </flux:button>
                <flux:button variant="ghost" size="sm" square aria-label="Carrello" class="!text-ink hover:!text-brand-cyan">
                    <flux:icon.cart class="h-5 w-5" />
                </flux:button>

                @auth
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" square aria-label="Profilo" class="!text-ink hover:!text-brand-cyan">
                            <flux:icon.profile class="h-5 w-5" />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item>Il mio profilo</flux:menu.item>
                            <flux:menu.item>I miei ordini</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item>Esci</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                @endauth
            </div>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="relative isolate overflow-hidden bg-brand-cyan-bg">
        <img src="{{ asset('img/home-hero.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">

        <div class="{{ $px }} flex min-h-[560px] max-h-[976px] flex-col justify-center py-24 lg:h-[calc(100svh-5rem)]">
            {{-- Box hero (stile XD: #152E36, radius 2px): titolo, testo, Dove/Quando --}}
            <div class="w-full max-w-2xl rounded-[2px] bg-[#152E36] px-4 py-6 shadow-[0px_3px_6px_#00000029]">
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white">Viaggia con il tuo<br>migliore amico.</h1>
                <p class="mt-4 text-sm leading-relaxed text-white/70">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.</p>

                {{-- Search bar stile XD: pill bianco (border #F4F4F4, radius 100px) con input + pulsante dentro --}}
                <div class="mt-8 flex w-full items-center gap-2 rounded-[100px] border border-[#F4F4F4] bg-white p-2">
                    <label class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:icon.pin class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <input type="text" placeholder="Dove" class="w-full border-0 bg-transparent p-0 text-sm text-ink placeholder-gray-400 focus:ring-0">
                    </label>
                    <span class="h-6 w-px shrink-0 bg-gray-200"></span>
                    <label class="flex flex-1 items-center gap-3 px-4 py-2">
                        <flux:icon.calendar class="h-5 w-5 shrink-0 text-brand-cyan" />
                        <input type="text" placeholder="Quando" class="w-full border-0 bg-transparent p-0 text-sm text-ink placeholder-gray-400 focus:ring-0">
                    </label>
                    <flux:button square aria-label="Cerca" class="!h-auto !w-auto shrink-0 !rounded-full !bg-brand-cyan !p-3.5 !text-white hover:!bg-brand-cyan-soft">
                        <flux:icon.search class="h-5 w-5" />
                    </flux:button>
                </div>
            </div>

        </div>
    </section>

    {{-- ============ ANIMAL HOLIDAY ============ --}}
    <section id="holiday" class="{{ $px }} py-20">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h2 class="text-4xl font-extrabold">Animal Holiday</h2>
                <p class="mt-3 max-w-xl text-lg text-gray-500">Hotel, B&amp;B e strutture dove il tuo animale è sempre il benvenuto.</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-6">
            @foreach ($regioni as $r)
                <a href="#" wire:key="reg-{{ $loop->index }}" class="group block rounded-[3px] border border-[#E9E9E9] bg-white p-[10px]">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset('img/xd/'.$r['img'].'.jpg') }}" alt="{{ $r['nome'] }}" class="h-80 w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
                        <flux:badge class="absolute right-4 top-4 !rounded-[3px] !bg-brand-magenta !text-white">{{ $r['strutture'] }} Strutture</flux:badge>
                        <h3 class="absolute bottom-4 left-4 pr-4 text-[20px] font-bold text-white">{{ $r['nome'] }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10 flex justify-center">
            <a href="#" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C]">Vedi tutto</a>
        </div>
    </section>

    {{-- ============ EVENTI ============ --}}
    <section id="eventi">
        {{-- Banda immagine con gradiente XD: nero 60% a sx → trasparente a dx --}}
        <div class="relative isolate overflow-hidden">
            <img src="{{ asset('img/eventi-bg.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 -z-10 h-full w-full object-cover">
            <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,#00000099_0%,#71717100_100%)]"></div>
            <div class="{{ $px }} flex min-h-[660px] flex-col justify-end pb-10 pt-24">
                <h2 class="text-4xl font-extrabold text-white">Eventi pet friendly</h2>
                <p class="mt-3 max-w-xl text-lg text-white/85">Esperienze, gite e attività da vivere insieme al tuo amico a quattro zampe.</p>
                <a href="#" class="mt-12 w-fit rounded-full bg-brand-cyan px-6 py-3 text-[15px] font-extrabold text-white transition hover:bg-[#68CDEB]">Scopri gli eventi</a>
            </div>
        </div>
        <div class="{{ $px }} pb-16 pt-6">
            <div class="grid grid-cols-5 gap-6">
                @foreach ($eventi as $e)
                    <div wire:key="ev-{{ $loop->index }}" class="group rounded-[3px] border border-[#E9E9E9] bg-white p-2">
                        <div class="relative overflow-hidden">
                            <img src="{{ asset('img/xd/'.$e['img'].'.jpg') }}" alt="{{ $e['titolo'] }}" class="max-h-[227px] w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-2 pt-3">
                            <p class="flex items-center gap-1.5 text-[13px] text-brand-purple-soft">
                                <flux:icon.time class="h-4 w-4 shrink-0" />
                                {{ $e['data'] }}
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-[13px] text-[#555555]">
                                <flux:icon.pin class="h-4 w-4 shrink-0 text-[#555555]" />
                                {{ $e['luogo'] }}
                            </p>
                            <h3 class="mt-[10px] text-[20px] font-semibold leading-snug text-black">{{ $e['titolo'] }}</h3>
                            <div class="mt-4 flex items-center justify-between gap-2">
                                <flux:button href="#" size="sm" class="!rounded-full !border-0 !bg-[#E9E9E9] !px-5 !text-sm !text-[#0D171A] !shadow-none hover:!bg-brand-yellow">
                                    <flux:icon.check-1 class="h-4 w-4" />
                                    Partecipa
                                </flux:button>
                                @if ($e['prezzo'])
                                    <p class="text-[15px] font-normal text-[#627277]">A partire da <span class="whitespace-nowrap font-semibold text-[#0D171A]">€ {{ $e['prezzo'] }}</span></p>
                                @else
                                    <p class="text-[15px] italic text-[#627277]">Gratis</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 flex justify-center">
                <a href="#" class="rounded-full bg-[#0D171A] px-8 py-4 text-sm font-extrabold text-white transition hover:bg-[#232A2C]">Vedi tutto</a>
            </div>
        </div>
    </section>

    {{-- ============ SMARTBOX ============ --}}
    <section id="smartbox" class="{{ $px }} py-20">
        <div class="relative overflow-hidden rounded-[2.5rem]">
            <img src="{{ asset('img/xd/smartbox.jpg') }}" alt="Smartbox" class="h-[420px] w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-ink/85 via-ink/50 to-transparent"></div>
            <div class="absolute inset-y-0 left-0 flex max-w-xl flex-col justify-center p-16 text-white">
                <span class="inline-block w-fit rounded-full bg-brand-magenta px-4 py-1.5 text-xs font-extrabold uppercase tracking-wide">Idea regalo</span>
                <h2 class="mt-5 text-5xl font-extrabold leading-tight">Acquista una Smartbox</h2>
                <p class="mt-4 text-lg text-gray-200">Cofanetti di soggiorni ed esperienze pet-friendly. Il regalo perfetto per chi ama viaggiare con il proprio animale.</p>
                <a href="#" class="mt-8 w-fit rounded-full bg-brand-yellow px-8 py-4 text-sm font-extrabold text-ink transition hover:brightness-95">Trova il regalo giusto</a>
            </div>
        </div>
    </section>

    {{-- ============ NEWS ============ --}}
    <section id="news" class="{{ $px }} py-20">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <h2 class="text-4xl font-extrabold">News</h2>
                <p class="mt-3 max-w-xl text-lg text-gray-500">Normative, trasporti e consigli per viaggiare sereni con il tuo animale.</p>
            </div>
            <a href="#" class="hidden shrink-0 text-sm font-extrabold text-brand-cyan hover:underline md:block">Tutte le news →</a>
        </div>
        <div class="grid grid-cols-2 gap-8">
            @foreach ($news as $n)
                <a href="#" wire:key="news-{{ $loop->index }}" class="group flex items-center gap-6 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-150 transition hover:shadow-lg">
                    <img src="{{ asset('img/xd/'.$n['img'].'.jpg') }}" alt="{{ $n['titolo'] }}" class="h-48 w-56 shrink-0 object-cover transition duration-500 group-hover:scale-105">
                    <div class="py-6 pr-6">
                        <p class="text-sm font-bold text-gray-400">{{ $n['data'] }}</p>
                        <h3 class="mt-2 text-2xl font-extrabold leading-snug">{{ $n['titolo'] }}</h3>
                        <span class="mt-4 inline-block text-sm font-extrabold text-brand-cyan">Leggi l'articolo →</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ COMMUNITY ============ --}}
    <section id="community" class="bg-brand-cyan-bg py-20">
        <div class="{{ $px }} grid grid-cols-2 items-center gap-16">
            <div>
                <h2 class="text-4xl font-extrabold">Community</h2>
                <p class="mt-3 max-w-md text-lg text-gray-500">Confrontati con altri pet-lover: consigli, racconti di viaggio e domande prima di partire.</p>
                <a href="#" class="mt-8 inline-block rounded-full bg-ink px-8 py-4 text-sm font-extrabold text-white transition hover:bg-ink-800">Scopri la community</a>
            </div>
            {{-- Post preview card --}}
            <div class="rounded-3xl bg-white p-7 shadow-[0_20px_60px_-25px_rgba(7,24,37,0.35)]">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('img/xd/community.jpg') }}" alt="Sofia" class="h-12 w-12 rounded-full object-cover">
                    <div>
                        <p class="font-extrabold">Sofia</p>
                        <p class="text-sm text-gray-400">25/11/23</p>
                    </div>
                    <span class="ml-auto rounded-full bg-brand-purple-soft/30 px-3 py-1 text-xs font-extrabold text-brand-purple">benessere</span>
                </div>
                <p class="mt-5 text-gray-600">"Qualcuno ha consigli per un primo viaggio in treno con un cane di taglia media? Vorrei che fosse un'esperienza tranquilla per entrambi 🐾"</p>
                <div class="mt-6 flex items-center gap-2 border-t border-gray-150 pt-5 text-sm font-bold text-gray-400">
                    <flux:icon.chat class="h-5 w-5 text-brand-cyan" />
                    6 Risposte
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-ink text-gray-300">
        <div class="{{ $px }} grid grid-cols-4 gap-10 py-16">
            <div class="col-span-1">
                <span class="text-2xl font-extrabold text-white">Animal<span class="text-brand-cyan">Amo</span></span>
                <p class="mt-4 max-w-xs text-sm text-gray-400">Piattaforma multicanale di viaggi e servizi pet-friendly. Prenota, colleziona punti, vivi esperienze.</p>
            </div>
            <div>
                <h4 class="text-sm font-extrabold uppercase tracking-wide text-white">Esplora</h4>
                <ul class="mt-4 space-y-2 text-sm text-gray-400">
                    <li><a href="#holiday" class="hover:text-brand-cyan">Animal Holiday</a></li>
                    <li><a href="#eventi" class="hover:text-brand-cyan">Eventi</a></li>
                    <li><a href="#smartbox" class="hover:text-brand-cyan">Smartbox</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-extrabold uppercase tracking-wide text-white">Scopri</h4>
                <ul class="mt-4 space-y-2 text-sm text-gray-400">
                    <li><a href="#news" class="hover:text-brand-cyan">News</a></li>
                    <li><a href="#community" class="hover:text-brand-cyan">Community</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">Lavora con noi</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-extrabold uppercase tracking-wide text-white">Assistenza</h4>
                <ul class="mt-4 space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-brand-cyan">Chi siamo</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">Contatti</a></li>
                    <li><a href="#" class="hover:text-brand-cyan">Diventa partner</a></li>
                </ul>
            </div>
        </div>
        <div class="{{ $px }} flex items-center justify-between border-t border-white/10 py-6 text-xs text-gray-500">
            <span>© 2024 AnimalAmo. Tutti i diritti riservati.</span>
            <span>Privacy · Cookie · Termini</span>
        </div>
    </footer>
</div>
