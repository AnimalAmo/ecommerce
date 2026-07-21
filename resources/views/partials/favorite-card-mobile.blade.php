{{-- Card preferito mobile (XD app "Preferiti", symbol "Card strutture - servizi" 343x333):
     foto 327x184 con tag e cuore giallo sopra, righe pin/meta, titolo, divider e prezzo a destra.
     Contratto: $item (card FavoriteService), $wireKey, $heartAction (stringa wire:click).
     Niente bottone borsa: nell'artboard mobile la card ha il solo cuore. --}}
@php $itemType = \App\Enums\ProductType::from($item['type']); @endphp

<article wire:key="{{ $wireKey }}" class="rounded-[3px] border border-[#E9E9E9] bg-white p-2 pb-[14px]">
    <div class="relative">
        <img src="{{ $item['photo'] }}" alt="{{ $item['title'] }}" class="h-[184px] w-full rounded-t-[3px] object-cover">

        {{-- Tag e cuore sono a filo della foto (10px dentro), non del bordo card --}}
        <span class="absolute left-[10px] top-[10px] flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>

        {{-- Qui il cuore è sempre attivo (giallo) e cancella la riga favorites --}}
        <flux:button wire:click="{{ $heartAction }}" square aria-label="{{ __('nav.card.remove_from_favorites') }}" class="!absolute !right-[6px] !top-[10px] !h-[26px] !w-[26px] !min-w-0 !rounded-full !border-0 !bg-brand-yellow !shadow-none hover:!bg-brand-yellow [&>span]:flex [&>span]:items-center [&>span]:justify-center">
            <flux:icon.heart class="h-3 w-[15px] text-black" />
        </flux:button>
    </div>

    <div class="px-[2px]">
        {{-- Riga località (vuota per le smartbox partner senza audience: nascosta) --}}
        @if (filled($item['location']))
            <div class="mt-3 flex items-center gap-[9px] text-[#555555]">
                <flux:icon.pin class="h-[14px] w-[11px] shrink-0" />
                <span class="truncate text-[13px] font-semibold leading-none">{{ $item['location'] }}</span>
            </div>
        @endif

        <div class="mt-[9px] flex items-center gap-1 text-[#555555]">
            @include('partials.card-meta-icon', ['metaType' => $item['metaType'], 'class' => '!h-[15px] !w-4'])
            <span class="truncate text-[13px] font-semibold leading-none">{{ $item['metaText'] }}</span>
        </div>

        <h2 class="mt-[11px] truncate text-lg font-semibold leading-none text-[#0D171A]">{{ $item['title'] }}</h2>

        {{-- Il divider sborda i 2px del testo: nell'XD è largo quanto la foto --}}
        <div class="-mx-[2px] mt-[19px] h-px bg-[#F2F2F2]" aria-hidden="true"></div>

        <p class="mt-[11px] text-right text-[15px] font-semibold leading-none text-[#0D171A]">{{ __('nav.card.starting_from', ['price' => $item['price']]) }}</p>
    </div>
</article>
