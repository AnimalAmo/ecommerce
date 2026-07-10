{{-- Profilo partner – Sicurezza (XD "Profilo – sicurezza") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp
@php $labelClass = '!text-xs !font-normal !text-[#555555]'; @endphp
@php $ctaClass = '!text-[13px] !font-semibold !text-[#FF3EA5] !decoration-current hover:!text-[#DD2A8A]'; @endphp

<div class="flex min-h-screen flex-col bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="flex flex-col gap-8 md:flex-row">

                @include('partials.partner-profile-sidebar')

                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.security_heading') }}</h1>

                    {{-- Password (sola visualizzazione) + link reimposta --}}
                    <div class="mt-8 max-w-[520px]">
                        <flux:field>
                            <flux:label class="{{ $labelClass }}">{{ __('partner.profile.password') }} *</flux:label>
                            <flux:input type="password" value="password" readonly class="{{ $fieldClass }}" />
                        </flux:field>
                        <div class="mt-3">
                            <flux:link href="#" class="{{ $ctaClass }}">{{ __('partner.profile.reset_password') }}</flux:link>
                        </div>
                    </div>

                    {{-- Impostazioni sulla Privacy (copy segnaposto, in attesa del testo cliente) --}}
                    <div class="mt-12 max-w-[820px]">
                        <p class="text-[15px] leading-relaxed text-[#627277]">{{ $privacyText }}</p>
                        <div class="mt-4">
                            <flux:link href="#" class="{{ $ctaClass }}">{{ __('partner.profile.privacy_settings') }}</flux:link>
                        </div>
                    </div>

                    {{-- Elimina account (copy segnaposto + flusso backend pendente) --}}
                    <div class="mt-10 max-w-[820px]">
                        <p class="text-[15px] leading-relaxed text-[#627277]">{{ $deleteText }}</p>
                        <div class="mt-4">
                            <flux:button variant="ghost" class="!px-0 {{ $ctaClass }}">{{ __('partner.profile.delete_account') }}</flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
