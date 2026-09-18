<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 11 "Pagamento". Coordinate bancarie per gli accrediti.
 */
class HotelPaymentForm extends Form
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

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->accountHolder = $draft->account_holder ?? '';
        $this->iban = $draft->iban ?? '';
        $this->bic = $draft->bic ?? '';
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'account_holder' => $this->accountHolder,
            'iban' => $this->iban,
            'bic' => $this->bic,
        ];
    }
}
