{{-- Lavora con noi (XD: "Lavora con noi – inserimento campi") --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    {{-- Banda gradiente (XD: 296deg #FF3EA526 → #68CDEB33) --}}
    <main class="flex-1 bg-[linear-gradient(296deg,#FF3EA526_0%,#68CDEB33_100%)]">
        <div class="{{ $px }} pt-[60px] pb-20">
            <h1 class="text-4xl font-bold text-black">{{ __('partner.heading') }}</h1>
            <p class="mt-4 max-w-4xl text-lg text-black">{{ __('partner.intro') }}</p>

            {{-- Card form (XD: 1496x512, bianco op 0.5, bordo #E9E9E9, r 3) --}}
            <form wire:submit="submit" class="mt-10 rounded-[3px] border border-gray-150 bg-white/50 px-6 pb-8 pt-8">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.first_name') }}</flux:label>
                        <flux:input wire:model="form.firstName" placeholder="{{ __('partner.first_name') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.last_name') }}</flux:label>
                        <flux:input wire:model="form.lastName" placeholder="{{ __('partner.last_name') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.email') }}</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="{{ __('partner.email') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.phone') }}</flux:label>
                        <flux:input type="tel" wire:model="form.phone" placeholder="{{ __('partner.phone') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.website') }}</flux:label>
                        <flux:input wire:model="form.website" placeholder="{{ __('partner.website') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.city') }}</flux:label>
                        <flux:input wire:model="form.city" placeholder="{{ __('partner.city') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.business_name') }}</flux:label>
                        <flux:input wire:model="form.businessName" placeholder="{{ __('partner.business_name') }}" class="[&_input]:!border-[#C8C8C8] [&_input]:placeholder:!italic [&_input]:placeholder:!text-[#555555]" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.role') }}</flux:label>
                        <flux:select wire:model="form.role" placeholder="{{ __('partner.role') }}" class="!border-[#C8C8C8] [&_select]:!border-[#C8C8C8]">
                            <flux:select.option>{{ __('partner.role_owner') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_manager') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_employee') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.role_other') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-gray-600">{{ __('partner.offer_type') }}</flux:label>
                        <flux:select wire:model="form.offerType" placeholder="{{ __('partner.offer_type') }}" class="!border-[#C8C8C8] [&_select]:!border-[#C8C8C8]">
                            <flux:select.option>{{ __('partner.offer_accommodation') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_activities') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_dining') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_pet_services') }}</flux:select.option>
                            <flux:select.option>{{ __('partner.offer_other') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field class="mt-4">
                    <flux:label class="!text-xs !text-gray-600">{{ __('partner.description') }}</flux:label>
                    <flux:textarea wire:model="form.description" rows="4" placeholder="{{ __('partner.description_placeholder') }}" class="!h-[117px] !border-[#C8C8C8] placeholder:!italic placeholder:!text-[#555555]" />
                </flux:field>

                <div class="mt-8 flex justify-end">
                    <flux:button type="submit" class="!rounded-full !bg-brand-yellow !px-6 !text-sm !font-bold !text-ink hover:!bg-[#0D171A] hover:!text-white">{{ __('partner.submit') }}</flux:button>
                </div>
            </form>
        </div>
    </main>

    @include('partials.footer-minimal')
</div>
