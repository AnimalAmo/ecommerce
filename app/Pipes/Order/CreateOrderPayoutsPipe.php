<?php

namespace App\Pipes\Order;

use App\Data\Checkout\OrderPipelineData;
use App\Enums\PayoutStatus;
use App\Models\OrderItem\OrderItem;
use App\Services\Commerce\CommissionCalculator;
use App\Services\Payout\PayoutReleaseSchedule;
use Closure;

/**
 * Scrive il registro dei rilasci dentro la stessa transaction dell'ordine.
 *
 * La provvigione si calcola sul TOTALE dell'ordine (decisione 4: "10% sul
 * totale della prenotazione") e si ripartisce fra le righe in proporzione al
 * lordo, con la differenza da arrotondamento assegnata all'ultima riga: la
 * somma dei netti più la somma delle provvigioni deve fare il totale, sempre.
 */
class CreateOrderPayoutsPipe
{
    public function __construct(
        private readonly CommissionCalculator $commission,
        private readonly PayoutReleaseSchedule $schedule,
    ) {}

    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $order = $data->order;
        $items = $order->items()->get();
        $rateBp = (int) config('commerce.commission.rate_bp');

        $totalCommission = $this->commission->feeCentsFor($order->total_cents) ?? 0;
        $assigned = 0;
        $lastIndex = $items->count() - 1;

        $items->each(function (OrderItem $item, int $index) use ($order, $totalCommission, $rateBp, &$assigned, $lastIndex): void {
            $commissionCents = $index === $lastIndex
                ? $totalCommission - $assigned
                : intdiv($totalCommission * $item->price_cents, max($order->total_cents, 1));

            $assigned += $commissionCents;

            $order->payouts()->create([
                'order_item_id' => $item->id,
                'partner_user_id' => $item->partner_user_id,
                'stripe_account_id' => $item->partner?->partnerProfile?->stripe_account_id,
                'gross_cents' => $item->price_cents,
                'commission_cents' => $commissionCents,
                'net_cents' => $item->price_cents - $commissionCents,
                'commission_rate_bp' => $rateBp,
                'status' => $item->partner_user_id === null ? PayoutStatus::PlatformOnly : PayoutStatus::Pending,
                'release_at' => $this->schedule->releaseAtFor($item),
            ]);
        });

        return $next($data);
    }
}
