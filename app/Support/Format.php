<?php

namespace App\Support;

use Carbon\CarbonInterface;
use NumberFormatter;

/**
 * Formatter unico per soldi e date (decisioni ratificate #1 e #2,
 * docs/analisi-entita-dinamiche.md): i valori vivono come integer cents e
 * date native, TUTTI i formati display del design XD si derivano qui.
 * Multilingua: segue app()->getLocale(); i pattern testuali stanno in lang/.
 */
final class Format
{
    /**
     * '215 €' per importi interi, '118,50 €' con decimali, '0,00 €' per zero
     * (il design mostra i decimali solo quando servono, ma lo zero sempre esteso).
     */
    public static function money(int $cents): string
    {
        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);

        if ($cents !== 0 && $cents % 100 === 0) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        }

        return $formatter->formatCurrency($cents / 100, 'EUR');
    }

    /** '17/02/2024' */
    public static function dateShort(CarbonInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    /** '17/02/2024 - 22/02/2024' */
    public static function dateRange(CarbonInterface $from, CarbonInterface $to): string
    {
        return self::dateShort($from).' - '.self::dateShort($to);
    }

    /** '5 Ottobre 2023' (mese maiuscolo come nelle card news XD) */
    public static function dateLong(CarbonInterface $date): string
    {
        return ucwords($date->locale(app()->getLocale())->isoFormat('D MMMM YYYY'));
    }

    /** 'LUN, 30 MAG ALLE 15:30' (riga orario eventi) */
    public static function eventTime(CarbonInterface $dateTime): string
    {
        return __('format.event_time', [
            'date' => mb_strtoupper($dateTime->locale(app()->getLocale())->isoFormat('ddd, D MMM')),
            'time' => $dateTime->format('H:i'),
        ]);
    }
}
