{{-- Card "Box preferiti" 468x170 condivisa (XD symbol a7e925b1) — usata da /preferiti e dallo stato vuoto del carrello.
     Contratto: $item (array card), $wireKey, $heartActive/$bagActive (bool stato bottoni),
     $heartAction/$bagAction (stringhe wire:click, es. "removeFavorite(1)"). --}}
<article wire:key="{{ $wireKey }}" class="relative flex h-[170px] w-full rounded-[3px] border border-[#E9E9E9] bg-white p-[6px]">
    <img src="{{ asset('img/xd/' . $item['photo']) }}" alt="{{ $item['title'] }}" class="h-[158px] w-[163px] shrink-0 rounded-[2px] object-cover">

    {{-- Chip tag sovrapposta alla foto in alto a sinistra (testo SEMPRE bianco, anche sui colori chiari come in XD);
         label e colore dalla tassonomia ProductType ($item['type'] = value dell'enum) --}}
    @php $itemType = \App\Enums\ProductType::from($item['type']); @endphp
    <span class="absolute left-[11px] top-[13px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>

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
             I due gialli sono VOLUTAMENTE diversi da XD: cuore attivo sempre brand-yellow #EDFF00,
             borsa attiva ("in carrello") #FFE13E; gli stati inattivi sono grigio #F4F4F4 (borsa)
             e bianco bordato #E9E9E9 con cuore nero outline (cuore). --}}
        <div class="mt-auto flex items-center justify-end gap-[5px]">
            <span class="mr-[7px] text-[11px] font-semibold leading-none text-[#0D171A]">A partire da {{ $item['price'] }}</span>
            <flux:button wire:click="{{ $bagAction }}" square aria-label="{{ $bagActive ? 'Rimuovi dal carrello' : 'Aggiungi al carrello' }}" class="!h-[30px] !w-[30px] !min-w-0 !rounded-full !border-0 !shadow-none [&>span]:flex [&>span]:items-center [&>span]:justify-center {{ $bagActive ? '!bg-[#FFE13E] hover:!bg-[#FFE13E]' : '!bg-gray-100 hover:!bg-gray-100' }}">
                <flux:icon.shopping-bag class="!h-[14px] !w-[14px] text-black" />
            </flux:button>
            <flux:button wire:click="{{ $heartAction }}" square aria-label="{{ $heartActive ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti' }}" class="!h-[30px] !w-[30px] !min-w-0 !rounded-full !shadow-none [&>span]:flex [&>span]:items-center [&>span]:justify-center {{ $heartActive ? '!border-0 !bg-brand-yellow hover:!bg-brand-yellow' : '!border !border-[#E9E9E9] !bg-white hover:!bg-white' }}">
                <flux:icon.heart class="h-[14px] w-[14px] text-black" />
            </flux:button>
        </div>
    </div>
</article>
