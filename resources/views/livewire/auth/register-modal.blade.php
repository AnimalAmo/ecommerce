{{-- Modale registrazione a step (XD: "Pop-Up - Registrati - primo/secondo/terzo/quarto step") --}}
<flux:modal name="register" :closable="false" class="w-full !max-w-[537px] !rounded-none bg-white !px-8 !py-6 backdrop:!bg-black/30 max-lg:!m-0 max-lg:!min-h-dvh max-lg:!max-h-none max-lg:!max-w-full max-lg:!px-4">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="back" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">{{ __('auth-modal.back') }}</flux:button>
    </div>

    {{-- Logo centrato, solo mobile (XD app "Registrazione") --}}
    <img src="{{ asset('img/logo.svg') }}" alt="AnimalAmo" class="mx-auto mt-10 w-[108px] lg:hidden">

    <flux:heading level="2" class="mt-8 text-center !text-lg !font-semibold !text-[#0D171A] max-lg:hidden">
        @switch($step)
            @case(3) {{ __('auth-modal.register.address') }} @break
            @case(4) {{ __('auth-modal.register.pet') }} @break
            @default {{ __('auth-modal.register.personal_info') }}
        @endswitch
    </flux:heading>

    {{-- Indicatore step: 4 segmenti 60x6, gap 24 (Flux non ha un componente steps) --}}
    <div class="mt-5 flex justify-center gap-6 max-lg:mt-9 max-lg:gap-[11px]">
        @for ($i = 1; $i <= 4; $i++)
            <div class="h-1.5 w-[60px] max-lg:h-[3px] max-lg:w-[29px] {{ $i <= $step ? 'bg-brand-cyan' : 'bg-gray-200 max-lg:bg-[#DEDEDE]' }}"></div>
        @endfor
    </div>

    <form wire:submit="next" class="mt-6 max-lg:mt-8">
        @if ($step === 1)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.first_name') }}</flux:label>
                    <flux:input wire:model="form.firstName" placeholder="{{ __('auth-modal.register.first_name') }}" />
                    <flux:error name="form.firstName" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.last_name') }}</flux:label>
                    <flux:input wire:model="form.lastName" placeholder="{{ __('auth-modal.register.last_name') }}" />
                    <flux:error name="form.lastName" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.birth_date') }}</flux:label>
                    <flux:input type="date" wire:model="form.birthDate" />
                    <flux:error name="form.birthDate" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.email') }}</flux:label>
                    <flux:input type="email" wire:model="form.email" placeholder="{{ __('auth-modal.email') }}" />
                    <flux:error name="form.email" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @elseif ($step === 2)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.phone') }}</flux:label>
                    <flux:input type="tel" wire:model="form.phone" placeholder="{{ __('auth-modal.register.phone') }}" />
                    <flux:error name="form.phone" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.password') }}</flux:label>
                    <flux:input type="password" wire:model="form.password" placeholder="{{ __('auth-modal.password') }}" />
                    <flux:error name="form.password" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.repeat_password') }}</flux:label>
                    <flux:input type="password" wire:model="form.passwordConfirmation" placeholder="{{ __('auth-modal.register.repeat_password') }}" />
                    <flux:error name="form.passwordConfirmation" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @elseif ($step === 3)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.address_field') }}</flux:label>
                    <flux:input wire:model="form.address" placeholder="{{ __('auth-modal.register.address_field') }}" />
                    <flux:error name="form.address" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.city') }}</flux:label>
                    <flux:input wire:model="form.city" placeholder="{{ __('auth-modal.register.city') }}" />
                    <flux:error name="form.city" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.postal_code') }}</flux:label>
                    <flux:input wire:model="form.postalCode" placeholder="{{ __('auth-modal.register.postal_code') }}" />
                    <flux:error name="form.postalCode" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @else
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.pet_type') }}</flux:label>
                    <flux:input wire:model="form.petType" placeholder="{{ __('auth-modal.register.pet_type') }}" />
                    <flux:error name="form.petType" class="!mt-1 !text-xs" />
                </flux:field>
            </div>

            <div class="mt-6 space-y-3">
                <flux:field variant="inline">
                    <flux:checkbox wire:model="form.newsletter" class="!size-5 [--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan" />
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.newsletter') }}</flux:label>
                </flux:field>
                <flux:field variant="inline">
                    <flux:checkbox wire:model="form.privacyConsent" class="!size-5 [--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan" />
                    <flux:label class="!text-xs !text-gray-600">{{ __('auth-modal.register.privacy_consent') }}</flux:label>
                    <flux:error name="form.privacyConsent" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @endif

        <div class="mt-8 flex justify-center">
            <flux:button type="submit" class="!rounded-full !bg-brand-cyan !px-8 !text-[15px] !font-bold !text-white hover:!bg-[#4FB9DB] max-lg:w-full">{{ __('auth-modal.register.continue') }}</flux:button>
        </div>
    </form>
</flux:modal>
