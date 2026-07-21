{{-- Preferiti — griglia card salvate + stato vuoto (XD: "Preferiti – 2" / "Preferiti - nessuno";
     mobile: artboard app "Preferiti" e "Preferiti - vuoti") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pb-[140px] pt-14 max-lg:pb-8 max-lg:pt-5">
            <div class="mx-auto w-full max-w-[1496px]">
                {{-- Su mobile la pagina non ha titolo: la lista apre sulla riga di conteggio, lo stato vuoto sulla card --}}
                <h1 class="text-4xl font-bold leading-none text-black max-lg:hidden">{{ __('favorites.title') }}</h1>

                @if ($favorites === [])
                    {{-- Stato vuoto (artboard "Preferiti - nessuno"): card 865x358 centrata, stesso look bianco/50 --}}
                    {{-- Il doodle ciano a mano libera (Tracciato 654/655) è SALTATO: il Tracciato 655 arriva con offset master corrotti nel .xd --}}
                    <div class="mx-auto mt-[53px] flex min-h-[358px] w-full max-w-[865px] flex-col items-center rounded-[3px] border border-[#E9E9E9] bg-white px-6 pt-[82px] shadow-[0px_1px_10px_#0000001A] text-center max-lg:hidden">
                        <h2 class="text-2xl font-bold leading-none text-[#68CDEB]">{{ __('favorites.empty_heading') }}</h2>
                        <p class="mt-[41px] text-lg font-medium leading-[25px] text-black">{{ __('favorites.empty_text_line1') }}<br>{{ __('favorites.empty_text_line2') }}</p>
                        <flux:button href="{{ url('/animal-holiday') }}" class="mt-[34px] !h-10 !w-[174px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">{{ __('favorites.empty_cta') }}</flux:button>
                    </div>

                    {{-- Stato vuoto mobile (artboard "Preferiti - vuoti"): card 343x330 allineata a sinistra,
                         testo su due blocchi separati dalla riga vuota dell'XD e freccia disegnata a mano.
                         La CTA dell'artboard riporta l'etichetta "Aggiungi al carrello" del symbol da cui è
                         copiata: qui resta quella coerente della pagina ("Lasciati ispirare"), stessa azione. --}}
                    <div class="relative min-h-[330px] rounded-[3px] border border-[#E9E9E9] bg-white px-[17px] pb-[27px] pt-8 shadow-[0px_1px_5px_#0000001A] lg:hidden">
                        <h2 class="text-xl font-bold leading-[27px] text-[#68CDEB]">{{ __('favorites.empty_heading') }}</h2>
                        <p class="mt-[26px] text-[15px] font-medium leading-[22px] text-[#0D171A]">{{ __('favorites.empty_text_line1') }}</p>
                        <p class="mt-[22px] text-[15px] font-medium leading-[22px] text-[#0D171A]">{{ __('favorites.empty_text_line2') }}</p>
                        <flux:button href="{{ url('/animal-holiday') }}" class="relative !z-[1] !mt-[42px] !h-[39px] !rounded-full !border-0 !bg-brand-cyan !px-[33px] !text-sm !font-bold !text-white !shadow-[0px_1px_5px_#0000001A] hover:!bg-brand-cyan">{{ __('favorites.empty_cta') }}</flux:button>

                        @include('partials.favorites-empty-doodle')
                    </div>

                    {{-- "Le attività più amate su Animal-Amo": carosello orizzontale di card 280x200 (solo mobile) --}}
                    @if ($suggestions !== [])
                        <section class="lg:hidden">
                            <h2 class="mt-[25px] text-[15px] font-bold leading-none text-[#0D171A]">{{ __('favorites.most_loved') }}</h2>

                            {{-- Scorre fino al bordo schermo: -mr-4 annulla il padding del container --}}
                            <div class="-mr-4 mt-[17px] flex snap-x gap-4 overflow-x-auto pb-1">
                                @foreach ($suggestions as $item)
                                    @include('partials.most-loved-card-mobile', ['item' => $item])
                                @endforeach
                            </div>
                        </section>
                    @endif
                @else
                    {{-- Mobile: la pagina apre sulla riga di conteggio grigia (XD "Preferiti (2 prodotto)") --}}
                    @php $count = count($visibleFavorites); @endphp
                    <p class="text-sm leading-none text-[#959595] lg:hidden">{{ __('favorites.title') }} ({{ $count }} {{ $count === 1 ? __('favorites.count_one') : __('favorites.count_many') }})</p>

                    <div class="max-lg:hidden">
                        {{-- Riga filtro a destra sopra il contenitore (assente nello stato vuoto e su mobile) --}}
                        <div class="mt-[29px] flex h-[30px] items-center justify-end gap-3">
                            <span class="text-base leading-none text-black">{{ __('favorites.filter') }}</span>
                            {{-- Pannello a filo sotto la pill (offset/gap 0), angoli alti squadrati come da XD.
                                 NOTA: il symbol master XD mostra solo Evento/Attività; esteso a "Tutte" + le tipologie distinte presenti in pagina, stesso styling. --}}
                            <flux:dropdown align="start" offset="0" gap="0">
                                <flux:button class="!h-[30px] !w-[105px] !justify-between !rounded-full !border !border-[#C8C8C8] !bg-white !px-3 !text-sm !font-normal !text-[#555555] !shadow-none">
                                    {{ __('favorites.type') }}
                                    <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-[#555555]" />
                                </flux:button>
                                <flux:menu class="!w-[105px] !min-w-0 !rounded-t-none !rounded-b-[10px] !border-[#C8C8C8] !bg-white !px-0 !py-1 !shadow-none">
                                    <flux:menu.item wire:click="setTypeFilter(null)" class="!h-[35px] !rounded-none !px-4 !py-0 !text-sm !font-normal !text-[#555555] data-active:!bg-gray-50">{{ __('favorites.all') }}</flux:menu.item>
                                    @foreach ($types as $type)
                                        <flux:menu.item wire:key="type-{{ $type->value }}" wire:click="setTypeFilter('{{ $type->value }}')" class="!h-[35px] !rounded-none !px-4 !py-0 !text-sm !font-normal !text-[#555555] data-active:!bg-gray-50">{{ $type->label() }}</flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        {{-- Contenitore griglia (XD lo disegna intero al 50% di opacità; le card hanno bg bianco pieno proprio) --}}
                        <div class="mt-4 rounded-[3px] border border-[#E9E9E9] bg-white p-6 shadow-[0px_1px_10px_#0000001A]">
                            @if ($visibleFavorites === [])
                                {{-- Filtro tipologia senza risultati (nessun design XD; stessa riga minimale della community) --}}
                                <p class="text-[15px] leading-[21px] text-[#959595]">{{ __('favorites.no_results') }}</p>
                            @else
                                {{-- Griglia 3 colonne: card 468 + gap 22 (passo colonna 490), gap riga 16.
                                     NOTA: breakpoint in rem (87.5rem = 1400px), NON min-[1400px]: Tailwind 4 non ordina
                                     media query px tra quelle rem e md:grid-cols-2 vincerebbe la cascata anche a 1920. --}}
                                <div class="grid grid-cols-1 gap-x-[22px] gap-y-4 md:grid-cols-2 min-[87.5rem]:grid-cols-3">
                                    @foreach ($visibleFavorites as $item)
                                        {{-- Card condivisa: qui il cuore è sempre attivo (giallo) e cancella la riga favorites ($item['id'] = pk, unica tra i morph) --}}
                                        @include('partials.favorite-card', [
                                            'item' => $item,
                                            'wireKey' => 'favorite-' . $item['id'],
                                            'heartActive' => true,
                                            'heartAction' => 'removeFavorite(' . $item['id'] . ')',
                                            'bagActive' => in_array($item['id'], $inCart, true),
                                            'bagAction' => 'toggleCart(' . $item['id'] . ')',
                                        ])
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Lista mobile: card verticali a tutta larghezza, gap 16 (artboard app "Preferiti") --}}
                    <div class="mt-[31px] space-y-4 lg:hidden">
                        @foreach ($visibleFavorites as $item)
                            @include('partials.favorite-card-mobile', [
                                'item' => $item,
                                'wireKey' => 'favorite-mobile-' . $item['id'],
                                'heartAction' => 'removeFavorite(' . $item['id'] . ')',
                            ])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </main>

    @include('partials.site-footer')
</div>
