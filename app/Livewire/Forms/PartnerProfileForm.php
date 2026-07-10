<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Profilo partner — "Informazioni personali". Dati personali (su users) +
 * anagrafica fiscale (su partner_profiles), come da mock XD.
 */
class PartnerProfileForm extends Form
{
    // Personali (users)
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    // Fiscali (partner_profiles)
    public string $businessName = '';

    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $vat = '';

    public string $taxCode = '';

    public string $pec = '';

    public string $sdi = '';

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:64'],
            'lastName' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:128', Rule::unique('users', 'email')->ignore($this->userId())],
            'phone' => ['required', 'string', 'max:32'],
            'businessName' => ['required', 'string', 'max:128'],
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'vat' => ['required', 'string', 'max:13'],
            'taxCode' => ['required', 'string', 'max:16'],
            'pec' => ['required', 'email', 'max:128'],
            'sdi' => ['required', 'string', 'max:7'],
        ];
    }

    public function setFromUser(User $user): void
    {
        $this->firstName = $user->first_name ?? '';
        $this->lastName = $user->last_name ?? '';
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $profile = $user->partnerProfile;
        $this->businessName = $profile->business_name ?? '';
        $this->address = $profile->address ?? '';
        $this->city = $profile->city ?? '';
        $this->province = $profile->province ?? '';
        $this->zip = $profile->zip ?? '';
        $this->vat = $profile->vat ?? '';
        $this->taxCode = $profile->tax_code ?? '';
        $this->pec = $profile->pec ?? '';
        $this->sdi = $profile->sdi ?? '';
    }

    /** Campi personali per l'update di users. */
    public function toUser(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /** Campi fiscali per l'update di partner_profiles. */
    public function toProfile(): array
    {
        return [
            'business_name' => $this->businessName,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            'vat' => $this->vat,
            'tax_code' => $this->taxCode,
            'pec' => $this->pec,
            'sdi' => $this->sdi,
        ];
    }

    private function userId(): ?int
    {
        return auth()->id();
    }
}
