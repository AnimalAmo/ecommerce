<?php

namespace App\Livewire\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount(AdminAuthService $auth): void
    {
        // Già dentro come amministratore: niente form, dritto al pannello.
        if (Auth::user() !== null && $auth->canAccessPanel(Auth::user())) {
            $this->redirectRoute('admin.home', navigate: true);
        }
    }

    public function login(AdminAuthService $auth): void
    {
        $this->validate();

        $auth->attempt($this->email, $this->password, $this->remember, request()->ip());

        session()->regenerate();

        $this->redirectIntended(route('admin.home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts::admin-guest')
            ->title(__('admin.auth.login.title'));
    }
}
