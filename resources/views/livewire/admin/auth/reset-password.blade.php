<div>
    @if ($state === 'invalid')
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-magenta/15">
                <flux:icon.exclamation-triangle class="size-[27px] text-[#C2186F]" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Link non più valido</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Il link è scaduto o è già stato usato. Richiedine uno nuovo e riprova.</p>
            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.password.request')" wire:navigate class="!px-[34px]">Chiedi un nuovo link</x-admin.button>
            </div>
        </div>
    @elseif ($state === 'done')
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-cyan">
                <flux:icon.check class="size-[27px] text-white" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Password aggiornata</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Puoi accedere al pannello con la nuova password.</p>
            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.login')" wire:navigate class="!px-[34px]">Vai al pannello</x-admin.button>
            </div>
        </div>
    @else
        <div class="text-center">
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Scegli una nuova password</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Stai reimpostando la password di {{ $email }}.</p>
        </div>

        <form wire:submit="save" class="mt-7 flex flex-col gap-4">
            <flux:input type="password" label="Nuova password" wire:model="password" autocomplete="new-password" viewable />
            <flux:input type="password" label="Ripeti la password" wire:model="password_confirmation" autocomplete="new-password" viewable />
            <p class="m-0 text-[12.5px] leading-normal text-gray-400">Almeno dieci caratteri, con una lettera maiuscola e un numero.</p>

            <div class="mt-4 flex justify-center">
                <x-admin.button tone="primary" type="submit" class="!px-[34px]">Salva la password</x-admin.button>
            </div>
        </form>
    @endif
</div>
