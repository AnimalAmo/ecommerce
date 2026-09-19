<?php

namespace App\Livewire\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    /** Dopo l'invio la schermata diventa "Controlla la posta". */
    public bool $sent = false;

    public function send(AdminAuthService $auth): void
    {
        $this->validate();

        $auth->sendResetLink($this->email, request()->ip());

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.admin.auth.forgot-password', [
            'expiresIn' => app(AdminAuthService::class)->expiresInMinutes(),
        ])
            ->layout('layouts::admin-guest')
            ->title('Password dimenticata');
    }
}
