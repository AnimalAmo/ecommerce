{{-- Profilo – sicurezza (XD): cornice Profilo, card "Sicurezza e Privacy" con password + link privacy --}}
@php
    $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8';
    $card = 'rounded-[3px] border border-[#E9E9E9] bg-white shadow-[0px_1px_10px_#0000001A] max-lg:rounded-none max-lg:border-0 max-lg:bg-transparent max-lg:shadow-none';
    $inputClasses = '!min-w-0 !border-0 !bg-transparent !shadow-none !ring-0 [&_input]:!h-10 [&_input]:!w-full [&_input]:!rounded-[3px] [&_input]:!border [&_input]:!border-[#C8C8C8]/70 [&_input]:!bg-white [&_input]:!px-[15px] [&_input]:!text-[15px] [&_input]:!text-[#0D171A] [&_input]:!shadow-none [&_input]:!ring-0 max-lg:[&_input]:!rounded-[5px] max-lg:[&_input]:!px-4 max-lg:[&_input]:!text-sm';
    $labelClasses = '!block !pl-[15px] !text-xs !font-normal !leading-none !text-[#555555] max-lg:!pl-[7px]';
@endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1 bg-[linear-gradient(to_top_left,#FF3EA51A,#68CDEB1A)]">
        <div class="{{ $px }} pb-[140px] pt-[60px] max-lg:pb-8 max-lg:pt-9">
            <div class="flex flex-col gap-10 lg:flex-row lg:gap-[61px]">
                @include('partials.profile-sidebar', ['active' => 'sicurezza'])

                <section class="w-full min-w-0 p-6 lg:w-[1012px] lg:shrink-0 max-lg:p-0 {{ $card }}">
                    @include('partials.profile-mobile-header', ['title' => __('profile.security_title')])

                    <h1 class="text-2xl font-bold leading-none text-black max-lg:hidden">{{ __('profile.security_title') }}</h1>

                    {{-- Password attuale: ri-autenticazione obbligatoria prima del cambio (assente nell'artboard app, ma il flusso la esige) --}}
                    <div class="mt-6 w-full min-[68rem]:w-[472px] max-lg:mt-[10px]" wire:key="field-currentPassword">
                        <flux:label class="{{ $labelClasses }}">{{ __('profile.current_password_label') }}</flux:label>
                        <div class="mt-[11px] max-lg:mt-1">
                            <flux:input type="password" wire:model="currentPassword" placeholder="***************" class="{{ $inputClasses }} [&_input]:placeholder:!text-[#0D171A]" />
                        </div>
                        <flux:error name="currentPassword" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                    </div>

                    {{-- Password + conferma affiancate (472 + gap 20) --}}
                    <div class="mt-4 flex flex-col gap-4 min-[68rem]:flex-row min-[68rem]:gap-5 max-lg:mt-[21px] max-lg:gap-[21px]">
                        @foreach ([
                            ['model' => 'password', 'label' => __('profile.password_label')],
                            ['model' => 'passwordConfirm', 'label' => __('profile.password_confirm_label')],
                        ] as $field)
                            <div class="w-full min-[68rem]:w-[472px]" wire:key="field-{{ $field['model'] }}">
                                <flux:label class="{{ $labelClasses }}">{{ $field['label'] }}</flux:label>
                                <div class="mt-[11px] max-lg:mt-1">
                                    {{-- Asterischi XD resi come placeholder (un valore precompilato verrebbe inviato) --}}
                                    <flux:input type="password" wire:model="{{ $field['model'] }}" placeholder="***************" class="{{ $inputClasses }} [&_input]:placeholder:!text-[#0D171A]" />
                                </div>
                                <flux:error name="{{ $field['model'] }}" class="!mt-1 !pl-[15px] !text-xs max-lg:!pl-[7px]" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Link e testi privacy come da XD (y321..533): magenta 13px, lorem 15px, elimina #EA2E68.
                         App: "Reimposta Password" allineato a destra e in ciano --}}
                    {{-- TODO: flussi reimposta password / impostazioni privacy / elimina account — flussi non presenti nel design, da segnalare al cliente --}}
                    <a href="#" class="mt-4 block text-[13px] leading-none text-brand-magenta max-lg:mt-[29px] max-lg:text-right max-lg:text-brand-cyan">{{ __('profile.reset_password') }}</a>

                    <p class="mt-[30px] max-w-[964px] text-[15px] leading-[21px] text-[#0D171A] max-lg:mt-[37px] max-lg:leading-[19px]">{{ $privacyPlaceholder }}</p>

                    <a href="#" class="mt-[14px] block text-[13px] leading-none text-brand-magenta max-lg:mt-[18px]">{{ __('profile.privacy_settings') }}</a>

                    <p class="mt-[29px] max-w-[964px] text-[15px] leading-[21px] text-[#0D171A] max-lg:mt-[43px] max-lg:leading-[19px]">{{ $privacyPlaceholder }}</p>

                    <a href="#" class="mt-4 block text-[13px] leading-none text-[#EA2E68] max-lg:mt-[36px]">{{ __('profile.delete_account') }}</a>

                    <flux:button wire:click="save" class="mt-[37px] !h-10 !w-[103px] !rounded-full !border-0 !bg-brand-cyan !text-[15px] !font-bold !text-white !shadow-none hover:!bg-brand-cyan max-lg:!mt-8 max-lg:!h-[39px] max-lg:!w-full max-lg:!font-semibold">{{ __('profile.save') }}</flux:button>
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
