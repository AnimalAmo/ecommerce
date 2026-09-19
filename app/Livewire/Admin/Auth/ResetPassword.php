<?php

namespace App\Livewire\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Url, Locked]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** form | invalid | done: le tre schermate del design. */
    public string $state = 'form';

    public function mount(string $token, AdminAuthService $auth): void
    {
        $this->token = $token;

        if (! $auth->tokenIsValid($this->email, $this->token)) {
            $this->state = 'invalid';
        }
    }

    public function save(AdminAuthService $auth): void
    {
        $this->validate([
            // Copy del design: "Almeno dieci caratteri, con una lettera maiuscola e un numero."
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ], [
            'password.required' => 'Scegli una password.',
            'password.confirmed' => 'Le due password non coincidono.',
            'password.min' => 'Almeno dieci caratteri.',
            'password.mixed' => 'Serve almeno una lettera maiuscola e una minuscola.',
            'password.numbers' => 'Serve almeno un numero.',
        ]);

        $status = $auth->reset($this->email, $this->token, $this->password);

        $this->reset('password', 'password_confirmation');
        $this->state = $status === PasswordBroker::PASSWORD_RESET ? 'done' : 'invalid';
    }

    public function render()
    {
        return view('livewire.admin.auth.reset-password')
            ->layout('layouts::admin-guest')
            ->title('Nuova password');
    }
}
