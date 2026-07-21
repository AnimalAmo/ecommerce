{{-- Card "Le attività più amate su Animal-Amo" (XD app "Preferiti - vuoti", symbol "Card ' i più acquistati'" 280x200):
     foto 264x184 con gradiente scuro sul fondo, tag e cuore in alto, titolo/pin/meta bianchi in basso.
     Contratto: $item (card FavoriteService::topFavorited), $heartActive/$heartAction come su partials/favorite-card. --}}
@php $itemType = \App\Enums\ProductType::from($item['type']); @endphp

<article wire:key="most-loved-{{ $item['id'] }}" class="w-[280px] shrink-0 snap-start rounded-[3px] border border-[#E9E9E9] bg-white p-2">
    <div class="relative h-[184px] overflow-hidden rounded-[3px]">
        <img src="{{ $item['photo'] }}" alt="{{ $item['title'] }}" class="h-full w-full object-cover">

        {{-- Gradiente XD: dal basso #000000CC, 47% #1212128D, in alto #54545400 --}}
        <div class="absolute inset-x-0 bottom-0 h-[141px] bg-[linear-gradient(to_top,#000000CC_0%,#1212128D_47%,#54545400_100%)]" aria-hidden="true"></div>

        <span class="absolute left-[7px] top-2 flex h-[26px] items-center rounded-[3px] px-[10px] text-[13px] font-medium text-white" style="background-color: {{ $itemType->color() }}">{{ $itemType->label() }}</span>

        {{-- Cuore bianco che diventa giallo da preferito (stessi gialli della card desktop) --}}
        <flux:button wire:click="{{ $heartAction }}" square aria-label="{{ $heartActive ? __('nav.card.remove_from_favorites') : __('nav.card.add_to_favorites') }}" class="!absolute !right-[6px] !top-2 !z-[2] !h-[26px] !w-[26px] !min-w-0 !rounded-full !border-0 !shadow-none [&>span]:flex [&>span]:items-center [&>span]:justify-center {{ $heartActive ? '!bg-brand-yellow hover:!bg-brand-yellow' : '!bg-[#fff] hover:!bg-[#fff]' }}">
            <flux:icon.heart class="h-3 w-[15px] text-black" />
        </flux:button>

        <div class="absolute inset-x-0 bottom-[13px] px-2 text-white">
            <h3 class="truncate text-[15px] font-bold leading-none">{{ $item['title'] }}</h3>

            @if (filled($item['location']))
                <div class="mt-[11px] flex items-center gap-[9px]">
                    <flux:icon.pin class="h-3 w-[9px] shrink-0" />
                    <span class="truncate text-xs leading-none">{{ $item['location'] }}</span>
                </div>
            @endif

            <div class="mt-[8px] flex items-center gap-[5px]">
                @include('partials.card-meta-icon', ['metaType' => $item['metaType'], 'class' => '!h-[13px] !w-[14px]'])
                <span class="truncate text-xs leading-none">{{ $item['metaText'] }}</span>
            </div>
        </div>
    </div>
</article>
