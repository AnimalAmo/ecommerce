{{-- Stepper ospiti condiviso (estratto dal pop-up del carrello, riusato dai widget detail).
     Contratto: $guests ({adulti, ragazzi, bambini}), $guestsAtMax (bool: totale al clamp, "+" disabilitati);
     azioni wire del componente ospitante (trait HasBookingCalendar): incrementGuest / decrementGuest. --}}
@foreach ([
    ['key' => 'adulti', 'name' => __('nav.booking.guests.adults'), 'hint' => __('nav.booking.guests.adults_hint')],
    ['key' => 'ragazzi', 'name' => __('nav.booking.guests.teens'), 'hint' => __('nav.booking.guests.teens_hint')],
    ['key' => 'bambini', 'name' => __('nav.booking.guests.children'), 'hint' => __('nav.booking.guests.children_hint')],
] as $row)
    <div class="flex h-[57px] items-center justify-between" wire:key="guest-row-{{ $row['key'] }}">
        <div>
            <div class="text-base leading-none text-[#0D171A]">{{ $row['name'] }}</div>
            <div class="mt-[6px] text-sm leading-none text-[#627277]">{{ $row['hint'] }}</div>
        </div>
        <div class="flex items-center">
            <flux:button variant="ghost" square wire:click="decrementGuest('{{ $row['key'] }}')" aria-label="{{ __('nav.booking.decrease', ['name' => $row['name']]) }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                <flux:icon.minus class="!h-3 !w-3 text-black" />
            </flux:button>
            <span class="w-[42px] text-center text-base leading-none text-[#1E2E33]">{{ $guests[$row['key']] }}</span>
            <flux:button variant="ghost" square wire:click="incrementGuest('{{ $row['key'] }}')" :disabled="$guestsAtMax" aria-label="{{ __('nav.booking.increase', ['name' => $row['name']]) }}" class="!h-[26px] !w-[26px] !min-w-0 !rounded-full !border !border-[#E2EAEB] !bg-white !p-0 !shadow-none hover:!bg-white disabled:!opacity-40 [&>span]:flex [&>span]:items-center [&>span]:justify-center">
                <flux:icon.plus class="!h-3 !w-3 text-black" />
            </flux:button>
        </div>
    </div>
@endforeach
