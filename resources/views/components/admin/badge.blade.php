{{--
    Etichetta di stato del pannello (colori del design):
    success = pubblicata/confermato, muted = sospesa/bozza, warning = in attesa,
    info = struttura/contatto, pink = attività, purple = smartbox/candidatura,
    danger = segnalata/fallito.
--}}
@props(['tone' => 'muted'])

@php
    $colors = [
        'success' => '!bg-[#E7F7EC] !text-[#1B7A3E]',
        'muted' => '!bg-admin-row !text-gray-600',
        'warning' => '!bg-[#FFF8E5] !text-[#8A6200]',
        'info' => '!bg-brand-cyan-bg !text-admin-teal',
        'pink' => '!bg-[#FFE9F4] !text-[#C2186F]',
        'purple' => '!bg-[#F3ECFF] !text-[#7038CF]',
        'danger' => '!bg-[#FDEBE8] !text-[#C4351A]',
    ][$tone] ?? '';
@endphp

<flux:badge size="sm" {{ $attributes->class(['!h-[22px] !rounded-[3px] !px-[9px] !py-0 !text-xs !font-bold whitespace-nowrap', $colors]) }}>{{ $slot }}</flux:badge>
