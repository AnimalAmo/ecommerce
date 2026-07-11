<?php

namespace App\Livewire\Partner\Registration;

use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class PartnerRegisterStep2 extends Component
{
    /** Tipo di servizio scelto (radio, scelta singola): struttura | attivita | servizi. */
    public string $service = '';

    /**
     * Crea l'account partner reale dai dati dello step 1 (in sessione):
     * utente attivo con ruolo partner + profilo fiscale, login immediato e
     * atterraggio in dashboard. Password casuale: il mockup non prevede il
     * campo — l'accesso post-logout arriverà col flusso "imposta password"
     * (TODO, non ancora disegnato).
     */
    public function createAccount(): void
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

        if (User::where('email', $step1['email'])->exists()) {
            $this->addError('service', __('partner.register2.error_email_taken'));

            return;
        }

        $user = DB::transaction(function () use ($step1): User {
            $user = User::create([
                'first_name' => $step1['firstName'],
                'last_name' => $step1['lastName'],
                'email' => $step1['email'],
                'phone' => $step1['phone'],
                'password' => Str::password(32),
                'is_active' => true,
            ]);
            $user->syncRoles(['partner']);

            $user->partnerProfile()->create([
                'business_name' => $step1['businessName'],
                'vat' => $step1['vat'],
                'tax_code' => $step1['taxCode'],
                'pec' => $step1['pec'],
                'sdi' => $step1['sdi'],
                'address' => $step1['address'],
                'province' => $step1['province'],
                'zip' => $step1['zip'],
            ]);

            if ($applicationId = session('partner_registration.application_id')) {
                PartnerApplication::whereKey($applicationId)->update([
                    'status' => PartnerApplication::STATUS_REGISTERED,
                    'registered_at' => now(),
                ]);
            }

            return $user;
        });

        session()->forget(['partner_registration.step1', 'partner_registration.application_id']);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('partner.dashboard');
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step2')
            ->title(__('partner.register.title'));
    }
}
