{{--
    Conferma di "Cancella su richiesta" (UserIndex e UserShow).
    $anonymizing: User|null; $anonymizeBlock: motivo del blocco o null.
--}}
<flux:modal name="anonymize-user" class="w-full max-w-[460px]">
    @if ($anonymizing !== null)
        <h2 class="m-0 text-[19px] font-bold text-admin-rail">{{ __('admin-people.anonymize.title') }}</h2>
        <p class="mt-3 text-[14.5px] leading-normal text-gray-600">
            {{ __('admin-people.anonymize.body', ['email' => $anonymizing->email, 'name' => $anonymizing->name]) }}
        </p>
        <p class="mt-3 text-[14.5px] leading-normal text-gray-600">{{ __('admin-people.anonymize.orders_kept') }}</p>

        @if ($anonymizeBlock !== null)
            <x-admin.notice tone="warning" class="mt-4">{{ $anonymizeBlock }}</x-admin.notice>
        @endif

        <div class="mt-6 flex flex-wrap justify-end gap-2.5">
            <flux:modal.close>
                <x-admin.button tone="outline">{{ __('admin-people.anonymize.cancel') }}</x-admin.button>
            </flux:modal.close>
            @if ($anonymizeBlock === null)
                <x-admin.button tone="primary" wire:click="anonymize">{{ __('admin-people.anonymize.confirm') }}</x-admin.button>
            @endif
        </div>
    @endif
</flux:modal>
