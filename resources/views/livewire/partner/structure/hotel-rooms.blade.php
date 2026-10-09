{{-- Dashboard B2B – struttura ricettiva - hotel - info stanze (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $labelClass = '!text-xs !font-normal !text-[#555555]';
    // Casa vacanza: lo step descrive l'alloggio intero, non le singole camere.
    $whole = $form->wholeProperty;
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x479, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 5 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __($whole ? 'partner.hotel_rooms.whole_heading' : 'partner.hotel_rooms.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.hotel_rooms.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __($whole ? 'partner.hotel_rooms.whole_section' : 'partner.hotel_rooms.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __($whole ? 'partner.hotel_rooms.whole_helper' : 'partner.hotel_rooms.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Card stanza (casa vacanza: una sola, l'alloggio): il dettaglio si modifica nella modale. --}}
                    @include('livewire.partner.structure.partials.room-cards', [
                        'roomRows' => $form->rooms,
                        'whole' => $whole,
                        'rowsErrorKey' => 'form.rooms',
                    ])

                    {{-- Check in / Check out --}}
                    <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div>
                            <h2 class="text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_rooms.checkin') }}</h2>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.from') }}</flux:label>
                                    <flux:time-picker wire:model="form.checkinFrom" type="input" placeholder="--:--" class="[&_ui-time-picker-trigger>div]:!h-10 [&_ui-time-picker-trigger>div]:!rounded-[3px] [&_ui-time-picker-trigger>div]:!border-[#C8C8C8]" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.to') }}</flux:label>
                                    <flux:time-picker wire:model="form.checkinTo" type="input" placeholder="--:--" class="[&_ui-time-picker-trigger>div]:!h-10 [&_ui-time-picker-trigger>div]:!rounded-[3px] [&_ui-time-picker-trigger>div]:!border-[#C8C8C8]" />
                                </flux:field>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_rooms.checkout') }}</h2>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.from') }}</flux:label>
                                    <flux:time-picker wire:model="form.checkoutFrom" type="input" placeholder="--:--" class="[&_ui-time-picker-trigger>div]:!h-10 [&_ui-time-picker-trigger>div]:!rounded-[3px] [&_ui-time-picker-trigger>div]:!border-[#C8C8C8]" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.to') }}</flux:label>
                                    <flux:time-picker wire:model="form.checkoutTo" type="input" placeholder="--:--" class="[&_ui-time-picker-trigger>div]:!h-10 [&_ui-time-picker-trigger>div]:!rounded-[3px] [&_ui-time-picker-trigger>div]:!border-[#C8C8C8]" />
                                </flux:field>
                            </div>
                        </div>
                    </div>

                    @if ($errors->any())
                        <p class="mt-4 text-sm text-red-500">{{ __('partner.hotel_rooms.error_required') }}</p>
                    @endif

                    {{-- Azioni: Indietro (a descrizione) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.structure.hotel.description') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_rooms.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_rooms.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('livewire.partner.structure.partials.room-modal', ['whole' => $whole])

    @include('partials.partner-footer')
</div>
