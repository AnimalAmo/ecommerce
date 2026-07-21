{{-- Sweet alert dell'XD app (es. "Diventa partner - click 'chiudi'"):
     pannello 315x194 centrato su overlay nero 40%, titolo 18 bold nel colore
     accento, corpo 15/22 #555555 su 267px e X in alto a destra (glifo 7,5px
     con centro a 17px dal bordo). $panel/$accent tengono la palette fuori dal
     markup: l'alert verde "Aggiunto al carrello" cambia solo quelle due props.
     Slot: $slot = corpo, $decoration = grafica libera (es. l'aeroplanino). --}}
@props([
    'name',
    'model',
    'heading',
    'closeLabel' => null,
    'panel' => 'border-brand-cyan !bg-[#EBF9FD]',
    'accent' => '!text-brand-cyan',
])

<flux:modal :name="$name" variant="bare" wire:model="{{ $model }}"
    {{ $attributes->class(['w-[315px] max-w-[calc(100vw-60px)] rounded-[4px] border backdrop:!bg-black/40', $panel]) }}>
    <div class="relative px-4 pb-[54px] pt-12 text-center">
        <flux:heading level="2" class="!text-lg !font-bold !leading-[22px] {{ $accent }}">{{ $heading }}</flux:heading>

        <div class="mx-auto mt-0.5 max-w-[267px] text-[15px] leading-[22px] text-[#555555]">{{ $slot }}</div>

        <div class="absolute right-[1px] top-[2px]">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm" square aria-label="{{ $closeLabel ?? __('nav.close') }}" class="!rounded-full !text-ink">
                    <flux:icon.close class="h-3 w-3" />
                </flux:button>
            </flux:modal.close>
        </div>

        {{ $decoration ?? '' }}
    </div>
</flux:modal>
