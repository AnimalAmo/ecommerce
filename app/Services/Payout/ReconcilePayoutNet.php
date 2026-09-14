<?php

namespace App\Services\Payout;

use App\Enums\PayoutStatus;
use App\Models\Order\Order;
use App\Models\OrderPayout\OrderPayout;
use App\Support\PayoutNetSplit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Rilegge da Stripe il netto davvero accreditato e lo scrive nel registro.
 *
 * Serve perché al capture la balance transaction non esiste ancora: l'addebito
 * è già lì, il suo `balance_transaction` è null, e le righe nascono con lordo
 * meno provvigione — cioè qualche centesimo più di quanto il saldo del partner
 * conterrà, che al giorno 14 diventa un `balance_insufficient`. Qui il numero
 * si corregge, quando Stripe l'ha calcolato e prima che il bonifico parta.
 */
class ReconcilePayoutNet
{
    public function __construct(private readonly StripeClient $client) {}

    /** @return int righe riconciliate */
    public function run(): int
    {
        $reconciled = 0;

        OrderPayout::query()
            ->where('status', PayoutStatus::Pending)
            ->whereNull('net_reconciled_at')
            ->whereNotNull('stripe_account_id')
            ->with('order.payment')
            ->get()
            ->groupBy('order_id')
            ->each(function ($rows) use (&$reconciled): void {
                $reconciled += $this->reconcileOrder($rows->first()->order, $rows) ? $rows->count() : 0;
            });

        return $reconciled;
    }

    /** @param  Collection<int, OrderPayout>  $rows */
    private function reconcileOrder(?Order $order, $rows): bool
    {
        $intentId = $order?->payment?->transaction_id;
        $accountId = $rows->first()->stripe_account_id;

        if ($intentId === null || $accountId === null) {
            return false;
        }

        $net = $this->netFor($intentId, $accountId);

        if ($net === null) {
            // Stripe non l'ha ancora calcolata: si riprova al giro dopo,
            // senza toccare il provvisorio.
            return false;
        }

        $split = PayoutNetSplit::across($rows, $net, (int) $rows->sum('gross_cents'));

        DB::transaction(function () use ($split): void {
            foreach ($split as $id => $netCents) {
                OrderPayout::whereKey($id)->update([
                    'net_cents' => $netCents,
                    'net_reconciled_at' => now(),
                ]);
            }
        });

        return true;
    }

    private function netFor(string $intentId, string $accountId): ?int
    {
        try {
            $intent = $this->client->paymentIntents->retrieve(
                $intentId,
                ['expand' => ['latest_charge.balance_transaction']],
                ['stripe_account' => $accountId],
            );
        } catch (ApiErrorException $exception) {
            Log::warning('Riconciliazione del netto: retrieve fallita', [
                'payment_intent_id' => $intentId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $net = $intent->latest_charge->balance_transaction->net ?? null;

        return $net === null ? null : (int) $net;
    }
}
