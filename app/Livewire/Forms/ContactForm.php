<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class ContactForm extends Form
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $reason = '';

    public string $message = '';

    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:64'],
            'lastName' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:128'],
            'reason' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /** Attributi per la riga contact_messages (camelCase → snake_case). */
    public function toMessage(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'reason' => $this->reason,
            'message' => $this->message,
        ];
    }
}
