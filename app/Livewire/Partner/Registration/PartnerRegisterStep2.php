<?php

namespace App\Livewire\Partner\Registration;

use App\Enums\OrderPaymentMode;
use App\Models\User;
use App\Services\Partner\RegisterPartnerAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PartnerRegisterStep2 extends Component
{
    /** Tipo di servizio scelto (radio, scelta singola): struttura | attivita | servizi. */
    public string $service = '';

    /**
     * Come il partner vuole essere pagato: online | on_site (richiesta della
     * cliente, 22/09/2026). Preselezionato online: chi non sceglie resta come
     * i partner di prima. Si cambia poi da Profilo → Metodo di pagamento.
     */
    public string $paymentMode = 'online';

    /**
     * L'account è disattivato: l'iscrizione non si chiude, e si dice al
     * caricamento invece che dopo il click (vedi PartnerRegisterStep1).
     */
    public bool $accountInactive = false;

    /** Tornando qui dopo un conflitto email le scelte sono già fatte: si ritrovano. */
    public function mount(): void
    {
        $user = Auth::user();

        $this->accountInactive = $user !== null && ! $user->is_active;

        $this->service = (string) session('partner_registration.service', '');
        $this->paymentMode = (string) session('partner_registration.payment_mode', OrderPaymentMode::Online->value);
    }

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
            [
                'service' => ['required', 'string', 'in:struttura,attivita,servizi'],
                'paymentMode' => ['required', Rule::enum(OrderPaymentMode::class)],
            ],
            ['service.required' => __('partner.register2.error_required'), 'service.in' => __('partner.register2.error_required')],
        );

        $step1 = session('partner_registration.step1');

        if ($step1 === null) {
            // Sessione scaduta o step saltato via URL: si riparte dai dati.
            $this->redirectRoute('partner.register');

            return;
        }

        $account = Auth::user();

        // Account disattivato: `promote()` non riattiva nessuno e l'area partner
        // risponderebbe 403, quindi l'iscrizione si ferma qui. Dati, tipologia e
        // modalità di pagamento restano in sessione: riattivato l'account si
        // riprende davvero da dove si era arrivati, senza riscegliere nulla.
        if ($account !== null && ! $account->is_active) {
            $this->accountInactive = true;

            session([
                'partner_registration.service' => $this->service,
                'partner_registration.payment_mode' => $this->paymentMode,
            ]);

            return;
        }

        // L'email dello step 1 è bloccata sull'account di chi è loggato: una
        // sessione di registrazione iniziata da sloggati non la scavalca.
        if ($account !== null) {
            $step1['email'] = $account->email;
        }

        $existing = User::where('email', $step1['email'])->first();

        // Email di un ALTRO account: non si promuove né si duplica. L'errore
        // torna sullo step 1, dov'è il campo che lo genera e c'è l'accesso per
        // riprendere con quell'account; tipologia e modalità restano in
        // sessione, così dopo il login non vanno riscelte.
        if ($existing !== null && ! $existing->is($account)) {
            session([
                'partner_registration.email_conflict' => $step1['email'],
                'partner_registration.service' => $this->service,
                'partner_registration.payment_mode' => $this->paymentMode,
            ]);

            $this->redirectRoute('partner.register');

            return;
        }

        $step1['onlinePayment'] = $this->paymentMode === OrderPaymentMode::Online->value;

        $user = $registrar->register($step1, $existing, session('partner_registration.application_id'));

        session()->forget([
            'partner_registration.step1',
            'partner_registration.application_id',
            'partner_registration.service',
            'partner_registration.payment_mode',
            'partner_registration.email_conflict',
        ]);

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
