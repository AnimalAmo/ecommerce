<?php

namespace App\Services\Availability;

use App\Enums\OrderStatus;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Occupazione delle stanze: quante unità di una Room sono prenotate in un
 * intervallo. Conta gli ordini in OrderStatus::bookingStatuses() (Paid e
 * Confirmed, qualunque payment_mode) e considera libera la notte del check-out.
 */
class RoomOccupancy
{
    /**
     * Massimo di unità occupate in una qualsiasi notte di [checkIn, checkOut):
     * non la somma delle prenotazioni, perché due soggiorni consecutivi
     * (uno finisce il giorno in cui inizia l'altro) occupano una sola unità.
     */
    public function bookedUnits(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $exceptOrderId = null): int
    {
        $items = $this->overlappingItems($room, $checkIn, $checkOut, $exceptOrderId);

        return $this->peak($items, $checkIn, $checkOut);
    }

    public function isAvailable(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut): bool
    {
        return $this->bookedUnits($room, $checkIn, $checkOut) < $room->units;
    }

    /**
     * Ricontrollo del checkout: si chiama nella transaction dell'ordine, DOPO
     * il lock della stanza. Le righe si leggono con un lock condiviso perché
     * in REPEATABLE READ (MySQL) una SELECT semplice userebbe lo snapshot preso
     * prima di attendere il lock e non vedrebbe l'ordine appena committato dal
     * checkout concorrente. $pending sono i soggiorni sulla stessa stanza già
     * riservati da righe precedenti dello stesso ordine, che a db non ci sono
     * ancora.
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $pending
     */
    public function isAvailableAtCheckout(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, array $pending = []): bool
    {
        $items = $this->overlappingItems($room, $checkIn, $checkOut, lock: true)->concat(array_map(
            fn (array $stay): OrderItem => (new OrderItem)->forceFill(['booked_from' => $stay[0], 'booked_until' => $stay[1]]),
            $pending,
        ));

        return $this->peak($items, $checkIn, $checkOut) < $room->units;
    }

    /**
     * Notti ('Y-m-d') del mese con occupazione >= units, per disabilitare le
     * celle nei calendari. La notte del check-out non è mai inclusa.
     *
     * @return list<string>
     */
    public function fullDates(Room $room, int $year, int $month): array
    {
        $first = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $end = $first->addMonth();

        $items = $this->overlappingItems($room, $first, $end);

        $full = [];

        for ($night = $first; $night->lt($end); $night = $night->addDay()) {
            if ($this->occupiedOn($items, $night) >= $room->units) {
                $full[] = $night->toDateString();
            }
        }

        return $full;
    }

    /** Righe d'ordine valide che si sovrappongono a [checkIn, checkOut). */
    private function overlappingItems(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $exceptOrderId = null, bool $lock = false): Collection
    {
        // Join e non whereHas: con il lock anche le righe orders vanno lette
        // (e bloccate) all'ultima versione committata, non dallo snapshot.
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.room_id', $room->id)
            ->where('order_items.booked_from', '<', $checkOut)
            ->where('order_items.booked_until', '>', $checkIn)
            ->whereIn('orders.status', OrderStatus::bookingStatuses())
            ->when($exceptOrderId, fn ($query) => $query->where('order_items.order_id', '!=', $exceptOrderId))
            ->when($lock, fn ($query) => $query->sharedLock())
            ->get(['order_items.id', 'order_items.booked_from', 'order_items.booked_until']);
    }

    /** Massimo di righe che occupano una stessa notte di [checkIn, checkOut). */
    private function peak(Collection $items, CarbonImmutable $checkIn, CarbonImmutable $checkOut): int
    {
        $max = 0;

        for ($night = $checkIn->startOfDay(); $night->lt($checkOut->startOfDay()); $night = $night->addDay()) {
            $max = max($max, $this->occupiedOn($items, $night));
        }

        return $max;
    }

    /** Righe che occupano la notte indicata: booked_from <= notte < booked_until. */
    private function occupiedOn(Collection $items, CarbonImmutable $night): int
    {
        $day = $night->toDateString();

        return $items->filter(
            fn (OrderItem $item): bool => $item->booked_from->toDateString() <= $day
                && $item->booked_until->toDateString() > $day
        )->count();
    }
}
