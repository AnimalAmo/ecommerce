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

        $max = 0;

        for ($night = $checkIn->startOfDay(); $night->lt($checkOut->startOfDay()); $night = $night->addDay()) {
            $max = max($max, $this->occupiedOn($items, $night));
        }

        return $max;
    }

    public function isAvailable(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut): bool
    {
        return $this->bookedUnits($room, $checkIn, $checkOut) < $room->units;
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
    private function overlappingItems(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $exceptOrderId = null): Collection
    {
        return OrderItem::query()
            ->where('room_id', $room->id)
            ->where('booked_from', '<', $checkOut)
            ->where('booked_until', '>', $checkIn)
            ->whereHas('order', fn ($order) => $order->whereIn('status', OrderStatus::bookingStatuses()))
            ->when($exceptOrderId, fn ($query) => $query->where('order_id', '!=', $exceptOrderId))
            ->get(['id', 'booked_from', 'booked_until']);
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
