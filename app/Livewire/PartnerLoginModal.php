<?php

namespace App\Livewire;

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Forms\LoginForm;
use Flux\Flux;
use Livewire\Component;

class PartnerLoginModal extends Component
{
    use RedirectsAfterAuth;

    public LoginForm $form;

    public function login(): void
    {
        // Ruolo verificato prima di aprire la sessione (attemptWhen): un client già
        // loggato che sbaglia modale non perde la sua sessione, e il messaggio resta
        // quello delle credenziali errate (non riveliamo che l'account esiste).
        $this->form->authenticate(fn ($user): bool => $user->hasRole('partner'));

        $this->finishAuthentication('partner-login');
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
