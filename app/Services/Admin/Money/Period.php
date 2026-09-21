<?php

namespace App\Services\Admin\Money;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Il periodo di Incassi (e dei numeri "del mese" della home): un mese di
 * calendario o gli ultimi dodici mesi.
 *
 * I confini sono in ora italiana (`admin.timezone`): un ordine delle 00:30
 * del primo del mese è di quel mese anche se in UTC è ancora il giorno prima.
 * `bounds()` li converte nel fuso del database, e sono sempre istanze Carbon:
 * estremi testuali 'Y-m-d' su SQLite perderebbero l'ultimo giorno.
 */
final class Period
{
    public const LAST_12_MONTHS = '12m';

    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function current(): self
    {
        return self::month(CarbonImmutable::now(self::timezone()));
    }

    public static function month(CarbonInterface $date): self
    {
        $local = CarbonImmutable::instance($date)->setTimezone(self::timezone());

        return new self($local->format('Y-m'), $local->startOfMonth(), $local->endOfMonth());
    }

    public static function lastTwelveMonths(): self
    {
        $now = CarbonImmutable::now(self::timezone());

        return new self(self::LAST_12_MONTHS, $now->subMonthsNoOverflow(11)->startOfMonth(), $now->endOfMonth());
    }

    /**
     * Dalla query string. Un valore sconosciuto o un mese futuro tornano al
     * mese in corso invece di produrre una pagina vuota o un errore.
     */
    public static function fromKey(?string $key): self
    {
        if ($key === self::LAST_12_MONTHS) {
            return self::lastTwelveMonths();
        }

        if ($key !== null && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $key, $match) === 1) {
            $month = CarbonImmutable::create((int) $match[1], (int) $match[2], 1, 0, 0, 0, self::timezone());

            if ($month->lessThanOrEqualTo(CarbonImmutable::now(self::timezone()))) {
                return self::month($month);
            }
        }

        return self::current();
    }

    public static function timezone(): string
    {
        return (string) config('admin.timezone');
    }

    public function isMonth(): bool
    {
        return $this->key !== self::LAST_12_MONTHS;
    }

    /**
     * Estremi nel fuso del database, per whereBetween.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(): array
    {
        $timezone = (string) config('app.timezone');

        return [$this->start->setTimezone($timezone), $this->end->setTimezone($timezone)];
    }

    /** 'Settembre 2026' o 'Ultimi 12 mesi': le voci del selettore. */
    public function label(): string
    {
        return $this->isMonth()
            ? Str::ucfirst($this->start->locale(app()->getLocale())->isoFormat('MMMM YYYY'))
            : __('admin-money.period.last_12_months');
    }

    /** 'settembre': il nome del mese dentro una frase. */
    public function monthName(): string
    {
        return $this->start->locale(app()->getLocale())->isoFormat('MMMM');
    }

    /** '15 set 2026': un giorno salvato in UTC, letto in ora italiana. */
    public static function formatDay(CarbonInterface $date): string
    {
        return CarbonImmutable::instance($date)
            ->setTimezone(self::timezone())
            ->locale(app()->getLocale())
            ->isoFormat('D MMM YYYY');
    }
}
