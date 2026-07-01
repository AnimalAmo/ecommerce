<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('AnimalAmo — Home')] class extends Component
{
    /**
     * Section skeleton extracted from XD "Homepage – 4" (top → bottom).
     * Real pixel-match build lands next, driven by design/home.png.
     */
    public array $sections = [
        ['key' => 'hero',        'title' => 'Hero + ricerca (Dove / Quando)'],
        ['key' => 'holiday',     'title' => 'Animal Holiday — strutture per regione'],
        ['key' => 'eventi',      'title' => 'Eventi pet friendly'],
        ['key' => 'smartbox',    'title' => 'Acquista una Smartbox'],
        ['key' => 'news',        'title' => 'News'],
        ['key' => 'community',   'title' => 'Community'],
    ];
};
?>

<div class="min-h-screen bg-white text-ink font-sans">
    {{-- Placeholder header (final header comes from XD symbols + home.png) --}}
    <header class="flex items-center justify-between px-[140px] h-20 border-b border-gray-150">
        <span class="text-2xl font-extrabold tracking-tight">Animal<span class="text-brand-cyan">Amo</span></span>
        <nav class="flex gap-8 text-sm font-semibold text-gray-600">
            <span>Animal Holiday</span><span>Eventi</span><span>Smartbox</span>
            <span>News</span><span>Community</span>
        </nav>
        <button class="rounded-full bg-brand-yellow px-6 py-2 text-sm font-bold text-ink">Accedi</button>
    </header>

    {{-- Hero band (cyan bg matches XD #EBF9FD) --}}
    <section class="bg-brand-cyan-bg px-[140px] py-24">
        <h1 class="max-w-3xl text-5xl font-extrabold leading-tight">Viaggi e servizi pet-friendly</h1>
        <p class="mt-4 max-w-xl text-lg text-gray-600">Prenota soggiorni, esperienze e servizi per te e il tuo animale.</p>
        <div class="mt-8 inline-flex gap-3 rounded-full bg-white p-2 shadow-sm">
            <span class="px-6 py-3 text-sm font-semibold text-gray-500">Dove</span>
            <span class="px-6 py-3 text-sm font-semibold text-gray-500">Quando</span>
            <button class="rounded-full bg-brand-yellow px-8 py-3 text-sm font-bold text-ink">Cerca</button>
        </div>
    </section>

    {{-- Section skeleton --}}
    <main class="px-[140px] py-16 space-y-12">
        <p class="rounded-lg bg-brand-yellow-soft/40 px-4 py-3 text-sm font-semibold text-ink">
            🏗️ Shell in costruzione — in attesa di <code>design/home.png</code> per il pixel-match.
        </p>
        @foreach ($sections as $i => $s)
            <section wire:key="{{ $s['key'] }}" class="border-b border-gray-150 pb-8">
                <div class="flex items-baseline gap-3">
                    <span class="text-sm font-bold text-brand-magenta">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h2 class="text-3xl font-bold">{{ $s['title'] }}</h2>
                </div>
            </section>
        @endforeach
    </main>

    {{-- Placeholder footer --}}
    <footer class="bg-ink px-[140px] py-16 text-sm text-gray-300">
        <span class="text-xl font-extrabold text-white">Animal<span class="text-brand-cyan">Amo</span></span>
        <p class="mt-3 max-w-md text-gray-400">Piattaforma multicanale di viaggi e servizi pet-friendly.</p>
    </footer>
</div>
