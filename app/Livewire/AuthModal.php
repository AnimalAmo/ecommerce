<?php

namespace App\Livewire;

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Forms\LoginForm;
use Flux\Flux;
use Livewire\Component;

class AuthModal extends Component
{
    use RedirectsAfterAuth;

    public LoginForm $form;

    public function login(): void
    {
        $this->form->authenticate();

        $this->finishAuthentication('login');
    }

    public function openRegister(): void
    {
        Flux::modal('login')->close();
        Flux::modal('register')->show();
    }

    public function openPartnerLogin(): void
    {
        Flux::modal('login')->close();
        Flux::modal('partner-login')->show();
    }

    public function render()
    {
        return view('livewire.auth-modal');
    }
}
