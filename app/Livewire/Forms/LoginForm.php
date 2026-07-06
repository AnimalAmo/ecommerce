<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    public string $email = '';

    public string $password = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Valida e tenta il login, con throttle di 5 tentativi per email+IP.
     * $when (es. controllo ruolo partner) è verificato PRIMA di aprire la
     * sessione: se fallisce, un'eventuale sessione già attiva resta intatta
     * e il messaggio è lo stesso delle credenziali errate.
     */
    public function authenticate(?\Closure $when = null): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $credentials = ['email' => $this->email, 'password' => $this->password];

        if (! Auth::attemptWhen($credentials, $when !== null ? [$when] : [])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
