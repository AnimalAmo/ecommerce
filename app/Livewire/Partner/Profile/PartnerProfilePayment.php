<?php

namespace App\Livewire\Partner\Profile;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PaymentConfigurationException;
use App\Exceptions\PaymentModeException;
use App\Livewire\Forms\PartnerPaymentForm;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Payment\StripeConnectService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

class PartnerProfilePayment extends Component
{
    public PartnerPaymentForm $form;

    /** Come il partner riceve i pagamenti: online | on_site (22/09/2026). */
    public string $paymentMode = 'online';

    /** Sito facoltativo dove il cliente paga o prenota, se paga direttamente il partner. */
    public string $paymentUrl = '';

    public function mount(): void
    {
        $profile = Auth::user()->partnerProfile;

        $this->form->setFromProfile($profile);

        $this->paymentMode = $profile?->paymentMode()->value ?? OrderPaymentMode::Online->value;
        $this->paymentUrl = $profile?->payment_url ?? '';

        // Finché il conto non è pienamente operativo, questa pagina rilegge lo
        // stato da Stripe invece di fidarsi dello specchio locale: i flag
        // arrivano da `account.updated`, e se quell'evento non arriva — endpoint
        // non ancora creato, segreto sbagliato, endpoint disabilitato da Stripe
        // dopo troppi 400 — il partner resterebbe "non collegato" per sempre,
        // senza un modo di riallinearsi. A conto operativo la rilettura smette:
        // da lì in poi basta il webhook.
        if ($profile?->stripe_account_id === null || $profile->canBePaid()) {
            return;
        }

        try {
            // Risolto qui e non iniettato nel mount: il bind dello StripeClient
            // pretende le credenziali, e un gateway non configurato non deve
            // impedire al partner di aprire la propria pagina.
            app(StripeConnectService::class)->syncAccountState($profile->stripe_account_id);

            // Il servizio aggiorna un'ALTRA istanza del profilo, letta per
            // stripe_account_id. Senza scaricare la relazione, render()
            // rileggerebbe quella caricata qui sopra e mostrerebbe lo stato di
            // prima: il partner vedrebbe "incompleto" dopo un resync riuscito.
            Auth::user()->unsetRelation('partnerProfile');
        } catch (ApiErrorException|PaymentConfigurationException $exception) {
            // Stripe irraggiungibile (o chiavi assenti) non è un buon motivo
            // per non mostrare la pagina: si resta sull'ultimo stato noto.
            Log::warning('Riallineamento stato Connect fallito', [
                'stripe_account_id' => $profile->stripe_account_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Porta il partner all'onboarding ospitato da Stripe. Torna qui in
     * entrambi i casi (fine o abbandono): è questa pagina a mostrare lo stato
     * aggiornato, che arriva dal webhook account.updated.
     */
    public function connectStripe()
    {
        $url = app(StripeConnectService::class)->onboardingUrl(
            Auth::user(),
            route('partner.profile.payment'),
            route('partner.profile.payment'),
        );

        return $this->redirect($url);
    }

    public function save(): void
    {
        $this->form->validate();

        Auth::user()->partnerProfile()->updateOrCreate([], $this->form->toProfile());

        Flux::toast(text: __('partner.profile.saved'), variant: 'success');
    }

    /**
     * La regola sta nel service: il disabled sulla scelta "online" è grafica,
     * il rifiuto vero è PaymentModeException, mostrata sotto la scelta.
     */
    public function savePaymentMode(PartnerPaymentModeService $modes): void
    {
        $this->validate([
            'paymentMode' => ['required', 'string', Rule::in(array_column(OrderPaymentMode::cases(), 'value'))],
            'paymentUrl' => PartnerPaymentModeService::PAYMENT_URL_RULES,
        ]);

        $user = Auth::user();

        // Partner senza profilo (account demo o precedenti): lo ottiene qui, come fa save().
        $profile = $user->partnerProfile ?? $user->partnerProfile()->create([]);

        try {
            $modes->set($profile, $this->paymentMode === OrderPaymentMode::Online->value, $this->paymentUrl);
        } catch (PaymentModeException $exception) {
            // La radio torna alla modalità salvata: lasciata su "online" (disabilitato)
            // ripeterebbe lo stesso errore anche a chi poi cambia solo il link.
            $this->paymentMode = $profile->paymentMode()->value;
            $this->addError('paymentMode', $exception->getMessage());

            return;
        }

        $user->setRelation('partnerProfile', $profile);

        Flux::toast(text: __('partner.payment_mode.saved'), variant: 'success');
    }

    public function render()
    {
        $profile = Auth::user()->partnerProfile;
        $paysOnSite = $profile !== null && ! $profile->requiresOnlinePayment();

        return view('livewire.partner.profile.payment', [
            'stripeConnected' => $profile?->canBePaid() ?? false,
            'stripeStarted' => $profile?->stripe_account_id !== null,
            'stripeRequirements' => $profile?->stripe_requirements_due ?? [],
            'paysOnSite' => $paysOnSite,
            // Stessa regola con cui il service rifiuta il passaggio: il disabled è solo grafica.
            'onlineLocked' => $profile !== null && ! $profile->canSwitchToOnline(),
        ])
            ->title(__('partner.profile.payment_title'));
    }
}
