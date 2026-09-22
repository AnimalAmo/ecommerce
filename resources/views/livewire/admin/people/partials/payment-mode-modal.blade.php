{{--
    "Cambia" della modalità di pagamento (UserShow). Campi con `label` come
    prop, così l'errore del link compare da solo sotto il campo. Il rifiuto
    "serve Stripe" arriva invece come toast: non è colpa di un campo.
    Le opzioni (partner_create.payment.*) non ripetono i testi dei badge
    della card: la pagina è la stessa.
--}}
<flux:modal name="payment-mode" class="w-full max-w-[520px]">
    <h2 class="m-0 text-[19px] font-bold text-admin-rail">{{ __('admin-people.users.payment_mode_title') }}</h2>

    <form wire:submit="setPaymentMode" class="mt-5 flex flex-col gap-4">
        <flux:radio.group wire:model="paymentMode" :label="__('admin-people.partner_create.fields.paymentMode')">
            <flux:radio value="online" :label="__('admin-people.partner_create.payment.online')" />
            <flux:radio value="on_site" :label="__('admin-people.partner_create.payment.on_site')" />
        </flux:radio.group>

        <flux:input wire:model="paymentUrl" type="url" :label="__('admin-people.partner_create.fields.paymentUrl')" :description="__('admin-people.partner_create.payment.url_help')" />

        <div class="mt-2 flex flex-wrap justify-end gap-2.5">
            <flux:modal.close>
                <x-admin.button tone="outline">{{ __('admin-people.users.payment_mode_cancel') }}</x-admin.button>
            </flux:modal.close>
            <x-admin.button tone="primary" type="submit">{{ __('admin-people.users.payment_mode_save') }}</x-admin.button>
        </div>
    </form>
</flux:modal>
