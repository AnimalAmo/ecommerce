<?php

namespace App\Livewire;

use App\Livewire\Concerns\RedirectsAfterAuth;
use App\Livewire\Forms\RegisterForm;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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
        return view('livewire.register-modal');
    }
}
