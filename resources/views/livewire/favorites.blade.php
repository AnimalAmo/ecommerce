{{-- Preferiti — griglia card salvate + stato vuoto (XD: "Preferiti – 2" / "Preferiti - nessuno") --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';

    // Chip tag sopra la foto: bg per tag, testo SEMPRE bianco (in XD anche sui colori chiari).
    $chipClasses = [
        'Attività' => 'bg-[#8E53E6]',
        'Struttura' => 'bg-[#FF9F3E]',
        'Evento' => 'bg-[#C59FFD]',
        'Benessere' => 'bg-[#8DABFF]',
        'Soggiorno' => 'bg-[#8DE0FF]',
    ];
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
                                    {{-- Card "Box preferiti" 468x170: foto a sinistra offset 6px, colonna testo a destra --}}
                                    <article wire:key="favorite-{{ $item['id'] }}" class="relative flex h-[170px] w-full rounded-[3px] border border-[#E9E9E9] bg-white p-[6px]">
                                        <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

                                        {{-- Chip tag sovrapposta alla foto in alto a sinistra --}}
                                        <span class="absolute left-[11px] top-[13px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white {{ $chipClasses[$item['tag']] ?? 'bg-[#555555]' }}">{{ $item['tag'] }}</span>

                                        <div class="flex min-w-0 flex-1 flex-col pb-px pl-1 pr-1 pt-[7px]">
                                            <h2 class="line-clamp-2 pr-[9px] text-base font-semibold leading-[22px] text-black">{{ $item['title'] }}</h2>
                                            <div class="mr-[9px] mt-2 h-px shrink-0 bg-[#E9E9E9]" aria-hidden="true"></div>

                                            {{-- Riga meta per tipo: star/calendar custom tintabili; user/clock/shopping-bag = heroicon più vicine (approssimazione accettata a queste taglie) --}}
                                            <div class="mt-[6px] flex items-center gap-[6px] text-brand-magenta">
                                                @switch($item['metaType'])
                                                    @case('rating')
                                                        <flux:icon.star class="h-[11px] w-[11px] shrink-0" />
                                                        @break
                                                    @case('data')
                                                        <flux:icon.calendar class="h-[11px] w-[11px] shrink-0" />
                                                        @break
                                                    @case('persone')
                                                        <flux:icon.user class="!h-[11px] !w-[11px] shrink-0" />
                                                        @break
                                                    @case('durata')
                                                        <flux:icon.clock class="!h-[11px] !w-[11px] shrink-0" />
                                                        @break
                                                @endswitch
                                                <span class="truncate text-[11px] font-bold leading-none">{{ $item['metaText'] }}</span>
                                            </div>

                                            {{-- Riga località --}}
                                            <div class="mt-[9px] flex items-center gap-[6px] text-[#555555]">
                                                <flux:icon.pin class="h-[10px] w-[10px] shrink-0" />
                                                <span class="truncate text-[11px] font-semibold leading-none">{{ $item['location'] }}</span>
                                            </div>

                                            {{-- Cluster prezzo + borsa + cuore in basso a destra.
                                                 I due gialli sono VOLUTAMENTE diversi da XD: cuore sempre brand-yellow #EDFF00,
                                                 borsa attiva ("in carrello") #FFE13E. --}}
                                            <div class="mt-auto flex items-center justify-end gap-[5px]">
                                                <span class="mr-[7px] text-[11px] font-semibold leading-none text-[#0D171A]">A partire da {{ $item['price'] }}</span>
                                                <flux:button wire:click="toggleCart({{ $item['id'] }})" square aria-label="Aggiungi al carrello" class="!h-[30px] !w-[30px] !min-w-0 !rounded-full !border-0 !shadow-none [&>span]:flex [&>span]:items-center [&>span]:justify-center {{ in_array($item['id'], $inCart, true) ? '!bg-[#FFE13E] hover:!bg-[#FFE13E]' : '!bg-gray-100 hover:!bg-gray-100' }}">
                                                    <flux:icon.shopping-bag class="!h-[14px] !w-[14px] text-black" />
                                                </flux:button>
                                                <flux:button wire:click="removeFavorite({{ $item['id'] }})" square aria-label="Rimuovi dai preferiti" class="!h-[30px] !w-[30px] !min-w-0 !rounded-full !border-0 !bg-brand-yellow !shadow-none hover:!bg-brand-yellow [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                                                    <flux:icon.heart class="h-[14px] w-[14px] text-black" />
                                                </flux:button>
                                            </div>
                                        </div>
                                    </article>
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
