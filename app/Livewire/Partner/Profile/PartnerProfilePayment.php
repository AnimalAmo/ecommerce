<?php

namespace App\Livewire\Partner\Profile;

use App\Livewire\Forms\PartnerPaymentForm;
use App\Services\Payment\StripeConnectService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerProfilePayment extends Component
{
    public PartnerPaymentForm $form;

    public function mount(): void
    {
        $this->form->setFromProfile(Auth::user()->partnerProfile);
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
