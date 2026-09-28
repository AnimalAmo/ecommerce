<?php

namespace App\Livewire\Forms;

use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 11 "Pagamento". Coordinate bancarie per gli accrediti.
 *
 * Sono le stesse di Profilo → "Metodo di pagamento" (PartnerPaymentForm), e il
 * profilo è la fonte. Difetto W7 dell'audit dei flussi (28/09/2026): lo step le
 * scriveva solo sulla bozza, il profilo restava vuoto e il partner le doveva
 * digitare una seconda volta. Ora le scrive anche sul profilo (toProfile) e si
 * precompila da lì (setFrom). Sulla bozza restano perché il dettaglio del
 * servizio le mostra. Nessuno dei due punti le usa per pagare: i bonifici
 * passano da Stripe Connect.
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

    /**
     * Precompila dal profilo. Dalla bozza solo se il profilo non ha ancora
     * coordinate: è il servizio di chi le ha date al wizard prima di questa
     * correzione, e le ritrova invece di riscriverle.
     */
    public function setFrom(StructureDraft $draft, ?PartnerProfile $profile): void
    {
        $source = filled($profile?->account_holder) || filled($profile?->iban) || filled($profile?->bic)
            ? $profile
            : $draft;

        $this->accountHolder = $source->account_holder ?? '';
        $this->iban = $source->iban ?? '';
        $this->bic = $source->bic ?? '';
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

    /** Le stesse coordinate per `partner_profiles`, come PartnerPaymentForm::toProfile(). */
    public function toProfile(): array
    {
        return $this->toDraft();
    }
}
