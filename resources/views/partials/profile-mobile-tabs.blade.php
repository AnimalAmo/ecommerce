{{-- Tab mobile "In programma / Passati" (XD app: pillole h31 r100, bordo #C8C8C8, attiva #4FB8D8).
     Riceve $tabs (chiave → label) e $tab; l'azione setTab è quella del componente chiamante. --}}
<div class="flex gap-2 lg:hidden">
    @foreach ($tabs as $key => $label)
        <flux:button variant="ghost" wire:click="setTab('{{ $key }}')" wire:key="tab-mobile-{{ $key }}" class="!h-[31px] !rounded-full !border !border-[#C8C8C8] !bg-white !px-4 !text-sm !font-normal !leading-none hover:!bg-white {{ $tab === $key ? '!text-[#4FB8D8] hover:!text-[#4FB8D8]' : '!text-[#959595] hover:!text-[#959595]' }}">{{ $label }}</flux:button>
    @endforeach
</div>
