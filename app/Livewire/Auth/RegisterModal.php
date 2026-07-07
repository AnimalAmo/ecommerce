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

class RegisterModal extends Component
{
    use RedirectsAfterAuth;

    public RegisterForm $form;

    public int $step = 1;

    public function next(): void
    {
        if ($this->step < 4) {
            // Solo lo step 1 rivela l'esistenza di un'email (unique:users):
            // throttle per IP per impedire l'enumerazione massiva degli utenti.
            if ($this->step === 1) {
                $this->ensureIsNotRateLimited();
                RateLimiter::hit($this->throttleKey());
            }

            $this->form->validateStep($this->step);

            $this->step++;

            return;
        }

        // Ultimo step: riconvalida tutto (es. email occupata nel frattempo) e
        // riporta il wizard allo step del primo errore.
        try {
            $this->form->validate();
        } catch (ValidationException $e) {
            $this->step = $this->form->firstInvalidStep(array_keys($e->errors()));

            throw $e;
        }

        $user = $this->form->register();

        Auth::login($user);

        $this->finishAuthentication('register');
    }

    /** Blocca l'enumerazione: massimo 10 tentativi di step 1 al minuto per IP. */
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
        if ($this->step > 1) {
            $this->step--;

            return;
        }

        Flux::modal('register')->close();
        Flux::modal('login')->show();
    }

    public function render()
    {
        return view('livewire.auth.register-modal');
    }
}
