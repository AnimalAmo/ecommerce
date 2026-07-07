<?php

namespace App\Livewire\Profile;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfileSecurity extends Component
{
    /** Nuova password: campi vuoti, gli asterischi del mock XD sono resi come placeholder. */
    public string $password = '';

    public string $passwordConfirm = '';

    /** Testo segnaposto delle sezioni privacy come da mock XD. */
    public const PRIVACY_PLACEHOLDER = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.';

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
            // "La nuova password", non "la password" del generico lang (login).
            'password.required' => 'Inserisci la nuova password.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $user->update(['password' => $this->password]);

        // Re-login: riallinea la sessione al nuovo hash, l'utente resta autenticato.
        Auth::login($user);

        $this->reset('password', 'passwordConfirm');

        Flux::toast(text: 'Modifiche salvate.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.profile.profile-security', [
            'privacyPlaceholder' => self::PRIVACY_PLACEHOLDER,
        ])->title('Sicurezza — AnimalAmo');
    }
}
