<div>
    <a href="{{ route('admin.login') }}" wire:navigate class="mb-[22px] inline-flex items-center gap-1.5 text-[13px] text-gray-400 hover:text-admin-teal">
        <flux:icon.chevron-left class="size-3.5" />Indietro
    </a>

    @if ($sent)
        <div class="text-center">
            <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-brand-cyan/15">
                <flux:icon.envelope class="size-[27px] text-admin-teal" />
            </div>
            <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Controlla la posta</h1>
            <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Se {{ $email }} è un account del pannello, il link per reimpostare la password è in arrivo.</p>

            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" :href="route('admin.login')" wire:navigate class="!px-[34px]">Torna all'accesso</x-admin.button>
            </div>

            <p class="mt-[26px] text-[13px] leading-normal text-gray-600">Il link vale {{ $expiresIn }} minuti. Se non lo trovi, guarda nella cartella dello spam.</p>
        </div>
    @else
        <h1 class="m-0 text-[19px] leading-snug font-bold text-admin-rail">Password dimenticata</h1>
        <p class="mt-2.5 text-[14.5px] leading-normal text-gray-600">Inserisci l'indirizzo email del tuo account: ti mandiamo un link per scegliere una nuova password.</p>

        <form wire:submit="send" class="mt-7">
            <flux:input type="email" label="Email" wire:model="email" placeholder="Email" autocomplete="username" autofocus />

            <div class="mt-8 flex justify-center">
                <x-admin.button tone="primary" type="submit" class="!px-[34px]">Invia il link</x-admin.button>
            </div>
        </form>
    @endif
</div>
