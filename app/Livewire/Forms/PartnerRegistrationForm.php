<?php

namespace App\Livewire\Forms;

use Livewire\Form;

/**
 * Iscrizione B2B — step 1 "Informazioni personali".
 * Dati anagrafici + fiscali del partner (Ragione Sociale, P.IVA, CF, PEC, SDI).
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

    public string $pec = '';

    public string $sdi = '';

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
            'phone' => ['required', 'string', 'max:32'],
            'vat' => ['required', 'string', 'max:13'],
            'taxCode' => ['required', 'string', 'max:16'],
            'pec' => ['required', 'email', 'max:128'],
            'sdi' => ['required', 'string', 'max:7'],
        ];
    }
}
