{{-- Profilo partner – Metodo di pagamento (XD "Profilo – metodo di pagamento") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp
@php $fieldClass = '[&_input]:!h-10 [&_input]:!rounded-[3px] [&_input]:!border-[#C8C8C8]'; @endphp
@php $labelClass = '!text-xs !font-normal !text-[#555555]'; @endphp

<div class="flex min-h-screen flex-col bg-[linear-gradient(296deg,#FF3EA51A_0%,#68CDEB1A_100%)] font-sans text-ink antialiased">

    @include('partials.partner-dash-header')

    <main class="flex-1 pt-10 pb-20">
        <div class="{{ $px }}">
            <div class="flex flex-col gap-8 md:flex-row">

                @include('partials.partner-profile-sidebar')

                <div class="hidden w-px self-stretch bg-white md:block"></div>

                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.payment_heading') }}</h1>

                    <form wire:submit="save" class="mt-8">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-3">
                            <div class="md:col-span-2 md:grid-cols-2 grid-cols-1 grid gap-x-4 gap-y-5 ">
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.account_holder') }} *</flux:label>
                                    <flux:input wire:model="form.accountHolder" class="{{ $fieldClass }}" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.iban') }} *</flux:label>
                                    <flux:input wire:model="form.iban" class="{{ $fieldClass }}" />
                                </flux:field>

                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.sdi') }} *</flux:label>
                                    <flux:input wire:model="form.sdi" class="{{ $fieldClass }}" />
                                </flux:field>
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.bic') }} *</flux:label>
                                    <flux:input wire:model="form.bic" class="{{ $fieldClass }}" />
                                </flux:field>

                                <div class="flex justify-end md:col-span-2">
                                    <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-10 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.profile.save') }}</flux:button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
