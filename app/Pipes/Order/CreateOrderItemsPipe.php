<?php

namespace App\Pipes\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\OrderPipelineData;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Crea le righe ordine come snapshot autonomo dal catalogo (title/photo/tipo/
 * località/prezzo/options del CartItemData) più la finestra prenotata
 * booked_from/booked_until calcolata qui per famiglia — è ciò che permette al
 * profilo ordini di bucketizzare programma/passati senza toccare il prodotto.
 */
class CreateOrderItemsPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        foreach ($data->input->items as $item) {
            [$bookedFrom, $bookedUntil] = $this->bookedWindow($item);

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
                'options' => $item->options,
                'booked_from' => $bookedFrom,
                'booked_until' => $bookedUntil,
            ]));
        }

        return $next($data);
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
