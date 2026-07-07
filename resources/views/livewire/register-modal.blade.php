{{-- Modale registrazione a step (XD: "Pop-Up - Registrati - primo/secondo/terzo/quarto step") --}}
<flux:modal name="register" :closable="false" class="w-full !max-w-[537px] !rounded-none bg-white !px-8 !py-6 backdrop:!bg-black/30">
    <div class="flex">
        <flux:button variant="ghost" size="xs" wire:click="back" icon="arrow-back" icon:class="!size-3.5" class="!gap-1.5 !px-0 !text-[13px] !font-normal !text-gray-400 hover:!bg-transparent hover:!text-ink">Indietro</flux:button>
    </div>

    <flux:heading level="2" class="mt-8 text-center !text-lg !font-semibold !text-[#0D171A]">
        @switch($step)
            @case(3) Indirizzo @break
            @case(4) Animale domestico @break
            @default Informazioni personali
        @endswitch
    </flux:heading>

    {{-- Indicatore step: 4 segmenti 60x6, gap 24 (Flux non ha un componente steps) --}}
    <div class="mt-5 flex justify-center gap-6">
        @for ($i = 1; $i <= 4; $i++)
            <div class="h-1.5 w-[60px] {{ $i <= $step ? 'bg-brand-cyan' : 'bg-gray-200' }}"></div>
        @endfor
    </div>

    <form wire:submit="next" class="mt-6">
        @if ($step === 1)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Nome</flux:label>
                    <flux:input wire:model="form.firstName" placeholder="Nome" />
                    <flux:error name="form.firstName" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Cognome</flux:label>
                    <flux:input wire:model="form.lastName" placeholder="Cognome" />
                    <flux:error name="form.lastName" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Data di nascita</flux:label>
                    <flux:input type="date" wire:model="form.birthDate" />
                    <flux:error name="form.birthDate" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Email</flux:label>
                    <flux:input type="email" wire:model="form.email" placeholder="Email" />
                    <flux:error name="form.email" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @elseif ($step === 2)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Cellulare</flux:label>
                    <flux:input type="tel" wire:model="form.phone" placeholder="Cellulare" />
                    <flux:error name="form.phone" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Password</flux:label>
                    <flux:input type="password" wire:model="form.password" placeholder="Password" />
                    <flux:error name="form.password" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Ripeti password</flux:label>
                    <flux:input type="password" wire:model="form.passwordConfirmation" placeholder="Ripeti password" />
                    <flux:error name="form.passwordConfirmation" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @elseif ($step === 3)
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Indirizzo</flux:label>
                    <flux:input wire:model="form.address" placeholder="Indirizzo" />
                    <flux:error name="form.address" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Città</flux:label>
                    <flux:input wire:model="form.city" placeholder="Città" />
                    <flux:error name="form.city" class="!mt-1 !text-xs" />
                </flux:field>
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Cap</flux:label>
                    <flux:input wire:model="form.postalCode" placeholder="Cap" />
                    <flux:error name="form.postalCode" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @else
            <div class="space-y-4">
                <flux:field>
                    <flux:label class="!text-xs !text-gray-600">Tipologia animale</flux:label>
                    <flux:input wire:model="form.petType" placeholder="Tipologia animale" />
                    <flux:error name="form.petType" class="!mt-1 !text-xs" />
                </flux:field>
            </div>

            <div class="mt-6 space-y-3">
                <flux:field variant="inline">
                    <flux:checkbox wire:model="form.newsletter" class="!size-5 [--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan" />
                    <flux:label class="!text-xs !text-gray-600">Iscriviti alla newsletter</flux:label>
                </flux:field>
                <flux:field variant="inline">
                    <flux:checkbox wire:model="form.privacyConsent" class="!size-5 [--color-accent:var(--color-brand-cyan)] [--color-accent-foreground:#fff] [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-checkbox-indicator]]:rounded-full [&_[data-flux-checkbox-indicator]]:border-brand-cyan" />
                    <flux:label class="!text-xs !text-gray-600">Acconsento all’uso dei miei dati personali per ricevere promozioni esclusive.</flux:label>
                    <flux:error name="form.privacyConsent" class="!mt-1 !text-xs" />
                </flux:field>
            </div>
        @endif

        <div class="mt-8 flex justify-center">
            <flux:button type="submit" class="!rounded-full !bg-brand-cyan !px-8 !text-[15px] !font-bold !text-white hover:!bg-[#4FB9DB]">Prosegui</flux:button>
        </div>
    </form>
</flux:modal>
