<?php

namespace App\Livewire\Partner\Profile;

use App\Exceptions\PaymentConfigurationException;
use App\Livewire\Forms\PartnerPaymentForm;
use App\Services\Payment\StripeConnectService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

class PartnerProfilePayment extends Component
{
    public PartnerPaymentForm $form;

    public function mount(): void
    {
        $profile = Auth::user()->partnerProfile;

        $this->form->setFromProfile($profile);

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

    public function render()
    {
        $profile = Auth::user()->partnerProfile;

        return view('livewire.partner.profile.payment', [
            'stripeConnected' => $profile?->canBePaid() ?? false,
            'stripeStarted' => $profile?->stripe_account_id !== null,
            'stripeRequirements' => $profile?->stripe_requirements_due ?? [],
        ])
            ->title(__('partner.profile.payment_title'));
    }
}
