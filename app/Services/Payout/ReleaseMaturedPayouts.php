<?php

namespace App\Services\Payout;

use App\Enums\PayoutStatus;
use App\Models\OrderPayout\OrderPayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Emette i bonifici maturati verso i partner.
 *
 * Con i direct charges non esistono Transfer: il denaro è già sul saldo del
 * partner, e quello che AnimalAmo controlla è il momento del bonifico verso la
 * sua banca. Si lavora quindi per account, non per ordine: le righe mature di
 * uno stesso partner diventano UN payout e ne condividono l'id.
 */
class ReleaseMaturedPayouts
{
    public function __construct(private readonly StripeClient $client) {}

    /** @return int numero di payout emessi */
    public function run(): int
    {
        $released = 0;

        OrderPayout::query()
            ->with('partner.partnerProfile')
            ->where('status', PayoutStatus::Pending)
            ->whereNotNull('stripe_account_id')
            ->where('release_at', '<=', now())
            ->get()
            ->groupBy('stripe_account_id')
            ->each(function (Collection $rows, string $accountId) use (&$released): void {
                $released += $this->releaseAccount($accountId, $rows) ? 1 : 0;
            });

        return $released;
    }

    private function releaseAccount(string $accountId, Collection $rows): bool
    {
        // Payout bloccati finché l'onboarding non è completo: meglio non
        // chiedere che collezionare errori a ogni giro dello scheduler.
        if ($rows->first()?->partner?->partnerProfile?->canBePaid() !== true) {
            return false;
        }

        $amount = (int) $rows->sum('net_cents');

        if ($amount <= 0) {
            return false;
        }

        $ids = $rows->pluck('id');

        try {
            $payout = $this->client->payouts->create(
                ['amount' => $amount, 'currency' => 'eur'],
                // Idempotency key sul gruppo: un secondo giro nello stesso
                // minuto non paga due volte le stesse righe.
                ['stripe_account' => $accountId, 'idempotency_key' => $this->keyFor($rows)],
            );
        } catch (ApiErrorException $exception) {
            // Il caso reale è il saldo insufficiente (un rimborso partito
            // prima): deve restare visibile, non finire in un catch muto.
            Log::warning('Payout al partner fallito', [
                'stripe_account_id' => $accountId,
                'amount_cents' => $amount,
                'error' => $exception->getMessage(),
            ]);

            $this->recordFailure($ids, $accountId, $exception);

            return false;
        }

        DB::transaction(fn () => OrderPayout::whereIn('id', $ids)->update([
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => $payout->id,
            'released_at' => now(),
        ]));

        return true;
    }

    /**
     * Un fallimento non chiude la riga: la lascia `Pending`, così il giro del
     * giorno dopo riprova da solo. Quasi tutte le cause sono transitorie — il
     * saldo che non copre ancora, un rate limit, un 500 di Stripe — e chiudere
     * al primo errore significherebbe non pagare mai più quel partner senza
     * che nessuno se ne accorga.
     *
     * Esauriti i tentativi la riga diventa `Failed` e serve una mano umana:
     * `payouts:retry` la rimette in coda. Il log è `critical` perché da lì in
     * poi il denaro non si muove più da solo.
     *
     * @param  Collection<int, int>  $ids
     */
    private function recordFailure(Collection $ids, string $accountId, ApiErrorException $exception): void
    {
        OrderPayout::whereIn('id', $ids)->increment('payout_attempts', 1, [
            'failed_at' => now(),
            'last_error' => $exception->getMessage(),
        ]);

        $exhausted = OrderPayout::whereIn('id', $ids)
            ->where('payout_attempts', '>=', (int) config('commerce.payout.max_release_attempts'))
            ->pluck('id');

        if ($exhausted->isEmpty()) {
            return;
        }

        OrderPayout::whereIn('id', $exhausted)->update(['status' => PayoutStatus::Failed]);

        Log::critical('Payout al partner esaurito: nessun altro tentativo automatico', [
            'stripe_account_id' => $accountId,
            'order_payout_ids' => $exhausted->all(),
            'error' => $exception->getMessage(),
        ]);
    }

    /** Stessa chiave per lo stesso gruppo di righe: due giri, un solo payout. */
    private function keyFor(Collection $rows): string
    {
        return 'payout-'.md5($rows->pluck('id')->sort()->implode('-'));
    }
}
