{{-- Profilo – dati pagamento (XD app 375x812 / desktop 1012x397): il titolare è un
     campo nostro, numero/scadenza/cvv stanno nel Payment Element di Stripe in
     modalità setup — il PAN non passa mai da qui. --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 [&_input::placeholder]:!text-[#959595] max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!px-4 max-lg:[&_input]:!text-sm';
    $labelClasses = '!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555] max-lg:!pl-[7px]';
    $noticeClasses = 'rounded-[3px] border border-[#E9E9E9] bg-[#F4F4F4] px-[15px] py-3 text-[13px] leading-5 text-[#555555] max-lg:rounded-[5px]';
    $primaryButton = '!h-10 !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan max-lg:!h-[39px] max-lg:!w-full max-lg:!font-semibold';
    $ghostButton = '!h-10 !rounded-full !border !border-[#C8C8C8] !bg-white !text-[15px] !font-bold !text-[#0D171A] !shadow-none max-lg:!h-[39px] max-lg:!w-full max-lg:!font-semibold';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-4">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px] max-lg:gap-[22px]">
                @include('partials.profile-sidebar', ['active' => 'pagamento'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }}">
                    @include('partials.profile-mobile-header', ['title' => __('profile.nav_payment_data')])

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.payment_title') }}</h1>

                    @if ($editing)
                        {{-- Inserimento/sostituzione carta --}}
                        <div class="mt-6 space-y-4 max-lg:mt-[10px] max-lg:space-y-[21px]">
                            <div class="w-full min-[68rem]:w-[472px]">
                                <flux:label class="{{ $labelClasses }}">{{ __('profile.payment_card_holder') }} *</flux:label>
                                <div class="mt-[11px] max-lg:mt-1">
                                    <flux:input type="text" wire:model="cardHolder" class="{{ $inputClasses }}" />
                                </div>
                                <flux:error name="cardHolder" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                            </div>

                            {{-- Numero/scadenza/cvv: le stesse tre label del mock XD, ma disegnate
                                 dal Payment Element di Stripe (il PAN non tocca il nostro dominio). --}}
                            <div class="w-full min-[68rem]:w-[472px]">
                                @if ($paymentUnavailable || $clientSecret === null)
                                    <div class="{{ $noticeClasses }}">{{ __('profile.payment_unavailable') }}</div>
                                @else
                                    {{-- wire:ignore: l'iframe Stripe non va toccato dai re-render Livewire.
                                         La key cambia col client secret così un nuovo SetupIntent rimonta l'element. --}}
                                    <div wire:ignore wire:key="stripe-setup-{{ $clientSecret }}"
                                         x-data="stripeSetupMethod(@js($clientSecret), @js($stripeKey), { returnUrl: @js(route('profilo.pagamento')), country: @js($billingCountry), incompleteMessage: @js(__('profile.payment_card_incomplete')) })">
                                        <div x-ref="element"></div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-3 max-lg:mt-6 max-lg:flex-col-reverse max-lg:gap-[10px]">
                            @if ($user->hasSavedCard())
                                <flux:button wire:click="cancelSetup" class="!w-[103px] {{ $ghostButton }}">{{ __('profile.cancel') }}</flux:button>
                            @endif

                            <flux:button wire:click="save" :disabled="$saving || $paymentUnavailable || ! $elementReady" class="!w-[103px] {{ $primaryButton }}">{{ __('profile.save') }}</flux:button>
                        </div>
                    @else
                        {{-- Carta salvata: stessi campi del mock XD, in sola lettura --}}
                        <div class="mt-6 space-y-4 max-lg:mt-[10px] max-lg:space-y-[21px]">
                            <div class="flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5 max-lg:gap-[21px]">
                                @foreach ([
                                    ['key' => 'holder', 'label' => __('profile.payment_card_holder'), 'value' => $user->card_holder ?? $user->name],
                                    ['key' => 'number', 'label' => __('profile.payment_card_number'), 'value' => '•••• •••• •••• '.$user->card_last4],
                                ] as $field)
                                    <div class="w-full min-[68rem]:w-[472px]" wire:key="saved-{{ $field['key'] }}">
                                        <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                        <div class="mt-[11px] max-lg:mt-1">
                                            <flux:input type="text" value="{{ $field['value'] }}" readonly class="{{ $inputClasses }}" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ([
                                ['key' => 'expiry', 'label' => __('profile.payment_card_expiry'), 'value' => $user->card_expiry],
                                ['key' => 'cvv', 'label' => __('profile.payment_card_cvv'), 'value' => '•••'],
                            ] as $field)
                                <div class="w-full min-[68rem]:w-[295px]" wire:key="saved-{{ $field['key'] }}">
                                    <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                    <div class="mt-[11px] max-lg:mt-1">
                                        <flux:input type="text" value="{{ $field['value'] }}" readonly class="{{ $inputClasses }}" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 flex items-center gap-3 max-lg:mt-6 max-lg:flex-col max-lg:gap-[10px]">
                            <flux:button wire:click="openSetup" class="!w-[150px] {{ $primaryButton }}">{{ __('profile.payment_edit_card') }}</flux:button>
                            <flux:button wire:click="remove" wire:confirm="{{ __('profile.payment_remove_confirm') }}" class="!w-[150px] {{ $ghostButton }}">{{ __('profile.payment_remove_card') }}</flux:button>
                        </div>
                    @endif
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
