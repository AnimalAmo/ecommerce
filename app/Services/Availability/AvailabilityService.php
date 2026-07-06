<?php

namespace App\Services\Availability;

use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\Structure\Structure;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonImmutable;

/**
 * Disponibilità delle prenotazioni: validazione read-only (lo step 3 non
 * consuma capienza — ReserveAvailabilityPipe arriva con gli ordini, step 4)
 * più l'helper closedDates per disabilitare i giorni nei calendari.
 */
class AvailabilityService
{
    /**
     * Verifica che il purchasable sia prenotabile con le options date; le
     * violazioni diventano CartValidationException (toast danger in UI).
     *
     * @throws CartValidationException
     */
    public function ensureAvailable(object $purchasable, array $options): void
    {
        match (BookingPricingService::family($purchasable)) {
            'structure' => $this->ensureStructureAvailable($purchasable, $options),
            'service' => $this->ensureServiceAvailable($purchasable, $options),
            'event', 'activity' => $this->ensureEventAvailable($purchasable, $options),
            // La smartbox è sempre disponibile (nessuna data né capienza).
            'smartbox' => null,
        };
    }

    /**
     * Giorni chiusi ('Y-m-d') della struttura/servizio nel mese indicato, per
     * disabilitare le celle nei calendari (widget detail e modal carrello).
     *
     * @return list<string>
     */
    public function closedDates(Structure $structure, int $year, int $month): array
    {
        $first = CarbonImmutable::create($year, $month, 1);

        return $structure->closures()
            ->whereBetween('date', [$first->toDateString(), $first->endOfMonth()->toDateString()])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date): string => $date->toDateString())
            ->all();
    }

    /** Structure: check-in futuro, intervallo valido, nessuna chiusura in [check_in, check_out). */
    private function ensureStructureAvailable(Structure $structure, array $options): void
    {
        $checkIn = CarbonImmutable::parse($options['check_in']);
        $checkOut = CarbonImmutable::parse($options['check_out']);

        if ($checkIn->lt(CarbonImmutable::today())) {
            throw CartValidationException::pastDate();
        }

        if ($checkOut->lte($checkIn)) {
            throw CartValidationException::invalidRange();
        }

        // La notte del check-out è esclusa: si dorme fino al giorno prima.
        $closed = $structure->closures()
            ->whereBetween('date', [$checkIn->toDateString(), $checkOut->subDay()->toDateString()])
            ->exists();

        if ($closed) {
            throw CartValidationException::unavailableDates();
        }
    }

    /** Service: giorno futuro e non chiuso, orario di fine dopo l'inizio. */
    private function ensureServiceAvailable(Structure $service, array $options): void
    {
        $day = CarbonImmutable::parse($options['day']);

        if ($day->lt(CarbonImmutable::today())) {
            throw CartValidationException::pastDate();
        }

        $closed = $service->closures()
            ->where('date', $day->toDateString())
            ->exists();

        if ($closed) {
            throw CartValidationException::unavailableDay();
        }

        $from = CarbonImmutable::parse($options['day'].' '.$options['time_from']);
        $to = CarbonImmutable::parse($options['day'].' '.$options['time_to']);

        if ($to->lte($from)) {
            throw CartValidationException::invalidTimes();
        }
    }

    /**
     * Event/activity: non ancora iniziato (starts_at null = attività senza data
     * puntuale, nessun vincolo) e persone richieste entro la capienza massima
     * (null = illimitata). Validazione contro il massimo, non contro il venduto.
     */
    private function ensureEventAvailable(Event $event, array $options): void
    {
        if ($event->starts_at !== null && $event->starts_at->isPast()) {
            throw CartValidationException::pastDate();
        }

        $persons = (int) ($options['participants'] ?? array_sum($options['guests'] ?? []));

        if ($event->max_participants !== null && $persons > $event->max_participants) {
            throw CartValidationException::soldOut();
        }
    }
}
