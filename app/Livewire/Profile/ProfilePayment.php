<?php

namespace App\Livewire\Profile;

use App\Exceptions\PaymentConfigurationException;
use App\Services\Payment\SavedPaymentMethodService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Carta salvata dell'utente (XD "Profilo – dati pagamento"). Il numero carta
 * NON passa da qui: il titolare è un nostro campo, numero/scadenza/cvv vivono
 * nel Payment Element di Stripe in modalità setup. Il SetupIntent confermato
 * dal browser è sempre riverificato server-side prima di salvare.
 */
class ProfilePayment extends Component
{
    /** Titolare della carta: campo nostro, inoltrato a Stripe come billing_details.name. */
    public string $cardHolder = '';

    /** True = form di inserimento carta montato (sempre, se non c'è una carta salvata). */
    public bool $editing = false;

    /** "Salva" premuto: la conferma è in corso lato Stripe.js. */
    public bool $saving = false;

    /** L'element è montato: prima di allora "Salva" non ha nulla da confermare. */
    public bool $elementReady = false;

    /** Chiavi mancanti o API KO: box informativo al posto dell'element. */
    public bool $paymentUnavailable = false;

    public ?string $clientSecret = null;

    /** Id del SetupIntent aperto: il payload del client deve combaciare. */
    #[Locked]
    public ?string $setupIntentId = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->cardHolder = $user->card_holder ?? $user->name;

        // Nessuna carta salvata: la pagina è direttamente il form del mock XD.
        if (! $user->hasSavedCard()) {
            $this->openSetup();
        }
    }

    /** Passa dalla carta salvata al form di inserimento (nuovo SetupIntent). */
    public function openSetup(): void
    {
        $this->editing = true;
        $this->initSetupSession();
    }

    /** Torna alla carta salvata annullando il SetupIntent aperto. */
    public function cancelSetup(): void
    {
        $user = Auth::user();

        if (! $user->hasSavedCard()) {
            return;
        }

        $this->reset('editing', 'saving', 'elementReady', 'clientSecret', 'setupIntentId');
        $this->resetValidation();

        $this->cardHolder = $user->card_holder ?? $user->name;
    }

    /**
     * "Salva": valida il titolare e passa la palla a Stripe.js, che conferma
     * il SetupIntent e richiama onSetupSucceeded/onSetupFailed.
     */
    public function save(): void
    {
        $this->validate(['cardHolder' => ['required', 'string', 'max:255']]);

        if ($this->paymentUnavailable || $this->clientSecret === null || ! $this->elementReady || $this->saving) {
            return;
        }

        $this->saving = true;

        $this->dispatch('confirm-setup', name: $this->cardHolder);
    }

    /** Element montato: "Salva" può confermare. */
    public function markElementReady(): void
    {
        $this->elementReady = true;
    }

    /** Stripe.js non è partito (rete/adblock): meglio un box che un bottone morto. */
    public function reportSetupInitFailed(): void
    {
        $this->paymentUnavailable = true;
        $this->clientSecret = null;
        $this->setupIntentId = null;
    }

    /** Carta confermata dal browser: riverifica server-side e persistenza. */
    public function onSetupSucceeded(array $payload): void
    {
        $this->saving = false;

        // Id diverso da quello della sessione aperta: replay via devtools.
        if ($this->setupIntentId === null || ($payload['setup_intent_id'] ?? null) !== $this->setupIntentId) {
            Flux::toast(text: __('profile.payment_card_error'), variant: 'danger');

            return;
        }

        try {
            $saved = app(SavedPaymentMethodService::class)->saveFromSetupIntent(Auth::user(), $this->setupIntentId);
        } catch (PaymentConfigurationException) {
            $this->paymentUnavailable = true;
            Flux::toast(text: __('payment.errors.config_missing'), variant: 'danger');

            return;
        }

        if (! $saved) {
            Flux::toast(text: __('profile.payment_card_error'), variant: 'danger');

            return;
        }

        $this->reset('editing', 'elementReady', 'clientSecret', 'setupIntentId');

        $user = Auth::user()->refresh();
        $this->cardHolder = $user->card_holder ?? $user->name;

        Flux::toast(text: __('profile.payment_card_saved'), variant: 'success');
    }

    /** Conferma rifiutata da Stripe (carta rifiutata, campi incompleti). */
    public function onSetupFailed(string $message = ''): void
    {
        $this->saving = false;

        Flux::toast(text: $message !== '' ? $message : __('profile.payment_card_error'), variant: 'danger');
    }

    /** Elimina la carta salvata e riapre il form vuoto. */
    public function remove(): void
    {
        try {
            app(SavedPaymentMethodService::class)->forget(Auth::user());
        } catch (PaymentConfigurationException) {
            $this->paymentUnavailable = true;
            Flux::toast(text: __('payment.errors.config_missing'), variant: 'danger');

            return;
        }

        Flux::toast(text: __('profile.payment_card_removed'), variant: 'success');

        $this->cardHolder = Auth::user()->name;
        $this->openSetup();
    }

    /**
     * Apre il SetupIntent del form. Config mancante o API KO: box informativo
     * al posto dell'element — la pagina profilo non crasha per Stripe.
     */
    private function initSetupSession(): void
    {
        $this->reset('saving', 'elementReady', 'clientSecret', 'setupIntentId');
        $this->paymentUnavailable = false;

        try {
            $session = app(SavedPaymentMethodService::class)->createSetupIntent(Auth::user());

            $this->clientSecret = $session['client_secret'];
            $this->setupIntentId = $session['setup_intent_id'];
        } catch (PaymentConfigurationException) {
            $this->paymentUnavailable = true;
        } catch (Throwable $exception) {
            Log::warning('Profilo: apertura del SetupIntent fallita', [
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            $this->paymentUnavailable = true;
        }
    }

    public function render()
    {
        return view('livewire.profile.profile-payment', [
            'user' => Auth::user(),
            'stripeKey' => (string) config('payment.stripe.key'),
            // Il mock non prevede il campo paese: l'element non lo chiede e
            // lo dichiariamo noi (marketplace italiano).
            'billingCountry' => 'IT',
        ])->title(__('profile.title_payment'));
    }
}
