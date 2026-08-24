{{-- Dashboard B2B – struttura ricettiva - hotel - info stanze (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $inputClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]';
    $selectClass = '[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]';
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
                    {{-- Casa vacanza: unità sola, posti letto + prezzo a notte. --}}
                    @if ($whole)
                        <div class="mb-5 grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2">
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.beds') }} *</flux:label>
                                <flux:input type="number" min="1" max="50" wire:model="form.rooms.0.beds" class="{{ $inputClass }}" />
                                <flux:error name="form.rooms.0.beds" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.whole_price') }} *</flux:label>
                                <flux:input type="number" min="0" wire:model="form.rooms.0.price" class="{{ $inputClass }}" />
                                <flux:error name="form.rooms.0.price" />
                            </flux:field>
                        </div>
                    @else
                    {{-- Righe stanza (ripetibili) --}}
                    @foreach ($form->rooms as $i => $room)
                        <div class="mb-5 grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2" wire:key="room-{{ $i }}">
                            {{-- Tipologia Stanze (select) --}}
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.room_type') }} *</flux:label>
                                <flux:select wire:model="form.rooms.{{ $i }}.type" placeholder="{{ __('partner.hotel_rooms.room_type') }}" class="{{ $selectClass }}">
                                    <flux:select.option value="singola">{{ __('partner.hotel_rooms.type_single') }}</flux:select.option>
                                    <flux:select.option value="doppia">{{ __('partner.hotel_rooms.type_double') }}</flux:select.option>
                                    <flux:select.option value="tripla">{{ __('partner.hotel_rooms.type_triple') }}</flux:select.option>
                                    <flux:select.option value="suite">{{ __('partner.hotel_rooms.type_suite') }}</flux:select.option>
                                </flux:select>
                            </flux:field>

                            {{-- Numero di stanze (stepper) + Prezzo --}}
                            <div class="grid grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.room_count') }} *</flux:label>
                                    <div class="flex h-10 items-center justify-between rounded-[3px] border border-[#C8C8C8] px-1">
                                        <flux:button type="button" wire:click="decrementRoom({{ $i }})" variant="ghost" size="sm" square icon="minus" class="!h-8 !w-8 !rounded-[3px] !bg-[#F4F4F4] !text-black" />
                                        <span class="text-[15px] text-[#2B2B2B]">{{ $room['count'] }}</span>
                                        <flux:button type="button" wire:click="incrementRoom({{ $i }})" variant="ghost" size="sm" square icon="plus" class="!h-8 !w-8 !rounded-[3px] !bg-[#F4F4F4] !text-black" />
                                    </div>
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.hotel_rooms.price') }} *</flux:label>
                                    <flux:input type="number" min="0" wire:model="form.rooms.{{ $i }}.price" class="{{ $inputClass }}" />
                                </flux:field>
                            </div>
                        </div>
                    @endforeach

                    {{-- + Aggiungi stanze (link cyan) --}}
                    <flux:button type="button" wire:click="addRoom" variant="ghost" icon="plus" class="!-ml-1 !px-1 !text-[16px] !font-bold !text-brand-cyan hover:!bg-transparent [&_svg]:!text-brand-cyan">{{ __('partner.hotel_rooms.add_rooms') }}</flux:button>
                    @endif

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

    @include('partials.partner-footer')
</div>
