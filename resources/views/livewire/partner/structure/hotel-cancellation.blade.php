{{-- Dashboard B2B – struttura ricettiva - hotel - cancellazione (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x398, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 6 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.hotel_cancellation.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.hotel_cancellation.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.hotel_cancellation.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.hotel_cancellation.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Quando? (select, aggiorna la barra live) --}}
                    <div class="max-w-[368px]">
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_cancellation.when') }} *</flux:label>
                            <flux:select wire:model.live="when" class="[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]">
                                <flux:select.option value="30">{{ __('partner.hotel_cancellation.days_30') }}</flux:select.option>
                                <flux:select.option value="15">{{ __('partner.hotel_cancellation.days_15') }}</flux:select.option>
                                <flux:select.option value="7">{{ __('partner.hotel_cancellation.days_7') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('partner.hotel_cancellation.days_1') }}</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    {{-- Timeline: verde (cancellazione gratuita) → rosso (paga l'importo totale) --}}
                    <div class="mt-10">
                        <div class="flex items-center justify-between text-[13px] font-medium text-[#555555]">
                            <span>{{ __('partner.hotel_cancellation.free') }}</span>
                            <span>{{ __('partner.hotel_cancellation.pays') }}</span>
                        </div>
                        <div class="mt-3 flex h-[9px] w-full overflow-hidden rounded-[5px]">
                            <div class="h-full bg-[#ABFDB2] transition-all" style="width: {{ $green }}%"></div>
                            <div class="h-full bg-[#FFD3C9] transition-all" style="width: {{ 100 - $green }}%"></div>
                        </div>
                        <div class="relative mt-2 h-4 text-[13px] font-medium text-[#959595]">
                            <span class="absolute -translate-x-1/2" style="left: 17%">{{ __('partner.hotel_cancellation.days_30') }}</span>
                            <span class="absolute -translate-x-1/2" style="left: 35%">{{ __('partner.hotel_cancellation.days_15') }}</span>
                            <span class="absolute -translate-x-1/2" style="left: 53%">{{ __('partner.hotel_cancellation.days_7') }}</span>
                            <span class="absolute -translate-x-1/2" style="left: 68%">{{ __('partner.hotel_cancellation.days_1') }}</span>
                            <span class="absolute right-0">{{ __('partner.hotel_cancellation.arrival') }}</span>
                        </div>
                    </div>

                    {{-- Azioni: Indietro (a info stanze) + Avanti (pill scuro) --}}
                    <div class="mt-10 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.structure.hotel.rooms') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_cancellation.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_cancellation.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
