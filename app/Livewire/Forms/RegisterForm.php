<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class RegisterForm extends Form
{
    // Step 1 — informazioni personali
    public string $firstName = '';

    public string $lastName = '';

    public string $birthDate = '';

    public string $email = '';

    // Step 2 — credenziali e contatti
    public string $phone = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    // Step 3 — indirizzo
    public string $address = '';

    public string $city = '';

    public string $postalCode = '';

    // Step 4 — animale domestico e consensi
    public string $petType = '';

    public bool $newsletter = false;

    public bool $privacyConsent = false;
}
