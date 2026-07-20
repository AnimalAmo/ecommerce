{{-- Calendario prenotazione condiviso (estratto dal pop-up "Modifica prenotazione" del carrello, riusato dai widget detail).
     Contratto: $calendar (settimane di celle {day, date, inMonth, inRange, disabled}), $calendarLabel ('Marzo 2024');
     azioni wire del componente ospitante (trait HasBookingCalendar): previousMonth / nextMonth / selectDay('Y-m-d').
     I giorni passati o chiusi (disabled) sono greyed e non cliccabili.
     Variante app (modal filtri mobile, XD app): $appStyle=true + $rangeStart/$rangeEnd (Y-m-d) — titolo mese cyan,
     giorni selezionati quadrato 27x25 r4 #6CD1EF, banda range #EBF9FD; senza $appStyle il rendering resta identico. --}}
@php
    $appStyle ??= false;
    $rangeStart ??= null;
    $rangeEnd ??= null;
@endphp
<div class="mt-2 flex items-center justify-center gap-6">
    <flux:button variant="ghost" square wire:click="previousMonth" aria-label="{{ __('nav.booking.previous_month') }}" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
        <flux:icon.chevron-left class="!h-[14px] !w-[14px] text-black" />
    </flux:button>
    <span class="w-[110px] text-center text-[15px] leading-none {{ $appStyle ? 'font-medium text-[#68CDEB]' : 'font-semibold text-black' }}">{{ $calendarLabel }}</span>
    <flux:button variant="ghost" square wire:click="nextMonth" aria-label="{{ __('nav.booking.next_month') }}" class="!h-6 !w-6 !min-w-0 !p-0 hover:!bg-transparent [&>span]:flex [&>span]:items-center [&>span]:justify-center">
        <flux:icon.chevron-right class="!h-[14px] !w-[14px] text-black" />
    </flux:button>
</div>

<div class="mt-5 grid grid-cols-7 text-center leading-none {{ $appStyle ? 'text-[13px] text-[#959595]' : 'text-[15px] text-[#555555]' }}">
    @foreach (__('nav.booking.dow') as $dow)
        <span wire:key="dow-{{ $dow }}">{{ $dow }}</span>
    @endforeach
</div>

{{-- Giorni: click → check-in, secondo click successivo → check-out (giorno singolo nei picker a un giorno);
     i giorni nell'intervallo hanno il cerchio giallo 28px (desktop) o quadrato cyan + banda #EBF9FD (app);
     quelli disabilitati sono grigio chiaro senza click --}}
<div class="mt-2">
    @foreach ($calendar as $weekIndex => $week)
        <div class="grid grid-cols-7" wire:key="week-{{ $weekIndex }}">
            @foreach ($week as $day)
                @php
                    $isStart = $appStyle && $rangeStart !== null && $day['date'] === $rangeStart;
                    $isEnd = $appStyle && $rangeEnd !== null && $day['date'] === $rangeEnd;
                    $isEdge = $isStart || $isEnd;
                    // Banda solo con range completo (due estremi diversi), come da XD.
                    $hasBand = $appStyle && $day['inRange'] && ! $day['disabled']
                        && $rangeEnd !== null && $rangeEnd !== $rangeStart;

                    if ($appStyle) {
                        $cellClass = $hasBand
                            ? 'bg-clip-content py-[7px] '.($isStart ? 'bg-[linear-gradient(to_right,transparent_50%,#EBF9FD_50%)]' : ($isEnd ? 'bg-[linear-gradient(to_right,#EBF9FD_50%,transparent_50%)]' : 'bg-[#EBF9FD]'))
                            : '';
                        $dayClass = $isEdge
                            ? '!h-[25px] !w-[27px] !rounded-[4px] !bg-brand-cyan hover:!bg-brand-cyan !text-white'
                            : '!h-7 !w-7 !rounded-full hover:!bg-gray-100 '.($day['disabled'] ? '!text-[#DEDEDE] hover:!bg-transparent' : ($day['inMonth'] ? '!text-[#0D171A]' : '!text-[#959595]'));
                        $dayClass .= ' !text-[14px]';
                    } else {
                        $cellClass = '';
                        $dayClass = '!h-7 !w-7 !rounded-full !text-[15px] '
                            .($day['inRange'] && ! $day['disabled'] ? '!bg-brand-yellow hover:!bg-brand-yellow' : 'hover:!bg-gray-100').' '
                            .($day['disabled'] ? '!text-[#DEDEDE] hover:!bg-transparent' : ($day['inMonth'] ? '!text-[#2B2B2B]' : '!text-[#959595]'));
                    }
                @endphp
                <div class="flex h-10 items-center justify-center {{ $cellClass }}" wire:key="day-{{ $day['date'] }}">
                    <flux:button variant="ghost" square wire:click="selectDay('{{ $day['date'] }}')" :disabled="$day['disabled']" class="!min-w-0 !p-0 !font-normal disabled:!opacity-100 {{ $dayClass }} [&>span]:flex [&>span]:items-center [&>span]:justify-center">{{ $day['day'] }}</flux:button>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
