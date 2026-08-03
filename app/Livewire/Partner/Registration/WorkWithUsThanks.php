<?php

namespace App\Livewire\Partner\Registration;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkWithUsThanks extends Component
{
    public function render()
    {
        // L'utente loggato è già identificato: può proseguire subito con
        // l'iscrizione B2B, senza aspettare il link firmato dell'email.
        $user = Auth::user();

        return view('livewire.partner.registration.work-with-us-thanks', [
            'canContinueRegistration' => $user !== null
                && ! $user->hasRole('partner')
                && $user->openPartnerApplication() !== null,
        ])->title(__('partner.title_thanks'));
    }
}
