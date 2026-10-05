{{--
    "Modifica" dei dati fiscali (UserShow). Facoltativi come in "Nuovo
    partner": un campo svuotato cancella il valore. Campi con `label` come
    prop, così l'errore compare da solo sotto il campo.
--}}
<flux:modal name="fiscal-data" class="w-full max-w-[520px]">
    <h2 class="m-0 text-[19px] font-bold text-admin-rail">{{ __('admin-people.users.fiscal_title') }}</h2>
    <p class="mt-1 mb-0 text-[13.5px] text-gray-400">{{ __('admin-people.users.fiscal_hint') }}</p>

    <form wire:submit="saveFiscalData" class="mt-5 flex flex-col gap-4">
        <flux:input wire:model="vat" :label="__('admin-people.partner_create.fields.vat')" />
        <flux:input wire:model="taxCode" :label="__('admin-people.partner_create.fields.taxCode')" />

        <div class="mt-2 flex flex-wrap justify-end gap-2.5">
            <flux:modal.close>
                <x-admin.button tone="outline">{{ __('admin-people.users.payment_mode_cancel') }}</x-admin.button>
            </flux:modal.close>
            <x-admin.button tone="primary" type="submit">{{ __('admin-people.users.payment_mode_save') }}</x-admin.button>
        </div>
    </form>
</flux:modal>
