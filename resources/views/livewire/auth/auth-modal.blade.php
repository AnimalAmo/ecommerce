{{-- Modale login/registrazione (XD: "Pop-Up - Login") --}}
<flux:modal name="login" :closable="false" class="w-full !max-w-[868px] !rounded-none bg-white !px-[32px] !py-6 backdrop:!bg-black/30 max-lg:!m-0 max-lg:!min-h-dvh max-lg:!max-h-none max-lg:!max-w-full max-lg:!px-4">
    <div class="mb-6 flex justify-between lg:justify-end">
        <flux:modal.close>
            <flux:button variant="ghost" size="xs" class="lg:hidden !text-[13px] !font-normal !text-[#959595] hover:!text-black [&>span]:flex [&>span]:items-center [&>span]:gap-2">
                <flux:icon.chevron-left class="h-4 w-4" />
                {{ __('auth-modal.back') }}
            </flux:button>
        </flux:modal.close>
        <flux:modal.close>
            <flux:button variant="ghost" size="xs" class="max-lg:hidden !text-xs !font-normal !text-[#555555] hover:!text-black">
                <flux:icon.close class="h-3 w-3" />
                {{ __('auth-modal.close') }}
            </flux:button>
        </flux:modal.close>
    </div>

    {{-- Logo centrato, solo mobile (XD app "Accedi") --}}
    <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="mx-auto mt-10 w-[108px] lg:hidden">

    <flux:heading level="2" class="text-center !text-2xl !font-semibold !text-black max-lg:hidden">{{ __('auth-modal.welcome') }}</flux:heading>

    <div class="mt-6 flex flex-col gap-6 max-lg:mt-10 lg:flex-row">
        {{-- Card cliente --}}
        <flux:card class="w-full !rounded-[3px] !border-gray-150 !bg-white !px-4 !py-6 shadow-[0px_1px_10px_#0000001A] max-lg:!border-0 max-lg:!px-0 max-lg:!py-0 max-lg:!shadow-none lg:w-[400px]">
            <flux:heading level="3" class="text-center !text-lg !font-medium !text-black max-lg:hidden">{{ __('auth-modal.client.title') }}</flux:heading>

            <form wire:submit="login">
                <div class="mt-6 space-y-4">
                    <flux:field>
                        <flux:label class="!text-xs !text-[#555555]">{{ __('auth-modal.email') }}</flux:label>
                        <flux:input type="email" wire:model="form.email" placeholder="{{ __('auth-modal.email') }}" />
                        <flux:error name="form.email" class="!mt-1 !text-xs" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="!text-xs !text-[#555555]">{{ __('auth-modal.password') }}</flux:label>
                        <flux:input type="password" wire:model="form.password" placeholder="{{ __('auth-modal.password') }}" />
                        <flux:error name="form.password" class="!mt-1 !text-xs" />
                    </flux:field>
                </div>
                <div class="mt-2 text-right">
                    <flux:link as="button" wire:click="openForgotPassword" variant="ghost" class="!text-[13px] !font-normal !text-[#555555] max-lg:!font-medium max-lg:!text-[#6CD1EF]">{{ __('auth-modal.forgot_password') }}</flux:link>
                </div>

                <div class="mt-6 flex justify-center">
                    <flux:button type="submit" class="!rounded-full !bg-[#0D171A] !px-9 !text-[15px] !font-bold !text-white hover:!bg-[#232A2C] max-lg:w-full max-lg:!rounded-[5px] max-lg:!text-sm">{{ __('auth-modal.login') }}</flux:button>
                </div>
            </form>

            <flux:text class="mt-6 text-center !text-[15px] !text-[#0D171A]">{{ __('auth-modal.client.no_account') }} <flux:link as="button" wire:click="openRegister" variant="ghost" class="!font-bold !text-[#F2BD2D]">{{ __('auth-modal.client.register_free') }}</flux:link></flux:text>
        </flux:card>

        {{-- Card partner --}}
        <flux:card class="w-full !rounded-[3px] !border-gray-150 !bg-white !px-4 !py-6 shadow-[0px_1px_10px_#0000001A] max-lg:!border-0 max-lg:!border-t max-lg:!rounded-none max-lg:!border-gray-150 max-lg:!px-0 max-lg:!py-6 max-lg:!shadow-none lg:w-[400px]">
            <flux:heading level="3" class="text-center !text-lg !font-medium !text-black">{{ __('auth-modal.partner.title') }}</flux:heading>

            <flux:text class="mt-10 text-center !text-[15px] text-black">{{ __('auth-modal.partner.already_partner') }} <flux:link as="button" wire:click="openPartnerLogin" variant="ghost" class="!text-[#68CDEB] !font-bold">{{ __('auth-modal.partner.access_reserved_area') }}</flux:link></flux:text>

            <flux:separator class="my-6 !bg-[#DEDEDE]" />

            <flux:text class="text-center !text-[15px] !text-[#0D171A]">{{ __('auth-modal.partner.want_to_become') }} <flux:link href="{{ route('work-with-us') }}" variant="ghost" class="!font-bold !text-[#F2BD2D]">{{ __('auth-modal.partner.fill_form') }}</flux:link></flux:text>
        </flux:card>
    </div>
</flux:modal>
