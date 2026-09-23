<?php

namespace App\Services\Payment;

use App\Jobs\PublishAwaitingDrafts;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

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

        $this->ensureManualPayouts($account);

        // Dopo i bonifici manuali, non prima: con la coda sync tutta la
        // pubblicazione gira qui, e non deve ritardare la trattenuta del recesso.
        $this->publishAwaitingDraftsOnPayable($profile);
    }

    /**
     * Pianificazione dei bonifici su `manual`: è la sola leva che regge la
     * trattenuta dei 14 giorni di recesso. Con i direct charges il denaro è del
     * partner dal primo secondo, e un account Standard nasce con la
     * pianificazione automatica: senza questa riga Stripe bonificherebbe alla
     * banca del partner dopo pochi giorni, e `payouts:release` troverebbe il
     * saldo vuoto.
     *
     * Si riasserisce a ogni `account.updated` invece di scriverla una volta
     * sola: se la pianificazione torna automatica, il giro dopo la rimette.
     * Prima di `payouts_enabled` non si prova nemmeno — Stripe rifiuta la
     * modifica su un account che non ha ancora la capability, e l'evento
     * successivo ripassa comunque di qui.
     */
    private function ensureManualPayouts(Account $account): void
    {
        if (($account->payouts_enabled ?? false) !== true) {
            return;
        }

        if (($account->settings->payouts->schedule->interval ?? null) === 'manual') {
            return;
        }

        try {
            $this->client->accounts->update($account->id, [
                'settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]],
            ]);
        } catch (ApiErrorException $exception) {
            // Un rifiuto NON deve risalire: l'endpoint webhook è lo stesso da
            // cui passano gli incassi, e un 400 farebbe riconsegnare l'evento
            // finché Stripe non disabilita l'endpoint. Resta il log, e la
            // trattenuta torna a essere solo contrattuale — visibile.
            Log::warning('Pianificazione dei bonifici non impostata su manuale', [
                'stripe_account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Il collegamento Stripe appena completato sblocca i servizi che il
     * partner aveva chiuso quando non poteva ancora essere pagato (P4). Solo
     * sul passaggio, non a ogni evento: `wasChanged` guarda l'update appena
     * fatto. Mai inline, perché qui passano il webhook account.updated e il
     * mount della pagina pagamento.
     *
     * Il try/catch serve perché con la coda `sync` (i test, o una produzione
     * configurata così) il job gira dentro questa chiamata. Un suo errore
     * arriverebbe a StripeWebhookController, che risponde 400 a qualunque
     * Throwable: Stripe riconsegnerebbe finché non disabilita l'endpoint, lo
     * stesso degli incassi. Una dispatch persa la recupera entro dieci minuti
     * `animalamo:publish-awaiting-drafts`.
     */
    private function publishAwaitingDraftsOnPayable(PartnerProfile $profile): void
    {
        if (! $profile->wasChanged(['stripe_charges_enabled', 'stripe_payouts_enabled']) || ! $profile->canBePaid()) {
            return;
        }

        try {
            PublishAwaitingDrafts::dispatch((int) $profile->user_id);
        } catch (Throwable $exception) {
            Log::warning('Pubblicazione delle bozze in attesa non avviata', [
                'partner_user_id' => $profile->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
