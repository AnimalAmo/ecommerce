{{-- Calendario prenotazione condiviso (estratto dal pop-up "Modifica prenotazione" del carrello, riusato dai widget detail).
     Contratto: $calendar (settimane di celle {day, date, inMonth, inRange, disabled}), $calendarLabel ('Marzo 2024');
     azioni wire del componente ospitante (trait HasBookingCalendar): previousMonth / nextMonth / selectDay('Y-m-d').
     I giorni passati o chiusi (disabled) sono greyed e non cliccabili. --}}
<div class="mt-2 flex items-center justify-center gap-6">
    <flux:button variant="ghost" square wire:click="previousMonth" aria-label="Mese precedente" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
        <flux:icon.chevron-left class="!h-[14px] !w-[14px] text-black" />
    </flux:button>
    <span class="w-[110px] text-center text-[15px] font-semibold leading-none text-black">{{ $calendarLabel }}</span>
    <flux:button variant="ghost" square wire:click="nextMonth" aria-label="Mese successivo" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
        <flux:icon.chevron-right class="!h-[14px] !w-[14px] text-black" />
    </flux:button>
</div>

<div class="mt-5 grid grid-cols-7 text-center text-[15px] leading-none text-[#555555]">
    @foreach (['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'] as $dow)
        <span wire:key="dow-{{ $dow }}">{{ $dow }}</span>
    @endforeach
</div>

{{-- Giorni: click → check-in, secondo click successivo → check-out (giorno singolo nei picker a un giorno);
     i giorni nell'intervallo hanno il cerchio giallo 28px, quelli disabilitati sono grigio chiaro senza click --}}
<div class="mt-2">
    @foreach ($calendar as $weekIndex => $week)
        <div class="grid grid-cols-7" wire:key="week-{{ $weekIndex }}">
            @foreach ($week as $day)
                <div class="flex h-10 items-center justify-center" wire:key="day-{{ $day['date'] }}">
                    <flux:button variant="ghost" square wire:click="selectDay('{{ $day['date'] }}')" :disabled="$day['disabled']" class="!h-7 !w-7 !min-w-0 !rounded-full !p-0 !text-[15px] !font-normal disabled:!opacity-100 {{ $day['inRange'] && ! $day['disabled'] ? '!bg-brand-yellow hover:!bg-brand-yellow' : 'hover:!bg-gray-100' }} {{ $day['disabled'] ? '!text-[#DEDEDE] hover:!bg-transparent' : ($day['inMonth'] ? '!text-[#2B2B2B]' : '!text-[#959595]') }} [&>span]:flex [&>span]:items-center [&>span]:justify-center">{{ $day['day'] }}</flux:button>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
