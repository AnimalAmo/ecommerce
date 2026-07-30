<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Forms\LoginForm;
use Flux\Flux;
use Livewire\Attributes\On;
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

    /**
     * L'iscrizione B2B manda qui l'email che risulta già di un altro account:
     * chi accede da lì trova il campo compilato e mette solo la password.
     */
    #[On('prefill-login-email')]
    public function prefillEmail(string $email): void
    {
        $this->form->email = $email;
    }

    /**
     * "Password dimenticata": la modale del reset apre e chiude da sé (sa
     * quale delle due login richiuderà con "Torna al login"); qui passiamo
     * solo l'email eventualmente già digitata, così non va riscritta.
     */
    public function openForgotPassword(): void
    {
        $this->dispatch('open-forgot-password', email: $this->form->email, origin: 'login');
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
        return view('livewire.auth.auth-modal');
    }
}
