<?php

namespace App\Livewire;

use App\Livewire\Forms\RegisterForm;
use Flux\Flux;
use Livewire\Component;

class RegisterModal extends Component
{
    public RegisterForm $form;

    public int $step = 1;

    public function next(): void
    {
        if ($this->step < 4) {
            $this->step++;

            return;
        }

        // TODO: register once the users backend exists.
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
