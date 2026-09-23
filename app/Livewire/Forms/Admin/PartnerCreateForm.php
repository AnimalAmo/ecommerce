<?php

namespace App\Livewire\Forms\Admin;

use App\Enums\OrderPaymentMode;
use App\Livewire\Forms\PartnerRegistrationForm;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Validation\Rule;

/**
 * "Nuovo partner" dal pannello. Estende il modulo d'iscrizione del sito, così
 * campi e regole restano gli stessi: se la cliente cambia lo step 1, cambia
 * anche qui.
 *
 * Due differenze. La provincia deve esistere: nel pannello la si sceglie da
 * un elenco, e una sigla inventata finirebbe nel profilo (la regione della
 * scheda si ricava da lì). E la modalità di pagamento, che sul sito si
 * sceglie allo step 2. L'email non è `unique`: i casi dell'email già presente
 * li decide PartnerAccountService.
 */
class PartnerCreateForm extends PartnerRegistrationForm
{
    public string $paymentMode = OrderPaymentMode::Online->value;

    public string $paymentUrl = '';

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['province'][] = 'exists:provinces,short_name';

        return [
            ...$rules,
            'paymentMode' => ['required', Rule::enum(OrderPaymentMode::class)],
            'paymentUrl' => PartnerPaymentModeService::PAYMENT_URL_RULES,
        ];
    }

    /**
     * Nomi dei campi nei messaggi di errore ("Il campo Partita IVA…"),
     * forzati in italiano come tutto il pannello.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return collect(array_keys($this->rules()))
            ->mapWithKeys(fn (string $field): array => [$field => __('admin-people.partner_create.fields.'.$field, [], 'it')])
            ->all();
    }
}
