{{-- Modale login partner (XD: "Pop-Up - Login – partner") --}}
<flux:modal name="partner-login" :closable="false" class="w-full !max-w-[660px] !rounded-[3px] bg-white !px-6 !py-6 backdrop:!bg-black/30">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="backToLogin" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">{{ __('auth-modal.back') }}</flux:button>
    </div>

    <flux:heading level="2" class="mt-4 text-center !text-lg !font-medium !text-black">{{ __('auth-modal.partner.title') }}</flux:heading>

    <form wire:submit="login" class="mx-auto mt-14 w-full max-w-[472px]">
        <div class="space-y-4">
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
            <flux:link as="button" wire:click="openForgotPassword" variant="ghost" class="!text-[13px] !font-normal !text-[#555555]">{{ __('auth-modal.forgot_password') }}</flux:link>
        </div>

        <div class="mt-6 flex justify-center">
            <flux:button type="submit" class="!rounded-full !bg-brand-cyan !px-8 !text-[15px] !font-bold !text-white hover:!bg-[#4FB9DB]">{{ __('auth-modal.login') }}</flux:button>
        </div>
    </form>

    <flux:text class="mt-16 mb-4 text-center !text-[15px] !text-[#0D171A]">{{ __('auth-modal.partner.not_yet_partner') }} <flux:link href="{{ route('work-with-us') }}" variant="ghost" class="!font-bold !text-[#F2BD2D]">{{ __('auth-modal.partner.request_access') }}</flux:link></flux:text>
</flux:modal>
