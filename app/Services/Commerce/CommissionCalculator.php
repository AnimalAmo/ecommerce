<?php

namespace App\Services\Commerce;

/**
 * Provvigione dovuta ad AnimalAmo su un totale ordine.
 *
 * Restituisce null — non zero — quando non è dovuta: Stripe vuole un
 * application_fee_amount "positive and less than the amount of the charge",
 * quindi sotto soglia il parametro va OMESSO dalla richiesta.
 */
class CommissionCalculator
{
    public function feeCentsFor(int $totalCents, ?int $rateBp = null, ?int $minCents = null): ?int
    {
        $rateBp ??= (int) config('commerce.commission.rate_bp');
        $minCents ??= (int) config('commerce.commission.min_cents');

        if ($totalCents < $minCents) {
            return null;
        }

        // intdiv e non round: l'arrotondamento va sempre a favore del partner.
        $feeCents = intdiv($totalCents * $rateBp, 10_000);

        return $feeCents > 0 ? $feeCents : null;
    }
}
