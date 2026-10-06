<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Forms\RegisterForm;
use Flux\Flux;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Registrazione rapida in un solo passo (cliente, 06/10/2026): nessuna mail
 * da confermare, si entra subito e si atterra nel profilo, dove il resto si
 * completa quando si vuole. Chi si registra dal carrello o dal checkout torna
 * lì: il profilo lo interromperebbe a metà acquisto.
 */
class RegisterModal extends Component
{
    use RedirectsAfterAuth;

    public RegisterForm $form;

    public function register(): void
    {
        // Il modulo rivela l'esistenza di un'email (unique:users): throttle per
        // IP per impedire l'enumerazione massiva degli utenti.
        $this->ensureIsNotRateLimited();
        RateLimiter::hit($this->throttleKey());

        $this->form->validate();

        $user = $this->form->register();

        Auth::login($user);

        session()->regenerate();
        // Letto (e tolto) dal profilo al primo arrivo: il saluto una volta sola.
        session()->put('profile_welcome', true);

        Flux::modal('register')->close();

        $this->redirect($this->afterRegistrationUrl());
    }

    private function afterRegistrationUrl(): string
    {
        $previous = $this->authRedirectUrl();

        foreach (['carrello', 'checkout'] as $route) {
            if (str_starts_with($previous, route($route))) {
                return $previous;
            }
        }

        return route('profilo.anagrafica');
    }

    /** Blocca l'enumerazione: massimo 10 tentativi al minuto per IP. */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 10)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate('register|'.request()->ip());
    }

    public function back(): void
    {
        Flux::modal('register')->close();
        Flux::modal('login')->show();
    }

    public function render()
    {
        return view('livewire.auth.register-modal');
    }
}
