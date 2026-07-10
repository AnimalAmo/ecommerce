{{-- Dashboard B2B – struttura ricettiva - hotel - luogo (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x396, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 3 di 11" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.hotel_location.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.hotel_location.step') }}</span>
                </div>

                <h2 class="mt-4 text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_location.section') }}</h2>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.hotel_location.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-2">
                        {{-- Indirizzo | Città --}}
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_location.address') }} *</flux:label>
                            <flux:input wire:model="form.address" class="{{ $fieldClass }}" />
                        </flux:field>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_location.city') }} *</flux:label>
                            <flux:input wire:model="form.city" class="{{ $fieldClass }}" />
                        </flux:field>

                        {{-- (Provincia + Cap) | Licenza apertura --}}
                        <div class="grid grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_location.province') }} *</flux:label>
                                <flux:input wire:model="form.province" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_location.zip') }} *</flux:label>
                                <flux:input wire:model="form.zip" inputmode="numeric" class="{{ $fieldClass }}" />
                            </flux:field>
                        </div>
                        <flux:field>
                            <flux:label class="!text-xs !font-normal !text-[#555555]">{{ __('partner.hotel_location.license') }} *</flux:label>
                            <flux:input wire:model="form.license" class="{{ $fieldClass }}" />
                        </flux:field>
                    </div>

                    {{-- Azioni: Indietro (a titolo) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.structure.hotel.title') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_location.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_location.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
