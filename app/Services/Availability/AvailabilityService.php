<?php

namespace App\Services\Availability;

use App\Enums\OrderPaymentMode;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Services\Partner\PartnerPaymentModeService;
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

    /**
     * Giorni non prenotabili del mese: chiusure, più le notti piene della
     * stanza — queste ultime solo se il partner incassa online (con pagamento
     * in struttura l'occupazione è informativa, non blocca).
     *
     * @return list<string>
     */
    public function unavailableDates(Structure $structure, ?Room $room, int $year, int $month): array
    {
        $dates = $this->closedDates($structure, $year, $month);

        if ($room !== null && $this->isOnline($structure)) {
            $dates = array_merge($dates, app(RoomOccupancy::class)->fullDates($room, $year, $month));
        }

        $dates = array_values(array_unique($dates));
        sort($dates);

        return $dates;
    }

    /**
     * Stanza scelta nelle options: null se la struttura non ha stanze (comportamento
     * storico); altrimenti room_id è obbligatorio e deve appartenere alla struttura.
     * Unica implementazione, riusata dal pricing.
     *
     * @throws CartValidationException
     */
    public function roomFor(Structure $structure, array $options): ?Room
    {
        // Struttura non salvata (calcoli puri senza db): nessuna stanza.
        if (! $structure->exists || ! $structure->rooms()->exists()) {
            return null;
        }

        $roomId = $options['room_id'] ?? null;

        // room_id può arrivare come stringa numerica da Livewire.
        if (! is_numeric($roomId)) {
            throw CartValidationException::notPurchasable();
        }

        $room = $structure->rooms()->whereKey((int) $roomId)->first();

        if ($room === null) {
            throw CartValidationException::notPurchasable();
        }

        return $room;
    }

    private function isOnline(Structure $structure): bool
    {
        // Risolto al momento: il service è scoped (memoizza per richiesta).
        return app(PartnerPaymentModeService::class)->forPurchasable($structure) === OrderPaymentMode::Online;
    }

    /** Structure: check-in futuro, intervallo valido, capienza stanza, nessuna chiusura, stanza libera (partner Online). */
    private function ensureStructureAvailable(Structure $structure, array $options): void
    {
        $room = $this->roomFor($structure, $options);
        $checkIn = CarbonImmutable::parse($options['check_in']);
        $checkOut = CarbonImmutable::parse($options['check_out']);

        if ($room !== null
            && (BookingPricingService::persons($options) > $room->max_guests
                || BookingPricingService::animalCount($options) > $room->max_animals)) {
            throw CartValidationException::invalidParticipants();
        }

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

        if ($room !== null && $this->isOnline($structure) && ! app(RoomOccupancy::class)->isAvailable($room, $checkIn, $checkOut)) {
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
     * puntuale, nessun vincolo) e posti residui sufficienti — i posti già
     * venduti (booked_participants, consumato da ReserveAvailabilityPipe)
     * contano nella capienza massima (null = illimitata).
     */
    private function ensureEventAvailable(Event $event, array $options): void
    {
        if ($event->starts_at !== null && $event->starts_at->isPast()) {
            throw CartValidationException::pastDate();
        }

        // Rifiuta input malevolo dal client (editGuests idratato senza clamp):
        // conteggi negativi o totale nullo — non affidarsi ai soli stepper UI.
        $guests = (array) ($options['guests'] ?? []);

        foreach ($guests as $count) {
            if ((int) $count < 0) {
                throw CartValidationException::invalidParticipants();
            }
        }

        $rawPersons = array_key_exists('participants', $options)
            ? (int) $options['participants']
            : array_sum(array_map('intval', $guests));

        if ($rawPersons < 1) {
            throw CartValidationException::invalidParticipants();
        }

        $persons = BookingPricingService::persons($options);

        if ($event->max_participants !== null && ($event->booked_participants ?? 0) + $persons > $event->max_participants) {
            throw CartValidationException::soldOut();
        }
    }
}
