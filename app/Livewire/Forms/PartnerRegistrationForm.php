<?php

namespace App\Livewire\Forms;

use App\Support\Phone;
use Livewire\Form;

/**
 * Iscrizione B2B — step 1 "Informazioni personali".
 * Dati anagrafici + fiscali del partner (Ragione Sociale, P.IVA, CF).
 *
 * PEC e codice SDI sono stati tolti da qui su richiesta della cliente
 * (18/09/2026): servono a fatturare, non a iscriversi, e allungavano il modulo
 * nel punto in cui il candidato decide se proseguire. Le due colonne restano su
 * partner_profiles (già nullable) e si compilano dal profilo partner.
 */
class PartnerRegistrationForm extends Form
{
    public string $firstName = '';

    public string $lastName = '';

    public string $businessName = '';

    public string $email = '';

    public string $address = '';

    public string $province = '';

    public string $zip = '';

    public string $phone = '';

    public string $vat = '';

    public string $taxCode = '';

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:64'],
            'lastName' => ['required', 'string', 'max:64'],
            'businessName' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:128'],
            'address' => ['required', 'string', 'max:128'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'phone' => ['required', ...Phone::rules()],
            'vat' => ['required', 'string', 'max:13'],
            'taxCode' => ['required', 'string', 'max:16'],
        ];
    }
}
