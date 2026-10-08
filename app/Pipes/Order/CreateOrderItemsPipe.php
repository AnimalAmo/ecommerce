<?php

namespace App\Pipes\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\OrderPipelineData;
use App\Models\Structure\Room;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Crea le righe ordine come snapshot autonomo dal catalogo (title/photo/tipo/
 * località/prezzo/options del CartItemData) più la finestra prenotata
 * booked_from/booked_until calcolata qui per famiglia — è ciò che permette al
 * profilo ordini di bucketizzare programma/passati senza toccare il prodotto.
 * Stanza: room_id per l'occupazione e room_name nelle options come snapshot,
 * leggibile anche quando il partner cancella la stanza (room_id → null).
 * Il nome si scrive nella lingua di default (quella del pannello partner e
 * delle sue mail), non in quella di chi compra.
 */
class CreateOrderItemsPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        foreach ($data->input->items as $item) {
            [$bookedFrom, $bookedUntil] = $this->bookedWindow($item);
            $room = $this->room($item);

            $data->orderItems->push($data->order->items()->create([
                'purchasable_type' => $item->type,
                'purchasable_id' => $item->purchasableId,
                'partner_user_id' => $item->partnerUserId,
                'title' => $item->title,
                'photo_url' => $item->photoUrl,
                'product_type' => $item->productType,
                'location' => $item->location,
                'price_cents' => $item->priceCents,
                'is_gift' => $item->isGift,
                'options' => $room !== null ? [...$item->options, 'room_name' => $room->displayName(LaravelLocalization::getDefaultLocale())] : $item->options,
                'room_id' => $room?->id,
                'booked_from' => $bookedFrom,
                'booked_until' => $bookedUntil,
            ]));
        }

        return $next($data);
    }

    /** Stanza della riga: esiste ed è della struttura, l'ha già verificato (e lockato) ReserveAvailabilityPipe. */
    private function room(CartItemData $item): ?Room
    {
        $roomId = $item->options['room_id'] ?? null;

        if ($item->type !== 'structure' || ! is_numeric($roomId)) {
            return null;
        }

        return Room::query()->whereKey((int) $roomId)->where('structure_id', $item->purchasableId)->first();
    }

    /**
     * Finestra prenotata per famiglia (blueprint): structure check_in→check_out;
     * service giorno intero; event/activity date reali del prodotto (fine
     * giornata se manca ends_at, null/null per attività senza data puntuale);
     * smartbox oggi → oggi + validity_months (validità del cofanetto).
     *
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    private function bookedWindow(CartItemData $item): array
    {
        $purchasable = Relation::getMorphedModel($item->type)::query()->findOrFail($item->purchasableId);

        return match (BookingPricingService::family($purchasable)) {
            'structure' => [
                CarbonImmutable::parse($item->options['check_in']),
                CarbonImmutable::parse($item->options['check_out']),
            ],
            'service' => [
                CarbonImmutable::parse($item->options['day']),
                CarbonImmutable::parse($item->options['day'])->endOfDay(),
            ],
            'event', 'activity' => [
                $purchasable->starts_at,
                $purchasable->ends_at ?? $purchasable->starts_at?->copy()->endOfDay(),
            ],
            'smartbox' => [
                now(),
                now()->addMonths($purchasable->validity_months),
            ],
        };
    }
}
