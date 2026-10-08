<?php

namespace App\Pipes\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\OrderPipelineData;
use App\Enums\OrderPaymentMode;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Services\Availability\AvailabilityService;
use App\Services\Availability\RoomOccupancy;
use App\Services\Partner\PartnerPaymentModeService;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Riverifica la disponibilità di ogni riga e consuma la capienza degli eventi.
 * Event/activity: riga events presa con lockForUpdate, ricontrollo inizio +
 * capienza (booked_participants inclusi, via AvailabilityService) e increment
 * del contatore nella stessa transaction — il sold-out concorrente fa saltare
 * l'intera pipeline (rollback totale, guard post-capture nel chiamante).
 * Structure/service: validazione (chiusure/passato/capienza); con una stanza
 * scelta, la riga rooms presa con lockForUpdate e, per i partner Online,
 * l'occupazione ricontrollata sotto lock contando anche le righe precedenti
 * dello stesso ordine sulla stessa stanza. Smartbox: solo esistenza a catalogo.
 *
 * Purchasable cancellato fra add-to-cart e callback: MAI un ModelNotFound
 * (500 senza storno) — si traduce in CartValidationException così il chiamante
 * storna l'incasso e resta allo step 2.
 */
class ReserveAvailabilityPipe
{
    /**
     * Soggiorni già riservati in questo ordine, per id stanza: a db non ci sono
     * ancora, quindi l'occupazione non li vedrebbe.
     *
     * @var array<int, list<array{0: CarbonImmutable, 1: CarbonImmutable}>>
     */
    private array $pendingStays = [];

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly RoomOccupancy $occupancy,
        private readonly PartnerPaymentModeService $paymentModes,
    ) {}

    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $this->pendingStays = [];

        // Ordine di lock GLOBALE e deterministico (type + id + stanza):
        // checkout concorrenti con righe sovrapposte prendono i lock nella
        // stessa sequenza — niente deadlock da ordinamenti incrociati.
        $items = $data->input->items->sortBy(
            fn (CartItemData $item): string => sprintf(
                '%s:%012d:%012d',
                $item->type,
                $item->purchasableId,
                is_numeric($item->options['room_id'] ?? null) ? (int) $item->options['room_id'] : 0,
            ),
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

        $room = $this->lockRoom($structure, $item->options);

        // Riga senza room_id su una struttura che nel frattempo ha stanze:
        // la rifiuta roomFor (notPurchasable), come all'add-to-cart.
        $this->availability->ensureAvailable($structure, $item->options);

        if ($room === null) {
            return;
        }

        $checkIn = CarbonImmutable::parse($item->options['check_in']);
        $checkOut = CarbonImmutable::parse($item->options['check_out']);

        // Il controllo di ensureAvailable gira senza lock e senza le righe di
        // questo ordine: qui quello che conta. Partner in struttura: nessuna
        // occupazione, gestisce lui le camere.
        if ($this->paymentModes->forPurchasable($structure) === OrderPaymentMode::Online
            && ! $this->occupancy->isAvailableAtCheckout($room, $checkIn, $checkOut, $this->pendingStays[$room->id] ?? [])) {
            throw CartValidationException::unavailableDates();
        }

        $this->pendingStays[$room->id][] = [$checkIn, $checkOut];
    }

    /**
     * Stanza della riga presa con lockForUpdate: due checkout sull'ultima
     * unità si mettono in fila qui, e il secondo ricontrolla dopo il commit
     * del primo. Stanza cancellata o di un'altra struttura ⇒ notPurchasable,
     * mai un 500. Nessun room_id ⇒ null (struttura senza stanze, o riga
     * legacy che ensureAvailable rifiuta).
     */
    private function lockRoom(Structure $structure, array $options): ?Room
    {
        $roomId = $options['room_id'] ?? null;

        if ($roomId === null || $roomId === '') {
            return null;
        }

        $room = is_numeric($roomId)
            ? Room::query()->whereKey((int) $roomId)->where('structure_id', $structure->id)->lockForUpdate()->first()
            : null;

        if ($room === null) {
            throw CartValidationException::notPurchasable();
        }

        return $room;
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
