<?php

namespace App\Livewire\Forms;

use App\Models\Partner\PartnerProfile;
use Livewire\Form;

/**
 * Profilo partner — "Metodo di pagamento". Coordinate per ricevere i pagamenti
 * (su partner_profiles). L'SDI è condiviso con le informazioni personali.
 */
class PartnerPaymentForm extends Form
{
    public string $accountHolder = '';

    public string $iban = '';

    public string $bic = '';

    public function rules(): array
    {
        return [
            'accountHolder' => ['required', 'string', 'max:128'],
            'iban' => ['required', 'string', 'max:34'],
            'bic' => ['required', 'string', 'max:11'],
        ];
    }

    public function setFromProfile(?PartnerProfile $profile): void
    {
        $this->accountHolder = $profile->account_holder ?? '';
        $this->iban = $profile->iban ?? '';
        $this->bic = $profile->bic ?? '';
    }

    public function toProfile(): array
    {
        return [
            'account_holder' => $this->accountHolder,
            'iban' => $this->iban,
            'bic' => $this->bic,
        ];
    }
}
