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
        ['img' => 'event-cavallo',  'titolo' => 'Passeggiata a cavallo',                'luogo' => 'Genova, Italia',              'data' => null,                    'gratis' => true],
        ['img' => 'event-mare',     'titolo' => 'Weekend al mare',                      'luogo' => 'Fiesole (FI), Toscana',       'data' => 'LUN, 8 GEN ALLE 19:30', 'gratis' => false],
        ['img' => 'event-asini',    'titolo' => 'Esperienza con gli asini in fattoria', 'luogo' => 'Manciano (GR), Toscana',      'data' => null,                    'gratis' => false],
        ['img' => 'event-maneggio', 'titolo' => 'Weekend in maneggio',                  'luogo' => 'Massa Lubrense (NA), Campania','data' => 'VEN, 18 GEN ALLE 15:00','gratis' => false],
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
            <a href="/" class="text-2xl font-extrabold tracking-tight">Animal<span class="text-brand-cyan">Amo</span></a>
            <nav class="hidden items-center gap-9 text-sm font-bold text-ink lg:flex">
                <a href="#holiday"   class="hover:text-brand-cyan">Animal Holiday</a>
                <a href="#eventi"    class="hover:text-brand-cyan">Eventi</a>
                <a href="#smartbox"  class="hover:text-brand-cyan">Smartbox</a>
                <a href="#news"      class="hover:text-brand-cyan">News</a>
                <a href="#community" class="hover:text-brand-cyan">Community</a>
            </nav>
            <div class="flex items-center gap-5">
                <button class="text-ink hover:text-brand-magenta" aria-label="Preferiti">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                </button>
                <button class="text-ink hover:text-brand-cyan" aria-label="Carrello">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z"/></svg>
                </button>
                <button class="rounded-full bg-brand-yellow px-6 py-2.5 text-sm font-extrabold text-ink transition hover:brightness-95">Accedi</button>
            </div>
        </div>
    </header>

    {{-- ============ HERO ============ --}}
    <section class="bg-brand-cyan-bg">
        <div class="{{ $px }} grid grid-cols-2 items-center gap-16 py-20">
            <div>
                <span class="inline-block rounded-full bg-white px-4 py-1.5 text-xs font-extrabold uppercase tracking-wide text-brand-cyan">Viaggi pet-friendly</span>
                <h1 class="mt-6 text-6xl font-extrabold leading-[1.05] tracking-tight">Viaggia con il tuo<br>migliore amico.</h1>
                <p class="mt-6 max-w-lg text-lg text-gray-500">Soggiorni, esperienze e servizi pensati per te e per il tuo animale. Prenoti tutto in un unico posto, con i punti che diventano vantaggi.</p>

                {{-- Search bar: Dove / Quando --}}
                <div class="mt-9 flex w-fit items-center gap-2 rounded-full bg-white p-2 shadow-[0_10px_40px_-12px_rgba(7,24,37,0.25)]">
                    <label class="flex flex-col px-6 py-1.5">
                        <span class="text-[11px] font-extrabold uppercase tracking-wide text-ink">Dove</span>
                        <input type="text" placeholder="Cerca una destinazione" class="w-52 border-0 p-0 text-sm text-gray-600 placeholder-gray-400 focus:ring-0">
                    </label>
                    <span class="h-9 w-px bg-gray-200"></span>
                    <label class="flex flex-col px-6 py-1.5">
                        <span class="text-[11px] font-extrabold uppercase tracking-wide text-ink">Quando</span>
                        <input type="text" placeholder="Aggiungi le date" class="w-44 border-0 p-0 text-sm text-gray-600 placeholder-gray-400 focus:ring-0">
                    </label>
                    <button class="ml-1 flex items-center gap-2 rounded-full bg-brand-yellow px-8 py-4 text-sm font-extrabold text-ink transition hover:brightness-95">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/></svg>
                        Cerca
                    </button>
                </div>
            </div>

            <div class="relative">
                <img src="{{ asset('img/xd/hero.jpg') }}" alt="Viaggio pet-friendly" class="aspect-[5/4] w-full rounded-[2rem] object-cover shadow-xl">
                <div class="absolute -bottom-6 -left-6 flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-lg">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-brand-magenta/10 text-brand-magenta">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l7.1-1.01L12 2z"/></svg>
                    </span>
                    <div class="text-sm"><p class="font-extrabold">+2.400 strutture</p><p class="text-gray-400">verificate pet-friendly</p></div>
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
            <a href="#" class="hidden shrink-0 items-center gap-2 text-sm font-extrabold text-brand-cyan hover:underline md:flex">Vedi tutte le regioni →</a>
        </div>
        <div class="grid grid-cols-3 gap-6">
            @foreach ($regioni as $r)
                <a href="#" wire:key="reg-{{ $loop->index }}" class="group relative overflow-hidden rounded-3xl">
                    <img src="{{ asset('img/xd/'.$r['img'].'.jpg') }}" alt="{{ $r['nome'] }}" class="h-80 w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                        <span class="inline-block rounded-full bg-brand-yellow px-3 py-1 text-xs font-extrabold text-ink">{{ $r['strutture'] }} Strutture</span>
                        <h3 class="mt-3 text-xl font-extrabold">{{ $r['nome'] }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ EVENTI ============ --}}
    <section id="eventi" class="bg-gray-100 py-20">
        <div class="{{ $px }}">
            <div class="mb-10 flex items-end justify-between">
                <div>
                    <h2 class="text-4xl font-extrabold">Eventi pet friendly</h2>
                    <p class="mt-3 max-w-xl text-lg text-gray-500">Esperienze, gite e attività da vivere insieme al tuo amico a quattro zampe.</p>
                </div>
                <a href="#" class="shrink-0 rounded-full bg-ink px-6 py-3 text-sm font-extrabold text-white transition hover:bg-ink-800">Scopri gli eventi</a>
            </div>
            <div class="grid grid-cols-4 gap-6">
                @foreach ($eventi as $e)
                    <a href="#" wire:key="ev-{{ $loop->index }}" class="group overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-150 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="relative">
                            <img src="{{ asset('img/xd/'.$e['img'].'.jpg') }}" alt="{{ $e['titolo'] }}" class="h-44 w-full object-cover">
                            @if ($e['gratis'])
                                <span class="absolute left-3 top-3 rounded-full bg-brand-magenta px-3 py-1 text-xs font-extrabold text-white">Gratis</span>
                            @endif
                        </div>
                        <div class="p-5">
                            @if ($e['data'])
                                <p class="text-xs font-extrabold uppercase tracking-wide text-brand-magenta">{{ $e['data'] }}</p>
                            @else
                                <p class="text-xs font-extrabold uppercase tracking-wide text-brand-cyan">Sempre disponibile</p>
                            @endif
                            <h3 class="mt-2 text-lg font-extrabold leading-snug">{{ $e['titolo'] }}</h3>
                            <p class="mt-2 flex items-center gap-1 text-sm text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0z"/></svg>
                                {{ $e['luogo'] }}
                            </p>
                        </div>
                    </a>
                @endforeach
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
                    <svg class="h-5 w-5 text-brand-cyan" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
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
