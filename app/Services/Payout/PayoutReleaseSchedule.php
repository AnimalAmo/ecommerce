<?php

namespace App\Services\Payout;

use App\Models\OrderItem\OrderItem;
use Carbon\CarbonImmutable;

/**
 * Quando il denaro di una riga diventa bonificabile al partner.
 *
 * Due regole, e vince la più lontana nel tempo:
 *  - mai prima dei giorni di recesso (decisione 7 sui cofanetti, applicata a
 *    tutto perché è il minimo che ci siamo dati);
 *  - per le prenotazioni datate, alla scadenza della cancellazione gratuita
 *    (decisione 2), cioè booked_from meno i giorni di policy del prodotto.
 */
class PayoutReleaseSchedule
{
    public function releaseAtFor(OrderItem $item): CarbonImmutable
    {
        $floor = CarbonImmutable::now()
            ->addDays((int) config('commerce.payout.release_delay_days'))
            ->startOfDay();

        if ($item->booked_from === null) {
            return $floor;
        }

        $policyDays = (int) ($item->purchasable?->cancellation_policy_days ?? 0);
        $freeCancellationEnds = CarbonImmutable::parse($item->booked_from)->subDays($policyDays)->startOfDay();

        return $freeCancellationEnds->greaterThan($floor) ? $freeCancellationEnds : $floor;
    }
}
