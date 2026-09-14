<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Ripartisce fra le righe di un ordine il netto che Stripe ha accreditato.
 *
 * Stessa regola della provvigione: quota proporzionale al lordo, differenza da
 * arrotondamento all'ultima riga. L'invariante che conta è che la somma dei
 * netti faccia **esattamente** il netto accreditato: un centesimo in più e il
 * bonifico chiede più di quanto il saldo contiene.
 */
class PayoutNetSplit
{
    /**
     * @param  Collection<int, object>  $rows  righe con `id` e `gross_cents`
     * @return array<int, int> id riga => netto in centesimi
     */
    public static function across(Collection $rows, int $totalNet, int $totalGross): array
    {
        $split = [];
        $assigned = 0;
        $last = $rows->count() - 1;

        $rows->values()->each(function (object $row, int $index) use (&$split, &$assigned, $last, $totalNet, $totalGross): void {
            $net = $index === $last
                ? $totalNet - $assigned
                : intdiv($totalNet * (int) $row->gross_cents, max($totalGross, 1));

            $assigned += $net;
            $split[(int) $row->id] = $net;
        });

        return $split;
    }
}
