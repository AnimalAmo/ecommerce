<?php

namespace App\Livewire\Auth;

use App\Services\PasswordResetService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modale "Password dimenticata": raccoglie l'email e chiede al broker il link
 * di reimpostazione. Serve entrambe le porte d'ingresso (modale client e
 * modale partner), che la aprono dispatchando 'open-forgot-password'.
 */
class ForgotPasswordModal extends Component
{
    public string $email = '';

    /** Passa allo stato di conferma: il form lascia il posto al "controlla la tua email". */
    public bool $sent = false;

    /** Indirizzo mostrato nella conferma, congelato al momento dell'invio. */
    public string $sentTo = '';

    /**
     * Modale da riaprire chiudendo questa: quella da cui siamo arrivati.
     * Vuota quando l'ingresso non è una login (Profilo → Sicurezza, dove
     * l'utente è già autenticato): lì il pulsante si limita a chiudere.
     */
    public string $origin = 'login';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    /**
     * Aperta dal link "Password dimenticata" delle due modali di login, che
     * passano l'email eventualmente già digitata. L'orchestrazione delle
     * modali sta qui e non nel chiamante: un solo posto sa quale si chiude.
     */
    #[On('open-forgot-password')]
    public function open(string $email = '', string $origin = 'login'): void
    {
        $this->reset('sent', 'sentTo');
        $this->resetValidation();

        $this->email = $email;
        $this->origin = in_array($origin, ['login', 'partner-login'], true) ? $origin : '';

        Flux::modal('login')->close();
        Flux::modal('partner-login')->close();
        Flux::modal('forgot-password')->show();
    }

    public function send(PasswordResetService $service): void
    {
        $this->validate();

        $service->sendResetLink($this->email);

        // Conferma identica per email registrata, sconosciuta o in throttle:
        // il service non ci dice quale caso sia, di proposito.
        $this->sentTo = $this->email;
        $this->sent = true;
    }

    public function backToLogin(): void
    {
        Flux::modal('forgot-password')->close();

        if ($this->origin !== '') {
            Flux::modal($this->origin)->show();
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password-modal');
    }
}
