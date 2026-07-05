{{-- Preferiti — griglia card salvate + stato vuoto (XD: "Preferiti – 2" / "Preferiti - nessuno") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pb-[140px] pt-14">
            <div class="mx-auto w-full max-w-[1496px]">
                <h1 class="text-4xl font-bold leading-none text-black">Preferiti</h1>

                @if ($favorites === [])
                    {{-- Stato vuoto (artboard "Preferiti - nessuno"): card 865x358 centrata, stesso look bianco/50 --}}
                    {{-- Il doodle ciano a mano libera (Tracciato 654/655) è SALTATO: il Tracciato 655 arriva con offset master corrotti nel .xd --}}
                    <div class="mx-auto mt-[53px] flex min-h-[358px] w-full max-w-[865px] flex-col items-center rounded-[3px] border border-[#E9E9E9] bg-white px-6 pt-[82px] shadow-[0px_1px_10px_#0000001A] text-center">
                        <h2 class="text-2xl font-bold leading-none text-[#68CDEB]">Aggiungi nuove avventure nei preferiti</h2>
                        <p class="mt-[41px] text-lg font-medium leading-[25px] text-black">Durante la ricerca hai trovato esperienze di tuo interesse?<br>Ti basta cliccare sull’icona a forma di cuore e verranno salvate qui.</p>
                        <flux:button href="{{ url('/animal-holiday') }}" class="mt-[34px] !h-10 !w-[174px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan">Lasciati ispirare</flux:button>
                    </div>
                @else
                    {{-- Riga filtro a destra sopra il contenitore (assente nello stato vuoto) --}}
                    <div class="mt-[29px] flex h-[30px] items-center justify-end gap-3">
                        <span class="text-base leading-none text-black">Filtra:</span>
                        {{-- Pannello a filo sotto la pill (offset/gap 0), angoli alti squadrati come da XD.
                             NOTA: il symbol master XD mostra solo Evento/Attività; esteso a "Tutte" + i 5 tag in pagina per funzionalità, stesso styling. --}}
                        <flux:dropdown align="start" offset="0" gap="0">
                            <flux:button class="!h-[30px] !w-[105px] !justify-between !rounded-full !border !border-[#C8C8C8] !bg-white !px-3 !text-sm !font-normal !text-[#555555] !shadow-none">
                                Tipologia
                                <flux:icon.chevron-down class="!h-3 !w-3 shrink-0 text-[#555555]" />
                            </flux:button>
                            <flux:menu class="!w-[105px] !min-w-0 !rounded-t-none !rounded-b-[10px] !border-[#C8C8C8] !bg-white !px-0 !py-1 !shadow-none">
                                <flux:menu.item wire:click="setTypeFilter(null)" class="!h-[35px] !rounded-none !px-4 !py-0 !text-sm !font-normal !text-[#555555] data-active:!bg-gray-50">Tutte</flux:menu.item>
                                @foreach ($types as $type)
                                    <flux:menu.item wire:key="type-{{ $type }}" wire:click="setTypeFilter('{{ $type }}')" class="!h-[35px] !rounded-none !px-4 !py-0 !text-sm !font-normal !text-[#555555] data-active:!bg-gray-50">{{ $type }}</flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    </div>

                    {{-- Contenitore griglia (XD lo disegna intero al 50% di opacità; le card hanno bg bianco pieno proprio) --}}
                    <div class="mt-4 rounded-[3px] border border-[#E9E9E9] bg-white p-6 shadow-[0px_1px_10px_#0000001A]">
                        @if ($visibleFavorites === [])
                            {{-- Filtro tipologia senza risultati (nessun design XD; stessa riga minimale della community) --}}
                            <p class="text-[15px] leading-[21px] text-[#959595]">Nessun risultato</p>
                        @else
                            {{-- Griglia 3 colonne: card 468 + gap 22 (passo colonna 490), gap riga 16.
                                 NOTA: breakpoint in rem (87.5rem = 1400px), NON min-[1400px]: Tailwind 4 non ordina
                                 media query px tra quelle rem e md:grid-cols-2 vincerebbe la cascata anche a 1920. --}}
                            <div class="grid grid-cols-1 gap-x-[22px] gap-y-4 md:grid-cols-2 min-[87.5rem]:grid-cols-3">
                                @foreach ($visibleFavorites as $item)
                                    {{-- Card condivisa: qui il cuore è sempre attivo (giallo) e rimuove il preferito --}}
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
                @endif
            </div>
        </div>
    </main>

    @include('partials.site-footer')

    {{-- Modali auth raggiungibili dall'header --}}
    <livewire:auth-modal />
    <livewire:register-modal />
    <livewire:partner-login-modal />
</div>
