<?php

namespace App\Livewire\Concerns;

use App\Data\Cart\CartItemData;
use App\Models\Structure\Room;

/**
 * Riga «Stanza: …» delle card di carrello e checkout per le righe con
 * options.room_id. Il nome si legge dalla stanza (Room::displayName()): se il
 * partner l'ha cancellata la riga stanza sparisce in silenzio, e l'errore lo
 * dà il checkout (AvailabilityService::roomFor), non la card.
 */
trait PresentsCartRoom
{
    /**
     * Nomi già letti in questa richiesta, per id stanza (null = cancellata).
     *
     * @var array<int, string|null>
     */
    private array $cartRoomNames = [];

    private function cartRoomLabel(CartItemData $item): ?string
    {
        $id = $item->options['room_id'] ?? null;

        if (! is_numeric($id)) {
            return null;
        }

        $id = (int) $id;

        if (! array_key_exists($id, $this->cartRoomNames)) {
            $this->cartRoomNames[$id] = Room::find($id)?->displayName();
        }

        $name = $this->cartRoomNames[$id];

        return $name === null ? null : __('orders.room', ['name' => $name]);
    }
}
