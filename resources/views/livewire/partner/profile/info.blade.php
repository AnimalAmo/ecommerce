{{-- Profilo partner – Informazioni personali (XD "Profilo - info personali") --}}
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

                {{-- Card contenuto --}}
                <div class="flex-1 rounded-[10px] bg-white p-6">
                    <h1 class="text-2xl font-bold text-[#0D171A]">{{ __('partner.profile.info_heading') }}</h1>

                    <form wire:submit="save" class="mt-8">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-5 md:grid-cols-3">
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.first_name') }} *</flux:label>
                                <flux:input wire:model="form.firstName" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.last_name') }} *</flux:label>
                                <flux:input wire:model="form.lastName" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.email') }} *</flux:label>
                                <flux:input type="email" wire:model="form.email" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.tax_code') }} *</flux:label>
                                <flux:input wire:model="form.taxCode" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.phone') }} *</flux:label>
                                <flux:input type="tel" wire:model="form.phone" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.vat') }} *</flux:label>
                                <flux:input wire:model="form.vat" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.business_name') }} *</flux:label>
                                <flux:input wire:model="form.businessName" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.address') }} *</flux:label>
                                <flux:input wire:model="form.address" class="{{ $fieldClass }}" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-4">
                                <x-partner.province-select :provinces="$provinces" model="form.province" :label="__('partner.profile.province')" />
                                <flux:field>
                                    <flux:label class="{{ $labelClass }}">{{ __('partner.profile.zip') }} *</flux:label>
                                    <flux:input wire:model="form.zip" inputmode="numeric" class="{{ $fieldClass }}" />
                                </flux:field>
                            </div>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.city') }} *</flux:label>
                                <flux:input wire:model="form.city" class="{{ $fieldClass }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.pec') }} *</flux:label>
                                <flux:input type="email" wire:model="form.pec" class="{{ $fieldClass }}" />
                            </flux:field>
                            <flux:field>
                                <flux:label class="{{ $labelClass }}">{{ __('partner.profile.sdi') }} *</flux:label>
                                <flux:input wire:model="form.sdi" class="{{ $fieldClass }}" />
                            </flux:field>
                        </div>

                        <div class="mt-10 flex items-center justify-end">
                            <flux:button type="submit" class="!h-10 !rounded-full !border-0 !bg-[#0D171A] !px-10 !text-[15px] !font-bold !text-white !shadow-none hover:!bg-[#232A2C]">{{ __('partner.profile.save') }}</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('partials.partner-footer')
</div>
