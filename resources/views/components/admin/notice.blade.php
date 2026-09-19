{{-- Avviso a riquadro: info (azzurro) o warning (arancio), titolo + testo, azioni opzionali. --}}
@props(['tone' => 'info', 'heading' => null])

@php
    $box = $tone === 'warning' ? 'border-[#FFE0B2] bg-[#FFFBF4]' : 'border-[#BEE7F5] bg-[#F7FCFE]';
    $iconColor = $tone === 'warning' ? 'text-[#B45A0B]' : 'text-admin-teal';
@endphp

<div {{ $attributes->class(['rounded-[10px] border', $box]) }} role="note">
    <div class="flex flex-wrap items-start gap-3 px-5 py-4">
        <flux:icon.exclamation-triangle class="mt-0.5 size-[17px] shrink-0 {{ $iconColor }}" />
        <div class="min-w-[220px] flex-1">
            @if (filled($heading))
                <p class="m-0 text-[13.5px] font-bold text-admin-rail">{{ $heading }}</p>
            @endif
            <div class="mt-1.5 text-[13px] leading-normal text-gray-600">{{ $slot }}</div>
        </div>
        @isset($actions)
            <div class="flex shrink-0 items-center gap-2 self-center">{{ $actions }}</div>
        @endisset
    </div>
</div>
