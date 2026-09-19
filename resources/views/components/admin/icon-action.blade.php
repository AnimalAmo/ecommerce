{{--
    Azione tonda nelle righe di tabella (occhio, sospendi, elimina, modifica…).
    tone: view (giallo), suspend (viola), delete (rosso), edit (arancio), neutral.
    `label` diventa title e aria-label: l'icona da sola non dice niente a un lettore di schermo.
--}}
@props(['tone' => 'neutral', 'icon', 'label'])

@php
    $tones = [
        'view' => '!bg-[#FFF8E5] !text-[#8A6200]',
        'suspend' => '!bg-[#EFE5FF] !text-[#7038CF]',
        'delete' => '!bg-[#FDEBE8] !text-[#C4351A]',
        'edit' => '!bg-[#FFF2E6] !text-[#B45A0B]',
        'neutral' => '!bg-admin-row !text-gray-600',
    ];
@endphp

<flux:button
    size="xs"
    :icon="$icon"
    title="{{ $label }}"
    aria-label="{{ $label }}"
    {{ $attributes->class(['!size-7 !rounded-full !border-0 !p-0 !shadow-none [&_svg]:!size-3.5', $tones[$tone] ?? $tones['neutral']]) }}
/>
