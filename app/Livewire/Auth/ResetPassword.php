<?php

namespace App\Livewire\Auth;

use App\Services\PasswordResetService;
use Flux\Flux;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pagina raggiunta dal link dell'email: sceglie la nuova password e consuma il
 * token. Tre stati nella stessa cornice — form, esito positivo, link scaduto.
 */
class ResetPassword extends Component
{
    /** Token del broker, dalla rotta (mai in un campo modificabile dall'utente). */
    public string $token = '';

    /** Email firmata nel link: mostrata, non modificabile. */
    public string $email = '';

    public string $password = '';

    public string $passwordConfirm = '';

    public bool $done = false;

    /** Link scaduto, già usato o manomesso: il form lascia il posto all'invito a richiederne uno nuovo. */
    public bool $invalid = false;

    /**
     * Link di benvenuto di un partner creato dal pannello ("?welcome=1"):
     * copia "scegli la password", broker a 7 giorni e, alla fine, la login
     * partner. Solo per un partner non amministratore che arriva con un token
     * valido (acceptsWelcome): altrimenti la pagina resta quella di sempre.
     * Locked: dal browser non si allunga la validità di un link normale.
     */
    #[Locked]
    public bool $welcome = false;

    /**
     * $email non è un parametro di rotta: arriva dalla query string del link
     * ("?email="), che è come Laravel firma il destinatario del token. Resta
     * argomento esplicito perché il componente sia montabile anche senza
     * richiesta HTTP (test).
     */
    public function mount(string $token, ?string $email = null, ?bool $welcome = null): void
    {
        $this->token = $token;
        $this->email = $email ?? (string) request()->query('email', '');
        // Il token entra nel controllo: la copia di benvenuto la vede solo chi
        // ha in mano il link della mail (acceptsWelcome).
        $this->welcome = ($welcome ?? request()->boolean('welcome'))
            && app(PasswordResetService::class)->acceptsWelcome($this->email, $this->token);

        // Link troncato o incollato a metà: inutile mostrare il form, la
        // reimpostazione fallirebbe comunque dopo aver scritto la password.
        $this->invalid = $this->email === '';
    }

    protected function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirm' => ['required', 'same:password'],
        ];
    }

    protected function messages(): array
    {
        return [
            'password.required' => __('auth.choose_password'),
        ];
    }

    public function save(PasswordResetService $service): void
    {
        $this->validate();

        $status = $service->reset(
            $this->email,
            $this->token,
            $this->password,
            $this->welcome ? PasswordResetService::WELCOME_BROKER : null,
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Token scaduto/consumato oppure email non più esistente: un solo
            // esito a schermo, e in ogni caso la password non è cambiata.
            $this->invalid = true;
            $this->reset('password', 'passwordConfirm');

            return;
        }

        $this->done = true;
        $this->reset('password', 'passwordConfirm');
    }

    /**
     * Nessun login automatico: la nuova password va usata subito almeno una
     * volta (e per un account partner disattivato il controllo di ruolo deve
     * comunque passare dalla modale dedicata).
     */
    public function goToLogin(): void
    {
        // Un partner appena creato va alla sua login, non a quella dei clienti.
        if ($this->welcome) {
            Flux::modal('partner-login')->show();

            return;
        }

        Flux::modal('login')->show();

        $this->dispatch('prefill-login-email', email: $this->email);
    }

    public function requestNewLink(): void
    {
        $this->dispatch('open-forgot-password', email: $this->email);
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->title(__($this->welcome ? 'auth-modal.partner_welcome.title' : 'auth-modal.reset.title_page'));
    }
}
