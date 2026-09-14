<?php

namespace App\Services\Payout;

use App\Enums\PayoutStatus;
use App\Models\OrderPayout\OrderPayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\ApiConnectionException;
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

        $this->warnAboutProvisionalNets();

        OrderPayout::query()
            ->with('partner.partnerProfile')
            ->where('status', PayoutStatus::Pending)
            ->whereNotNull('stripe_account_id')
            ->where('release_at', '<=', now())
            // Il netto provvisorio (lordo meno provvigione) è più alto di
            // quanto il saldo contiene: bonificarlo sarebbe un
            // balance_insufficient annunciato. Si aspetta la riconciliazione.
            ->whereNotNull('net_reconciled_at')
            ->get()
            ->groupBy('stripe_account_id')
            ->each(function (Collection $rows, string $accountId) use (&$released): void {
                foreach ($this->attemptGroups($rows) as $group) {
                    $released += $this->releaseAccount($accountId, $group) ? 1 : 0;
                }
            });

        return $released;
    }

    /**
     * Righe mature che aspettano ancora il netto vero. Non è un errore — il
     * giro di `payouts:reconcile-net` è alle 05:45 e questo alle 06:00 — ma se
     * il numero non scende, la riconciliazione non sta girando e nessuno
     * verrebbe pagato, in silenzio.
     */
    private function warnAboutProvisionalNets(): void
    {
        $waiting = OrderPayout::query()
            ->where('status', PayoutStatus::Pending)
            ->whereNotNull('stripe_account_id')
            ->where('release_at', '<=', now())
            ->whereNull('net_reconciled_at')
            ->count();

        if ($waiting > 0) {
            Log::warning('Righe mature col netto ancora provvisorio: non verranno bonificate', [
                'righe' => $waiting,
            ]);
        }
    }

    /**
     * Gruppi di tentativo. Le righe già tentate restano insieme sotto la loro
     * chiave — stesso gruppo, stesso importo, stessa chiave, quindi Stripe
     * riconosce il doppione — e le righe mai tentate formano un gruppo nuovo.
     * Fonderle sarebbe il difetto: una riga maturata nel frattempo cambierebbe
     * la chiave del gruppo già tentato, e il bonifico ripartirebbe da zero.
     *
     * @param  Collection<int, OrderPayout>  $rows
     * @return list<Collection<int, OrderPayout>>
     */
    private function attemptGroups(Collection $rows): array
    {
        [$tentate, $nuove] = $rows->partition(
            fn (OrderPayout $row): bool => $row->payout_idempotency_key !== null,
        );

        $groups = $tentate->groupBy('payout_idempotency_key')->values()->all();

        if ($nuove->isNotEmpty()) {
            $groups[] = $nuove;
        }

        return $groups;
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
        // Scritta PRIMA della chiamata: se il processo muore a metà, il
        // tentativo successivo trova la chiave e non ne inventa una nuova.
        $key = $this->keyFor($rows);

        try {
            $payout = $this->client->payouts->create(
                ['amount' => $amount, 'currency' => 'eur'],
                ['stripe_account' => $accountId, 'idempotency_key' => $key],
            );
        } catch (ApiErrorException $exception) {
            // Il caso reale è il saldo insufficiente (un rimborso partito
            // prima): deve restare visibile, non finire in un catch muto.
            Log::warning('Payout al partner fallito', [
                'stripe_account_id' => $accountId,
                'amount_cents' => $amount,
                'error' => $exception->getMessage(),
            ]);

            $this->recordFailure($ids, $accountId, $key, $exception);

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
    private function recordFailure(Collection $ids, string $accountId, string $key, ApiErrorException $exception): void
    {
        if ($this->outcomeIsUnknown($exception)) {
            OrderPayout::whereIn('id', $ids)->update([
                'status' => PayoutStatus::Failed,
                'failed_at' => now(),
                'last_error' => 'ESITO IGNOTO: '.$exception->getMessage(),
            ]);

            Log::critical('Payout dall\'esito ignoto: verificare su Stripe prima di ritentare', [
                'stripe_account_id' => $accountId,
                'idempotency_key' => $key,
                'order_payout_ids' => $ids->all(),
                'error' => $exception->getMessage(),
            ]);

            return;
        }

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

    /**
     * Un errore di connessione non dice "non è successo": dice "non so". Il
     * bonifico può essere stato creato e la risposta persa per strada, e lo
     * stesso vale per un 5xx. Ritentare alla cieca significherebbe pagare due
     * volte, quindi la riga si ferma e aspetta un occhio umano.
     */
    private function outcomeIsUnknown(ApiErrorException $exception): bool
    {
        return $exception instanceof ApiConnectionException
            || ($exception->getHttpStatus() ?? 0) >= 500;
    }

    /**
     * La chiave del gruppo, generata e salvata al primo tentativo e riusata a
     * ogni ritentativo: se il bonifico era già passato, Stripe restituisce
     * quello invece di crearne un altro.
     */
    private function keyFor(Collection $rows): string
    {
        $existing = $rows->first()?->payout_idempotency_key;

        if ($existing !== null) {
            return $existing;
        }

        $key = 'payout-'.(string) Str::uuid();

        OrderPayout::whereIn('id', $rows->pluck('id'))->update(['payout_idempotency_key' => $key]);

        return $key;
    }
}
