<?php

namespace App\Livewire\Forms;

use App\Support\Phone;
use Livewire\Form;

class PartnerApplicationForm extends Form
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $city = '';

    public string $businessName = '';

    public string $role = '';

    public string $offerType = '';

    public string $description = '';

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:64'],
            'lastName' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:128'],
            'phone' => ['required', ...Phone::rules()],
            'website' => ['nullable', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'businessName' => ['required', 'string', 'max:128'],
            'role' => ['required', 'string', 'max:64'],
            'offerType' => ['required', 'string', 'max:64'],
            'description' => ['required', 'string', 'max:1000'],
        ];
    }

    /** Attributi per la riga partner_applications (camelCase → snake_case). */
    public function toApplication(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website ?: null,
            'city' => $this->city,
            'business_name' => $this->businessName,
            'role' => $this->role,
            'offer_type' => $this->offerType,
            'description' => $this->description,
        ];
    }
}
