{{-- "Torna a …" sopra il titolo delle schermate di dettaglio. --}}
@props(['href'])

<a href="{{ $href }}" wire:navigate {{ $attributes->class('inline-flex items-center gap-1.5 self-start text-[13.5px] font-bold text-gray-600 hover:text-admin-teal') }}>
    <flux:icon.chevron-left class="size-[15px]" />{{ $slot }}
</a>
