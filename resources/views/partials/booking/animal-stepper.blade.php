{{-- Stepper animali condiviso (una riga per specie in $animals, come la riga "Cani" del pop-up carrello).
     Contratto: $animals ({specie: count}), $animalsAtMax (bool: totale al clamp, "+" disabilitati);
     azioni wire del componente ospitante (trait HasBookingCalendar): incrementAnimal / decrementAnimal. --}}
@php
    // Nome riga per specie ('cane' → 'Cani'): mappa locale, niente chiave lang dedicata.
    $speciesNames = ['cane' => 'Cani', 'gatto' => 'Gatti', 'coniglio' => 'Conigli'];
@endphp
@foreach ($animals as $species => $count)
    <div class="flex h-[57px] items-center justify-between" wire:key="animal-row-{{ $species }}">
        <div class="text-base leading-none text-[#0D171A]">{{ $speciesNames[$species] ?? ucfirst($species) }}</div>
        <div class="flex items-center">
            <flux:button variant="ghost" square wire:click="decrementAnimal('{{ $species }}')" aria-label="Diminuisci {{ $speciesNames[$species] ?? ucfirst($species) }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                <flux:icon.minus class="!h-3 !w-3 text-black" />
            </flux:button>
            <span class="w-[42px] text-center text-base leading-none text-[#1E2E33]">{{ $count }}</span>
            <flux:button variant="ghost" square wire:click="incrementAnimal('{{ $species }}')" :disabled="$animalsAtMax" aria-label="Aumenta {{ $speciesNames[$species] ?? ucfirst($species) }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white disabled:!opacity-40 [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                <flux:icon.plus class="!h-3 !w-3 text-black" />
            </flux:button>
        </div>
    </div>
@endforeach
