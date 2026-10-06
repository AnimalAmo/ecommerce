{{-- Modale registrazione (XD: "Pop-Up - Registrati"). Dal 06/10/2026 un solo passo: nome, email,
     password e consensi; il resto del profilo si completa dopo (richiesta della cliente). --}}
@php
    // Campo XD app: box 343x45, radius 5, bordo #C8C8C8, testo 14 (#0D171A, placeholder #959595); label 12 #555555.
    $labelClass = '!text-xs !text-[#555555]';
    $inputClass = 'max-lg:!h-[45px] max-lg:!rounded-[5px] max-lg:!border-[#C8C8C8] max-lg:!text-sm max-lg:!text-[#0D171A] max-lg:placeholder:!text-[#959595]';
    // Checkbox XD: cerchio 20px, acceso #6CD1EF con spunta bianca.
    $checkboxClass = '!size-5 [--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan';
@endphp

<flux:modal name="register" :closable="false" class="w-full !max-w-[537px] !rounded-none bg-white !px-8 !py-6 backdrop:!bg-black/30 max-lg:!m-0 max-lg:!min-h-dvh max-lg:!max-h-none max-lg:!max-w-full max-lg:!px-4">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="back" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">{{ __('auth-modal.back') }}</flux:button>
    </div>

    {{-- Logo centrato, solo mobile (XD app "Registrazione") --}}
    <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="mx-auto mt-10 w-[108px] lg:hidden">

    <flux:heading level="2" class="mt-8 text-center !text-lg !font-semibold !text-[#0D171A] max-lg:hidden">{{ __('auth-modal.register.title') }}</flux:heading>
    <p class="mt-2 text-center text-[13px] text-[#555555]">{{ __('auth-modal.register.subtitle') }}</p>

    <form wire:submit="register" class="mt-6 max-lg:mt-8">
        <div class="space-y-4">
            <flux:field>
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.register.first_name') }}</flux:label>
                <flux:input wire:model="form.firstName" autocomplete="given-name" placeholder="{{ __('auth-modal.register.first_name') }}" class="{{ $inputClass }}" />
                <flux:error name="form.firstName" class="!mt-1 !text-xs" />
            </flux:field>
            <flux:field>
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.email') }}</flux:label>
                <flux:input type="email" wire:model="form.email" autocomplete="email" placeholder="{{ __('auth-modal.email') }}" class="{{ $inputClass }}" />
                <flux:error name="form.email" class="!mt-1 !text-xs" />
            </flux:field>
            <flux:field>
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.password') }}</flux:label>
                <flux:input type="password" wire:model="form.password" autocomplete="new-password" placeholder="{{ __('auth-modal.password') }}" class="{{ $inputClass }}" />
                <flux:error name="form.password" class="!mt-1 !text-xs" />
            </flux:field>
            <flux:field>
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.register.repeat_password') }}</flux:label>
                <flux:input type="password" wire:model="form.passwordConfirmation" autocomplete="new-password" placeholder="{{ __('auth-modal.register.repeat_password') }}" class="{{ $inputClass }}" />
                <flux:error name="form.passwordConfirmation" class="!mt-1 !text-xs" />
            </flux:field>
        </div>

        {{-- Consensi: testo 12 #555555. Termini e privacy obbligatori, gli altri due facoltativi. --}}
        <div class="mt-6 space-y-3 max-lg:mt-8 max-lg:space-y-4">
            <flux:field variant="inline">
                <flux:checkbox wire:model="form.termsAccepted" class="{{ $checkboxClass }}" />
                {{-- Uno span attorno: flux:label è flex, e senza i link perderebbero gli spazi ai lati. --}}
                <flux:label class="{{ $labelClass }}"><span>
                    {!! __('auth-modal.register.terms', [
                        'terms' => '<a href="'.e(route('terms.customers')).'" target="_blank" rel="noopener" class="underline">'.e(__('auth-modal.register.terms_link')).'</a>',
                        'privacy' => '<a href="'.e(route('privacy')).'" target="_blank" rel="noopener" class="underline">'.e(__('auth-modal.register.privacy_link')).'</a>',
                    ]) !!}
                </span></flux:label>
                <flux:error name="form.termsAccepted" class="!mt-1 !text-xs" />
            </flux:field>
            <flux:field variant="inline">
                <flux:checkbox wire:model="form.newsletter" class="{{ $checkboxClass }}" />
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.register.newsletter') }}</flux:label>
            </flux:field>
            <flux:field variant="inline">
                <flux:checkbox wire:model="form.privacyConsent" class="{{ $checkboxClass }}" />
                <flux:label class="{{ $labelClass }}">{{ __('auth-modal.register.privacy_consent') }}</flux:label>
            </flux:field>
        </div>

        {{-- Bottone XD app: 343x39, radius 19, #6CD1EF, testo 15 SemiBold bianco --}}
        <div class="mt-8 flex justify-center">
            <flux:button type="submit" class="!rounded-full !bg-brand-cyan !px-8 !text-[15px] !font-bold !text-white hover:!bg-[#4FB9DB] max-lg:h-[39px] max-lg:w-full max-lg:!font-semibold">
                {{ __('auth-modal.register.create_profile') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
