{{-- Dashboard B2B - smartbox - cibo / pasti (XD, artboard 1920x1080 → esteso con orari + dieta) --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php
    $mealOptions = [
        'nessuno' => 'partner.smartbox_meals.meal_none',
        'colazione' => 'partner.smartbox_meals.meal_breakfast',
        'pranzo' => 'partner.smartbox_meals.meal_lunch',
        'cena' => 'partner.smartbox_meals.meal_dinner',
    ];
    $mealLabels = [
        'colazione' => 'partner.smartbox_meals.meal_breakfast',
        'pranzo' => 'partner.smartbox_meals.meal_lunch',
        'cena' => 'partner.smartbox_meals.meal_dinner',
    ];
    $diets = [
        'diabetico' => 'partner.smartbox_meals.diet_diabetic',
        'vegano' => 'partner.smartbox_meals.diet_vegan',
        'vegetariano' => 'partner.smartbox_meals.diet_vegetarian',
        'senza_glutine' => 'partner.smartbox_meals.diet_gluten_free',
        'senza_uova' => 'partner.smartbox_meals.diet_egg_free',
        'senza_lattosio' => 'partner.smartbox_meals.diet_lactose_free',
    ];
    // Checkbox tondi cyan (come "check b2b" XD) + label SemiBold 15 #555.
    $checkboxWrap = '[--color-accent:#6CD1EF] [&_[data-flux-checkbox]]:!rounded-full [&_[data-flux-checkbox]_*]:!rounded-full [&_[data-flux-label]]:!text-[15px] [&_[data-flux-label]]:!font-semibold [&_[data-flux-label]]:!text-[#555555]';
    $selectedMeals = array_values(array_intersect(['colazione', 'pranzo', 'cena'], $meals));
    $hasMeals = $selectedMeals !== [];
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            {{-- Card (XD: 816x…, r10, bordo #E9E9E9) --}}
            <div class="mx-auto w-full max-w-[816px] rounded-[10px] border border-gray-150 bg-white px-8 py-8">

                {{-- Testata: titolo + "Step 6 di 12" --}}
                <div class="flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.smartbox_meals.heading') }}</h1>
                    <span class="mt-1 shrink-0 text-[15px] font-semibold text-[#C8C8C8]">{{ __('partner.smartbox_meals.step') }}</span>
                </div>

                <p class="mt-4 text-[15px] font-medium text-black">{{ __('partner.smartbox_meals.section') }}</p>

                <form wire:submit="next" class="mt-6 {{ $checkboxWrap }}">
                    {{-- Pasti offerti --}}
                    <div>
                        @foreach ($mealOptions as $key => $labelKey)
                            <div class="border-b border-[#E2EAEB] py-3" wire:key="meal-{{ $key }}">
                                <flux:checkbox wire:model.live="meals" value="{{ $key }}" :label="__($labelKey)" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Orari + restrizioni dietetiche (rivelati quando è offerto almeno un pasto) --}}
                    @if ($hasMeals)
                        <h2 class="mt-8 text-[15px] font-medium text-black">{{ __('partner.smartbox_meals.times_heading') }}</h2>
                        <div class="mt-4 space-y-6">
                            @foreach ($selectedMeals as $key)
                                <div wire:key="time-{{ $key }}">
                                    <p class="text-[15px] font-semibold text-[#1E2E33]">{{ __($mealLabels[$key]) }}</p>
                                    <div class="mt-3 grid max-w-[400px] grid-cols-2 gap-4">
                                        <div>
                                            <span class="mb-1 block text-xs font-normal text-[#555555]">{{ __('partner.smartbox_meals.time_from') }}</span>
                                            <flux:select wire:model="mealTimes.{{ $key }}.from" placeholder="--:--" class="[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]">
                                                @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                            </flux:select>
                                        </div>
                                        <div>
                                            <span class="mb-1 block text-xs font-normal text-[#555555]">{{ __('partner.smartbox_meals.time_to') }}</span>
                                            <flux:select wire:model="mealTimes.{{ $key }}.to" placeholder="--:--" class="[&_select]:!h-10 [&_select]:!rounded-[3px] [&_select]:!border-[#C8C8C8]">
                                                @foreach ($times as $t)<flux:select.option value="{{ $t }}">{{ $t }}</flux:select.option>@endforeach
                                            </flux:select>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <h2 class="mt-8 text-[15px] font-medium text-black">{{ __('partner.smartbox_meals.dietary_heading') }}</h2>
                        <div class="mt-2">
                            @foreach ($diets as $key => $labelKey)
                                <div class="border-b border-[#E2EAEB] py-3" wire:key="diet-{{ $key }}">
                                    <flux:checkbox wire:model="dietary" value="{{ $key }}" :label="__($labelKey)" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Azioni: Indietro (a cancellazione) + Avanti (pill scuro) --}}
                    <div class="mt-8 flex items-center justify-end gap-6">
                        <flux:button href="{{ route('partner.smartbox.cancellation') }}" variant="ghost" class="!text-[15px] !font-bold !text-[#959595] hover:!text-ink">{{ __('partner.smartbox_meals.back') }}</flux:button>
                        <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-8 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.smartbox_meals.next') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
