{{-- Tab "In programma / Passati" condivise da Ordini ed Eventi: un solo flux:tabs responsive.
     App (XD): pillole h31 r100 bianche SENZA bordo, ombra 0,0,5.5 #000000@0.11, attiva #4FB8D8.
     Desktop (XD): tab sottolineate ciano sulla linea #DEDEDE.
     Riceve $tabs (chiave → label) e binda wire:model.live sulla proprietà $tab del componente. --}}
@php
    $tabClass = implode(' ', [
        '!h-[31px] !mb-0 !rounded-full !border-0 !bg-white !px-4 !text-sm !font-normal !shadow-[0px_0px_6px_#0000001C]',
        '!text-[#959595] hover:!text-[#959595] data-selected:!text-[#4FB8D8] hover:data-selected:!text-[#4FB8D8]',
        'lg:!h-auto lg:!rounded-none lg:!bg-transparent lg:!px-0 lg:!pb-3 lg:!text-lg lg:!font-medium lg:!shadow-none',
        'lg:!border-b-[2.5px] lg:!border-transparent lg:!text-[#C8C8C8] lg:hover:!text-[#C8C8C8]',
        'lg:data-selected:!border-[#68CDEB] lg:data-selected:!text-[#68CDEB] lg:hover:data-selected:!text-[#68CDEB]',
    ]);
@endphp

<flux:tab.group>
    <flux:tabs wire:model.live="tab" class="!h-auto !gap-2 !border-b-0 lg:!gap-[50px] lg:!border-b lg:!border-[#DEDEDE]">
        @foreach ($tabs as $key => $label)
            <flux:tab name="{{ $key }}" class="{{ $tabClass }}">{{ $label }}</flux:tab>
        @endforeach
    </flux:tabs>
</flux:tab.group>
