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

    /** '4,5' per i mezzi punti, '3' per i voti interi (card listing e sommario recensioni). */
    public static function rating(float $rating): string
    {
        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 1);

        return $formatter->format($rating);
    }

    /** '25,00' — importo sempre a 2 decimali senza simbolo (card eventi home: 'A partire da € 25,00'). */
    public static function amount(int $cents): string
    {
        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->format($cents / 100);
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

    /** '23 febbraio 2023' (data recensioni, mese minuscolo come da XD) */
    public static function dateSentence(CarbonInterface $date): string
    {
        return $date->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
    }

    /** '8 Gen' (tile data sull'hero del dettaglio evento) */
    public static function dateTile(CarbonInterface $date): string
    {
        return ucwords($date->locale(app()->getLocale())->isoFormat('D MMM'));
    }

    /** 'LUN, 30 MAG ALLE 15:30' / 'OGGI ALLE 13:30' (riga orario griglia eventi) */
    public static function eventTime(CarbonInterface $dateTime): string
    {
        $date = $dateTime->isToday()
            ? __('format.today')
            : $dateTime->locale(app()->getLocale())->isoFormat('ddd, D MMM');

        return __('format.event_time', [
            'date' => mb_strtoupper($date),
            'time' => $dateTime->format('H:i'),
        ]);
    }

    /** 'Oggi alle ore 12:30' / 'Lun, 8 Gen alle ore 19:30' (card eventi home) */
    public static function eventTimeSentence(CarbonInterface $dateTime): string
    {
        $date = $dateTime->isToday()
            ? __('format.today')
            : ucwords($dateTime->locale(app()->getLocale())->isoFormat('ddd, D MMM'));

        return __('format.event_time_sentence', ['date' => $date, 'time' => $dateTime->format('H:i')]);
    }

    /** 'Oggi alle ore 13:30' / 'Lunedì 8 Gennaio alle ore 19:30' (testata dettaglio evento) */
    public static function eventTimeFull(CarbonInterface $dateTime): string
    {
        $date = $dateTime->isToday()
            ? __('format.today')
            : ucwords($dateTime->locale(app()->getLocale())->isoFormat('dddd D MMMM'));

        return __('format.event_time_sentence', ['date' => $date, 'time' => $dateTime->format('H:i')]);
    }

    /** 'Oggi dalle 13:30 alle 16:30' / 'Lunedì 8 Gennaio dalle ore 19:30 alle 21:30' (info generali evento) */
    public static function eventTimeRange(CarbonInterface $from, CarbonInterface $to): string
    {
        if ($from->isToday()) {
            return __('format.event_time_range_today', ['start' => $from->format('H:i'), 'end' => $to->format('H:i')]);
        }

        return __('format.event_time_range', [
            'date' => ucwords($from->locale(app()->getLocale())->isoFormat('dddd D MMMM')),
            'start' => $from->format('H:i'),
            'end' => $to->format('H:i'),
        ]);
    }

    /** '1 anno' / '2 anni' / '6 mesi' ("Valido per" del dettaglio smartbox) */
    public static function validity(int $months): string
    {
        if ($months % 12 === 0) {
            $years = intdiv($months, 12);

            return trans_choice('format.validity_years', $years, ['years' => $years]);
        }

        return trans_choice('format.validity_months', $months, ['months' => $months]);
    }
}
