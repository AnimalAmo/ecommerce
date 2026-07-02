<?php

namespace App\Livewire;

use App\Livewire\Forms\LoginForm;
use Flux\Flux;
use Livewire\Component;

class PartnerLoginModal extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        // TODO: authenticate the partner once the users backend exists.
    }

    public function backToLogin(): void
    {
        Flux::modal('partner-login')->close();
        Flux::modal('login')->show();
    }

    public function render()
    {
        return view('livewire.partner-login-modal');
    }
}
