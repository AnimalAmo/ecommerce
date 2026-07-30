<?php

namespace App\Livewire\Profile;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfileSecurity extends Component
{
    /** Password attuale: richiesta per ri-autenticare prima del cambio (anti-takeover di sessione). */
    public string $currentPassword = '';

    /** Nuova password: campi vuoti, gli asterischi del mock XD sono resi come placeholder. */
    public string $password = '';

    public string $passwordConfirm = '';

    /** Copy sezioni privacy vuota: in attesa del testo della cliente (i paragrafi sono guardati nel blade). */
    public const PRIVACY_PLACEHOLDER = '';

    protected function rules(): array
    {
        return [
            // Ri-autenticazione: senza la password attuale un accesso di sessione
            // rubato non può reimpostare le credenziali (takeover permanente).
            'currentPassword' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8'],
            'passwordConfirm' => ['required', 'same:password'],
        ];
    }

    protected function messages(): array
    {
        return [
            'currentPassword.required' => __('profile.current_password_required'),
            'currentPassword.current_password' => __('profile.current_password_incorrect'),
            // "La nuova password", non "la password" del generico lang (login).
            'password.required' => __('profile.new_password_required'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $user->update(['password' => $this->password]);

        // Invalida le altre sessioni attive (device rubati/condivisi) ruotando
        // l'hash di sessione e riallinea quella corrente al nuovo hash.
        Auth::logoutOtherDevices($this->password);
        Auth::login($user);

        $this->reset('currentPassword', 'password', 'passwordConfirm');

        Flux::toast(text: __('profile.saved'), variant: 'success');
    }

    /**
     * "Reimposta password": chi non ricorda la password attuale non può usare
     * il form qui sopra (richiede la ri-autenticazione), e passa dal link via
     * email come un ospite. L'indirizzo è quello dell'account, già noto.
     */
    public function openForgotPassword(): void
    {
        // Nessuna origin: siamo dentro l'area autenticata, riaprire la modale
        // di login chiudendo questa non avrebbe senso.
        $this->dispatch('open-forgot-password', email: Auth::user()->email, origin: 'none');
    }

    public function render()
    {
        return view('livewire.profile.profile-security', [
            'privacyPlaceholder' => self::PRIVACY_PLACEHOLDER,
        ])->title(__('profile.title_security'));
    }
}
