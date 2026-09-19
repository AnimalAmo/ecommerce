{{-- Numero riassuntivo: etichetta, valore grande, nota. `accent` = filetto azzurro a sinistra. --}}
@props(['label', 'value', 'note' => null, 'accent' => false])

<div {{ $attributes->class(['rounded-[10px] border border-gray-150 bg-white px-5 py-[18px]', 'border-l-[3px] !border-l-brand-cyan' => $accent]) }}>
    <p class="m-0 text-sm text-gray-400">{{ $label }}</p>
    <p class="mt-2 text-[28px] leading-none font-bold text-admin-rail">{{ $value }}</p>
    @if (filled($note))
        <p class="mt-[7px] text-[12.5px] text-gray-400">{{ $note }}</p>
    @endif
</div>
