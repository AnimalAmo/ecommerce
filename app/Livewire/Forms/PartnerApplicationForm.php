<?php

namespace App\Livewire\Forms;

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
}
