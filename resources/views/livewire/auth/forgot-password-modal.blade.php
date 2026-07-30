{{-- Modale "Password dimenticata": stessa cornice della modale login partner
     (660px, freccia indietro, form 472px), niente artboard XD dedicata. --}}
<flux:modal name="forgot-password" :closable="false" class="w-full !max-w-[660px] !rounded-[3px] bg-white !px-6 !py-6 backdrop:!bg-black/30 max-lg:!m-0 max-lg:!min-h-dvh max-lg:!max-h-none max-lg:!max-w-full max-lg:!rounded-none max-lg:!px-4">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="backToLogin" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">{{ __('auth-modal.back') }}</flux:button>
    </div>

    @if ($sent)
        {{-- Stato di conferma: nessuna informazione su cosa sia successo davvero
             (email registrata o no), solo cosa deve fare l'utente adesso. --}}
        <div class="mx-auto mt-10 w-full max-w-[472px] pb-4 text-center max-lg:mt-16">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-cyan/15">
                <flux:icon.envelope class="h-7 w-7 text-brand-cyan" />
            </div>

            <flux:heading level="2" class="mt-6 !text-lg !font-medium !text-black">{{ __('auth-modal.forgot.sent_title') }}</flux:heading>

            <flux:text class="mt-4 !text-[15px] !leading-[21px] !text-[#0D171A]">{{ __('auth-modal.forgot.sent_text', ['email' => $sentTo]) }}</flux:text>

            <flux:text class="mt-2 !text-[13px] !text-[#555555]">{{ __('auth-modal.forgot.sent_validity') }} {{ __('auth-modal.forgot.sent_spam_hint') }}</flux:text>

            <div class="mt-10 flex justify-center max-lg:mt-8">
                <flux:button wire:click="backToLogin" class="!rounded-full !bg-[#0D171A] !px-9 !text-[15px] !font-bold !text-white hover:!bg-[#232A2C] max-lg:w-full max-lg:!rounded-[5px] max-lg:!text-sm">{{ $origin === '' ? __('auth-modal.close') : __('auth-modal.forgot.back_to_login') }}</flux:button>
            </div>
        </div>
    @else
        <flux:heading level="2" class="mt-4 text-center !text-lg !font-medium !text-black">{{ __('auth-modal.forgot.title') }}</flux:heading>

        <form wire:submit="send" class="mx-auto mt-8 w-full max-w-[472px] pb-4 max-lg:mt-10">
            <flux:text class="text-center !text-[15px] !leading-[21px] !text-[#555555]">{{ __('auth-modal.forgot.intro') }}</flux:text>

            <div class="mt-8 max-lg:mt-6">
                {{-- Campo composto a mano: l'errore va aggiunto esplicitamente,
                     senza `label` come prop Flux non inietta nessun flux:error. --}}
                <flux:field>
                    <flux:label class="!text-xs !text-[#555555]">{{ __('auth-modal.email') }}</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="{{ __('auth-modal.email') }}" />
                    <flux:error name="email" class="!mt-1 !text-xs" />
                </flux:field>
            </div>

            <div class="mt-8 flex justify-center max-lg:mt-6">
                <flux:button type="submit" class="!rounded-full !bg-[#0D171A] !px-9 !text-[15px] !font-bold !text-white hover:!bg-[#232A2C] max-lg:w-full max-lg:!rounded-[5px] max-lg:!text-sm">{{ __('auth-modal.forgot.send_link') }}</flux:button>
            </div>
        </form>
    @endif
</flux:modal>
