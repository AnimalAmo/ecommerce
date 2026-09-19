{{--
    Conferma di "Cancella su richiesta" (UserIndex e UserShow).
    $anonymizing: User|null; $anonymizeBlock: motivo del blocco o null.
--}}
<flux:modal name="anonymize-user" class="w-full max-w-[460px]">
    @if ($anonymizing !== null)
        <h2 class="m-0 text-[19px] font-bold text-admin-rail">Cancellare questo contatto?</h2>
        <p class="mt-3 text-[14.5px] leading-normal text-gray-600">
            L'indirizzo {{ $anonymizing->email }} e i dati personali di {{ $anonymizing->name }} verranno cancellati:
            profilo, animali, preferiti, carrello, carta salvata e iscrizione alla newsletter.
            I post e le recensioni restano, senza il suo nome.
        </p>
        <p class="mt-3 text-[14.5px] leading-normal text-gray-600">
            Gli ordini restano intatti: vanno conservati per gli obblighi fiscali.
        </p>

        @if ($anonymizeBlock !== null)
            <x-admin.notice tone="warning" class="mt-4">{{ $anonymizeBlock }}</x-admin.notice>
        @endif

        <div class="mt-6 flex flex-wrap justify-end gap-2.5">
            <flux:modal.close>
                <x-admin.button tone="outline">Annulla</x-admin.button>
            </flux:modal.close>
            @if ($anonymizeBlock === null)
                <x-admin.button tone="primary" wire:click="anonymize">Cancella i dati</x-admin.button>
            @endif
        </div>
    @endif
</flux:modal>
