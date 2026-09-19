{{--
    Pulsante a pillola del pannello, sopra flux:button.
    tone: primary (scuro), outline (bianco bordato), ghost (senza fondo), danger (rosa).
    Tutti gli altri attributi (wire:click, href, icon, type…) passano a flux:button.
--}}
@props(['tone' => 'outline'])

@php
    $tones = [
        'primary' => '!border-0 !bg-admin-cta !text-white hover:!bg-admin-rail',
        'outline' => '!border !border-gray-300 !bg-white !font-semibold !text-admin-rail hover:!bg-gray-100',
        'ghost' => '!border-0 !bg-transparent !font-semibold !text-gray-600 hover:!text-admin-rail',
        'danger' => '!border !border-[#FDEBE8] !bg-[#FDEBE8] !text-[#C4351A] hover:!bg-[#FAD9D3]',
    ];
@endphp

<flux:button {{ $attributes->class([
    '!h-10 !gap-[7px] !rounded-full !px-[18px] !text-[14.5px] !font-bold !shadow-none whitespace-nowrap',
    '[&>span]:flex [&>span]:items-center [&>span]:gap-2',
    $tones[$tone] ?? $tones['outline'],
]) }}>{{ $slot }}</flux:button>
