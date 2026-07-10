{{-- Dashboard B2B – tipologia attività/eventi - descrizione (XD, artboard 1920x1080) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 4 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_description.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_description.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.activity_description.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_description.helper') }}</p>

                <form wire:submit="next" class="mt-6">
                    {{-- Campi localizzati it/en: entrambe le textarea della lingua nello stesso pannello --}}
                    <x-partner.locale-tabs>
                        <x-slot:it>
                            {{-- Descrizione breve (752x84, max 200) --}}
                            <div x-data="{ len: @js(mb_strlen($description['it'])) }">
                                <flux:textarea wire:model="description.it" maxlength="200" rows="3" x-on:input="len = $event.target.value.length" placeholder="{{ __('partner.activity_description.placeholder') }}" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                <p class="mt-2 text-right text-[13px] text-[#959595]"><span x-text="200 - len">200</span> {{ __('partner.activity_description.chars') }}</p>
                            </div>
                            @error('description.it')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            {{-- Descrizione dettagliata (solo Attività): 752x188, max 200 --}}
                            @if ($isActivity)
                                <p class="mt-4 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_description.detailed_label') }}</p>
                                <div class="mt-3" x-data="{ len: @js(mb_strlen($detailedDescription['it'])) }">
                                    <flux:textarea wire:model="detailedDescription.it" maxlength="200" rows="6" x-on:input="len = $event.target.value.length" placeholder="{{ __('partner.activity_description.placeholder') }}" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                    <p class="mt-2 text-right text-[13px] text-[#959595]"><span x-text="200 - len">200</span> {{ __('partner.activity_description.chars') }}</p>
                                </div>
                                @error('detailedDescription.it')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            @endif
                        </x-slot:it>
                        <x-slot:en>
                            {{-- Descrizione breve (752x84, max 200) --}}
                            <div x-data="{ len: @js(mb_strlen($description['en'])) }">
                                <flux:textarea wire:model="description.en" maxlength="200" rows="3" x-on:input="len = $event.target.value.length" placeholder="{{ __('partner.activity_description.placeholder') }} (EN)" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                <p class="mt-2 text-right text-[13px] text-[#959595]"><span x-text="200 - len">200</span> {{ __('partner.activity_description.chars') }}</p>
                            </div>
                            @error('description.en')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            {{-- Descrizione dettagliata (solo Attività): 752x188, max 200 --}}
                            @if ($isActivity)
                                <p class="mt-4 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_description.detailed_label') }} (EN)</p>
                                <div class="mt-3" x-data="{ len: @js(mb_strlen($detailedDescription['en'])) }">
                                    <flux:textarea wire:model="detailedDescription.en" maxlength="200" rows="6" x-on:input="len = $event.target.value.length" placeholder="{{ __('partner.activity_description.placeholder') }} (EN)" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                    <p class="mt-2 text-right text-[13px] text-[#959595]"><span x-text="200 - len">200</span> {{ __('partner.activity_description.chars') }}</p>
                                </div>
                                @error('detailedDescription.en')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            @endif
                        </x-slot:en>
                    </x-partner.locale-tabs>

                    {{-- Azioni: Indietro (a luogo) + Avanti (pill scuro) --}}
                    <div class="mt-6 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.location') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.activity_description.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.activity_description.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
