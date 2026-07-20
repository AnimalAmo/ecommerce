{{-- Chip tipologie attive, solo mobile (XD app: pill #EBF9FD testo #4FB8D8 con X).
     Con le chip Smartbox la riga sfora i 375px: scorre in orizzontale ("Scorri gruppo 3"). --}}
<div class="-mx-4 mt-4 flex items-center gap-1.5 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:hidden">
    {{-- Chip fascia di prezzo (XD app "Cerca - risultati – click su 'filtri'": "9 - 415 €" con X) --}}
    @if ($priceMin !== $priceFloor || $priceMax !== $priceCeil)
        <flux:button wire:click="resetPrice" class="!flex !h-[31px] !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
            {{ $priceMin }} - {{ $priceMax }} €
            <flux:icon.close class="h-2.5 w-2.5" />
        </flux:button>
    @endif
    @foreach ($activeTypes as $activeType)
        <flux:button wire:key="chip-{{ $activeType }}" wire:click="removeType('{{ $activeType }}')" class="!flex !h-[31px] shrink-0 !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
            {{ $activeType === 'hotel' ? __('catalog.badge_hotel') : __('catalog.filter_types.'.$activeType) }}
            <flux:icon.close class="h-2.5 w-2.5" />
        </flux:button>
    @endforeach
    {{-- Chip Smartbox (XD app "Cerca - risultati - click 'filtri' – 2": Soggiorno/Benessere/Avventura) --}}
    @foreach ($smartboxTypes as $smartboxType)
        <flux:button wire:key="chip-sb-{{ $smartboxType }}" wire:click="toggleSmartboxType('{{ $smartboxType }}')" class="!flex !h-[31px] shrink-0 !items-center !rounded-full !border-0 !bg-[#EBF9FD] !px-4 !text-sm !font-normal !text-[#4FB8D8] !shadow-none [&>span]:!flex [&>span]:!items-center [&>span]:!gap-2">
            {{ __('catalog.smartbox_chips.'.$smartboxType) }}
            <flux:icon.close class="h-2.5 w-2.5" />
        </flux:button>
    @endforeach
</div>
