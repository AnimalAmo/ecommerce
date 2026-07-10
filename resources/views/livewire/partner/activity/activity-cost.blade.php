{{-- Dashboard B2B – tipologia attività/eventi - costo (XD, artboard 1920x1080 + variante "– 2") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $radioWrap = '[--color-accent:#6CD1EF] [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555]'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 8 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_cost.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_cost.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.activity_cost.section') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Opzioni costo (scelta singola). "A pagamento" rivela "Costo a persona". --}}
                    <flux:radio.group wire:model.live="costType" class="!flex !flex-col !gap-0 {{ $radioWrap }}">
                        <div class="border-b border-[#E2EAEB] py-3">
                            <flux:radio value="pagamento" :label="__('partner.activity_cost.opt_paid')" />
                        </div>

                        @if ($costType === 'pagamento')
                            <div class="max-w-[300px] py-3">
                                <span class="mb-1 block text-xs font-normal text-[#555555]">{{ __('partner.activity_cost.price_label') }}</span>
                                <flux:input type="number" min="0" step="0.01" wire:model="pricePerPerson" class="[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]" />
                                @error('pricePerPerson')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="border-b border-[#E2EAEB] py-3">
                            <flux:radio value="gratuito" :label="__('partner.activity_cost.opt_free')" />
                        </div>
                    </flux:radio.group>

                    @error('costType')
                        <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Azioni: Indietro (a servizi animali) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.animal-services') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.activity_cost.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.activity_cost.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
