<?php

namespace App\Livewire\Partner\Registration;

use App\Models\User;
use App\Services\Partner\RegisterPartnerAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerRegisterStep2 extends Component
{
    /** Tipo di servizio scelto (radio, scelta singola): struttura | attivita | servizi. */
    public string $service = '';

    /**
     * Chiude l'iscrizione B2B con i dati dello step 1 (in sessione):
     *
     *  - visitatore → nasce un account partner nuovo, con login immediato;
     *  - utente ecommerce loggato → il SUO account viene promosso a partner
     *    (resta anche cliente) e la sessione non cambia.
     *
     * In entrambi i casi si atterra in dashboard partner.
     */
    public function createAccount(RegisterPartnerAccount $registrar): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:struttura,attivita,servizi']],
            ['service.required' => __('partner.register2.error_required'), 'service.in' => __('partner.register2.error_required')],
        );

        $step1 = session('partner_registration.step1');

        if ($step1 === null) {
            // Sessione scaduta o step saltato via URL: si riparte dai dati.
            $this->redirectRoute('partner.register');

            return;
        }

        $account = Auth::user();

        // L'email dello step 1 è bloccata sull'account di chi è loggato: una
        // sessione di registrazione iniziata da sloggati non la scavalca.
        if ($account !== null) {
            $step1['email'] = $account->email;
        }

        $existing = User::where('email', $step1['email'])->first();

        // Email di un ALTRO account: non si promuove né si duplica, si accede.
        if ($existing !== null && ! $existing->is($account)) {
            $this->addError('service', __('partner.register2.error_email_taken'));

            return;
        }

        $user = $registrar->register($step1, $existing, session('partner_registration.application_id'));

        session()->forget(['partner_registration.step1', 'partner_registration.application_id']);

        if ($account === null) {
            Auth::login($user);
            session()->regenerate();
        }

        $this->redirectRoute('partner.dashboard');
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step2')
            ->title(__('partner.register.title'));
    }
}
