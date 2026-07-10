{{-- Profilo partner – Sicurezza (XD "Profilo – sicurezza") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp
@php $labelClass = '!text-xs !font-normal !text-[#555555]'; @endphp

<div class="flex min-h-screen flex-col bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="flex flex-col gap-8 md:flex-row">

                @include('partials.partner-profile-sidebar')

                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.security_heading') }}</h1>

                    <form wire:submit="save" class="mt-8 max-w-[520px]">
                        <div class="flex flex-col gap-5">
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.current_password') }} *</flux:label>
                                <flux:input type="password" wire:model="currentPassword" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.new_password') }} *</flux:label>
                                <flux:input type="password" wire:model="password" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.confirm_password') }} *</flux:label>
                                <flux:input type="password" wire:model="passwordConfirm" class="{{ $fieldClass }}" />
                            </flux:field>
                        </div>

                        <div class="mt-8 flex items-center justify-start">
                            <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-10 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.profile.save') }}</flux:button>
                        </div>
                    </form>

                    {{-- Impostazioni sulla Privacy (copy segnaposto, in attesa del testo cliente) --}}
                    <div class="mt-12 border-t border-gray-150 pt-8">
                        <h2 class="text-lg font-bold text-[#0D171A]">{{ __('partner.profile.privacy_settings') }}</h2>
                        <p class="mt-4 max-w-[820px] text-[15px] leading-relaxed text-[#627277]">{{ $privacyPlaceholder }}</p>

                        <div class="mt-8">
                            <flux:link href="#" class="!text-[15px] !font-bold !text-[#E5484D] hover:!underline">{{ __('partner.profile.delete_account') }}</flux:link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
