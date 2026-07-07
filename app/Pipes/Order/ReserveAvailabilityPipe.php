<?php

namespace App\Pipes\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\OrderPipelineData;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\Structure\Structure;
use App\Services\Availability\AvailabilityService;
use App\Services\Pricing\BookingPricingService;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Riverifica la disponibilità di ogni riga e consuma la capienza degli eventi.
 * Event/activity: riga events presa con lockForUpdate, ricontrollo inizio +
 * capienza (booked_participants inclusi, via AvailabilityService) e increment
 * del contatore nella stessa transaction — il sold-out concorrente fa saltare
 * l'intera pipeline (rollback totale, guard post-capture nel chiamante).
 * Structure/service: sola validazione read-only (chiusure/passato), nessun
 * contatore da consumare. Smartbox: solo esistenza a catalogo.
 *
 * Purchasable cancellato fra add-to-cart e callback: MAI un ModelNotFound
 * (500 senza storno) — si traduce in CartValidationException così il chiamante
 * storna l'incasso e resta allo step 2.
 */
class ReserveAvailabilityPipe
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        // Ordine di lock GLOBALE e deterministico (type + id): checkout
        // concorrenti con righe sovrapposte prendono i lock nella stessa
        // sequenza — niente deadlock da ordinamenti incrociati.
        $items = $data->input->items->sortBy(
            fn (CartItemData $item): string => sprintf('%s:%012d', $item->type, $item->purchasableId),
        );

        foreach ($items as $item) {
            match ($item->type) {
                'event' => $this->reserveEventSeats($item),
                'structure' => $this->ensureStructureAvailable($item),
                // smartbox_package e futuri type senza data/capienza: basta l'esistenza.
                default => $this->ensurePurchasableExists($item),
            };
        }

        return $next($data);
    }

    /** Lock pessimistico, ricontrollo sull'istanza lockata, poi consumo posti. */
    private function reserveEventSeats(CartItemData $item): void
    {
        $event = Event::query()->whereKey($item->purchasableId)->lockForUpdate()->first();

        if ($event === null) {
            throw CartValidationException::notPurchasable();
        }

        $this->availability->ensureAvailable($event, $item->options);

        $event->increment('booked_participants', BookingPricingService::persons($item->options));
    }

    private function ensureStructureAvailable(CartItemData $item): void
    {
        $structure = Structure::query()->find($item->purchasableId);

        if ($structure === null) {
            throw CartValidationException::notPurchasable();
        }

        $this->availability->ensureAvailable($structure, $item->options);
    }

    private function ensurePurchasableExists(CartItemData $item): void
    {
        $exists = Relation::getMorphedModel($item->type)::query()
            ->whereKey($item->purchasableId)
            ->exists();

        if (! $exists) {
            throw CartValidationException::notPurchasable();
        }
    }
}
