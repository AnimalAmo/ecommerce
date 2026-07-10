{{-- Dashboard B2B – tipologia attività/eventi - cosa è incluso (XD, artboard 1920x1400) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $servizi = [
        'aria_condizionata' => 'partner.hotel_services.svc_ac',
        'riscaldamento' => 'partner.hotel_services.svc_heating',
        'wifi' => 'partner.hotel_services.svc_wifi',
        'ricarica_elettrica' => 'partner.hotel_services.svc_ev',
        'tv' => 'partner.hotel_services.svc_tv',
        'piscina' => 'partner.hotel_services.svc_pool',
        'sauna' => 'partner.hotel_services.svc_sauna',
    ];
    $aggiuntivi = [
        'nessuno' => 'partner.hotel_services.add_none',
        'colazione' => 'partner.hotel_services.add_breakfast',
        'pranzo' => 'partner.hotel_services.add_lunch',
        'cena' => 'partner.hotel_services.add_dinner',
        'altro' => 'partner.hotel_services.add_other',
    ];
    $regole = [
        'vietato_fumare' => 'partner.hotel_services.rule_no_smoking',
        'vietato_feste' => 'partner.hotel_services.rule_no_parties',
    ];
    // Checkbox tondi cyan (come "check b2b" XD) + label SemiBold 15 #555, divisore per riga.
    $checkboxWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x1013, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 6 di 10" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.activity_included.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.activity_included.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.activity_included.section') }}</p>
                <p class="mt-2 text-[15px] font-medium text-[#959595]">{{ __('partner.activity_included.helper') }}</p>

                <form wire:submit="next" class="mt-6 {{ $checkboxWrap }}">
                    {{-- Sezione 1: Servizi --}}
                    <div>
                        @foreach ($servizi as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="svc-{{ $key }}">
                                <flux:checkbox wire:model="form.services" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Sezione 2: Servizi aggiuntivi presenti --}}
                    <h2 class="mt-6 text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_services.additional_heading') }}</h2>
                    <div class="mt-2">
                        @foreach ($aggiuntivi as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="add-{{ $key }}">
                                <flux:checkbox wire:model.live="form.additional" value="{{ $key }}" :label="__($labelKey)" />

                                {{-- Pasti (colazione/pranzo/cena): range orario Ora inizio / Ora fine --}}
                                @if (in_array($key, ['colazione', 'pranzo', 'cena'], true) && in_array($key, $form->additional, true))
                                    <div class="mt-3 grid max-w-[400px] grid-cols-2 gap-4">
                                        <div>
                                            <span class="mb-1 block text-xs font-normal text-[#555555]">{{ __('partner.hotel_services.time_from') }}</span>
                                            <flux:select wire:model="form.mealTimes.{{ $key }}.from" placeholder="--:--" class="[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]">
                                                @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                            </flux:select>
                                        </div>
                                        <div>
                                            <span class="mb-1 block text-xs font-normal text-[#555555]">{{ __('partner.hotel_services.time_to') }}</span>
                                            <flux:select wire:model="form.mealTimes.{{ $key }}.to" placeholder="--:--" class="[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]">
                                                @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                            </flux:select>
                                        </div>
                                    </div>
                                @endif

                                {{-- Altro: descrizione libera (localizzata it/en) --}}
                                @if ($key === 'altro' && in_array('altro', $form->additional, true))
                                    <div class="mt-3">
                                        <x-partner.locale-tabs>
                                            <x-slot:it>
                                                <flux:textarea wire:model="form.additionalOther.it" rows="3" maxlength="200" placeholder="{{ __('partner.hotel_services.other_placeholder') }}" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                            </x-slot:it>
                                            <x-slot:en>
                                                <flux:textarea wire:model="form.additionalOther.en" rows="3" maxlength="200" placeholder="{{ __('partner.hotel_services.other_placeholder') }} (EN)" class="!rounded-[3px] !border-[#C8C8C8] placeholder:!text-[#959595]" />
                                            </x-slot:en>
                                        </x-partner.locale-tabs>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Sezione 3: Regole della struttura --}}
                    <h2 class="mt-6 text-lg font-medium text-[#0D171A]">{{ __('partner.hotel_services.rules_heading') }}</h2>
                    <div class="mt-2">
                        @foreach ($regole as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="rule-{{ $key }}">
                                <flux:checkbox wire:model="form.structureRules" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Azioni: Indietro (a info generali) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.activity.info') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.hotel_services.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.hotel_services.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
