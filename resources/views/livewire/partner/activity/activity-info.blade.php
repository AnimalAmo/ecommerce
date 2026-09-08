{{-- Dashboard B2B – tipologia attività/eventi - info generali (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    // Il date-picker Flux rende un bottone, non un input: le stesse misure
    // dell'XD vanno applicate a quello (stesso idioma di partner/bookings).
    $datePickerClass = '[&_button]:!h-10 [&_button]:!rounded-[3px] [&_button]:!border-[#C8C8C8]';
    $selectClass = '[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]';
    $labelClass = '!text-xs !font-normal !text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x274, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 5 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_info.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_info.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ $form->isEvent ? __('partner.activity_info.section_event') : __('partner.activity_info.section_activity') }}</p>

                <form wire:submit="next" class="mt-6">
                    <div class="flex flex-wrap gap-4">
                        {{-- Data inizio / Data fine --}}
                        <flux:field class="w-full sm:w-[176px]">
                            <flux:label class="{{ $labelClass }}">{{ __('partner.activity_info.date_start') }}</flux:label>
                            <flux:date-picker wire:model="form.dateStart" placeholder="{{ __('partner.activity_info.date_start') }}" class="{{ $datePickerClass }}" />
                        </flux:field>
                        <flux:field class="w-full sm:w-[176px]">
                            <flux:label class="{{ $labelClass }}">{{ __('partner.activity_info.date_end') }}</flux:label>
                            <flux:date-picker wire:model="form.dateEnd" placeholder="{{ __('partner.activity_info.date_end') }}" class="{{ $datePickerClass }}" />
                        </flux:field>

                        {{-- Ora inizio / Ora fine: solo Eventi --}}
                        @if ($form->isEvent)
                            <flux:field class="w-full sm:w-[176px]">
                                <flux:label class="{{ $labelClass }}">{{ __('partner.activity_info.time_start') }}</flux:label>
                                <flux:select wire:model="form.timeStart" placeholder="--:--" class="{{ $selectClass }}">
                                    @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                </flux:select>
                            </flux:field>
                            <flux:field class="w-full sm:w-[176px]">
                                <flux:label class="{{ $labelClass }}">{{ __('partner.activity_info.time_end') }}</flux:label>
                                <flux:select wire:model="form.timeEnd" placeholder="--:--" class="{{ $selectClass }}">
                                    @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                </flux:select>
                            </flux:field>
                        @endif
                    </div>

                    {{-- Azioni: Indietro (a descrizione) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.description') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.activity_info.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.activity_info.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
