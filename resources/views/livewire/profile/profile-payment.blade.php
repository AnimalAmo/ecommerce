{{-- Profilo – metodo pagamento (XD): stessa cornice del Profilo, card "Informazioni del metodo di pagamento" 1012x397 --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#959595] max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!px-4 max-lg:[&_input]:!text-sm';
    $labelClasses = '!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555] max-lg:!pl-[7px]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-9">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px] max-lg:gap-[22px]">
                @include('partials.profile-sidebar', ['active' => 'pagamento'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }}">
                    @include('partials.profile-mobile-header', ['title' => __('profile.nav_payment_data')])

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.payment_title') }}</h1>

                    <div class="mt-6 space-y-4 max-lg:mt-[10px] max-lg:space-y-[21px]">
                        {{-- Riga 1: titolare + numero carta affiancati (472 + gap 20) --}}
                        <div class="flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5 max-lg:gap-[21px]">
                            @foreach ([
                                ['model' => 'cardHolder', 'label' => __('profile.payment_card_holder')],
                                ['model' => 'cardNumber', 'label' => __('profile.payment_card_number')],
                            ] as $field)
                                <div class="w-full min-[68rem]:w-[472px]" wire:key="field-{{ $field['model'] }}">
                                    <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                    <div class="mt-[11px] max-lg:mt-1">
                                        <flux:input type="text" wire:model="{{ $field['model'] }}" class="{{ $inputClasses }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Righe 2-3: scadenza e cvv da 295px, vuoti con placeholder come in XD (app: a tutta larghezza) --}}
                        @foreach ([
                            ['model' => 'cardExpiry', 'label' => __('profile.payment_card_expiry'), 'placeholder' => __('profile.payment_expiry_placeholder')],
                            ['model' => 'cardCvv', 'label' => __('profile.payment_card_cvv'), 'placeholder' => __('profile.payment_cvv_placeholder')],
                        ] as $field)
                            <div class="w-full min-[68rem]:w-[295px]" wire:key="field-{{ $field['model'] }}">
                                <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                <div class="mt-[11px] max-lg:mt-1">
                                    <flux:input type="text" wire:model="{{ $field['model'] }}" placeholder="{{ $field['placeholder'] }}" class="{{ $inputClasses }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- TODO step 4 (Stripe): salvataggio metodo di pagamento — resta statico fino all'integrazione --}}
                    <flux:button class="mt-8 !h-10 !w-[103px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan max-lg:!h-[39px] max-lg:!w-full max-lg:!font-semibold">{{ __('profile.save') }}</flux:button>
                </section>
            </div>
        </div>
    </main>

    <div class="max-lg:hidden">
        @include('partials.footer-minimal')
    </div>

    <div class="lg:hidden">
        @include('partials.mobile-tabbar')
    </div>
</div>
