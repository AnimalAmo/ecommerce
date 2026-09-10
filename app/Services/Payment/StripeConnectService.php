<?php

namespace App\Services\Payment;

use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Stripe\StripeClient;

/**
 * Onboarding Connect del partner.
 *
 * Account **Standard**: Stripe raccomanda i direct charges proprio per gli
 * account con dashboard completa, e con Standard la responsabilità di dispute
 * e saldi negativi resta al partner — la decisione della cliente. Non
 * impostiamo `controller.fees.payer`: con i direct charges le commissioni
 * Stripe sono già addebitate all'account connesso, ed è un parametro
 * irreversibile che non va toccato.
 */
class StripeConnectService
{
    public function __construct(private readonly StripeClient $client) {}

    /** Id dell'account connesso del partner, creandolo se non esiste. */
    public function ensureAccountFor(User $partner): string
    {
        $profile = $partner->partnerProfile;

        if ($profile->stripe_account_id !== null) {
            return $profile->stripe_account_id;
        }

        $account = $this->client->accounts->create([
            'type' => 'standard',
            'country' => 'IT',
            'email' => $partner->email,
            'metadata' => ['partner_user_id' => (string) $partner->id],
        ]);

        $profile->update(['stripe_account_id' => $account->id]);

        return $account->id;
    }

    /** Link ospitato da Stripe per completare (o riprendere) l'onboarding. */
    public function onboardingUrl(User $partner, string $returnUrl, string $refreshUrl): string
    {
        return $this->client->accountLinks->create([
            'account' => $this->ensureAccountFor($partner),
            'type' => 'account_onboarding',
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
        ])->url;
    }

    /**
     * Rispecchia su partner_profiles ciò che Stripe dichiara dell'account.
     * La verità sta su Stripe: queste colonne sono uno specchio, non una fonte.
     * Un account che non conosciamo (o il cui profilo è stato cancellato) si
     * ignora: l'endpoint webhook risponde comunque 200.
     */
    public function syncAccountState(string $accountId): void
    {
        $profile = PartnerProfile::query()->firstWhere('stripe_account_id', $accountId);

        if ($profile === null) {
            return;
        }

        $account = $this->client->accounts->retrieve($accountId);

        $profile->update([
            'stripe_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_requirements_due' => $account->requirements->currently_due ?? [],
        ]);
    }
}
