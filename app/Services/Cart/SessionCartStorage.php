<?php

namespace App\Services\Cart;

use App\Data\Cart\CartData;
use App\Data\Cart\CartItemData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Storage carrello del guest: puro array in sessione (chiave 'cart'), nessuna
 * riga a db. Entry: chiave md5 → {type, id, is_gift, price_cents, options}.
 * I prezzi arrivano già quotati dal CartManager (mai dal client); le righe il
 * cui prodotto è sparito dal catalogo vengono scartate silenziosamente in lettura.
 */
class SessionCartStorage
{
    /** Chiave dell'array carrello in sessione (come matsuri). */
    public const SESSION_KEY = 'cart';

    public function get(): CartData
    {
        $items = $this->items();

        return new CartData(
            // Il carrello guest non ha riga carts: id null.
            id: null,
            items: $items,
            count: $items->count(),
            totalCents: (int) $items->sum(fn (CartItemData $item): int => $item->priceCents),
        );
    }

    /** Aggiunge l'entry; chiave già presente (stesso contenuto) = aggiorna solo il prezzo. */
    public function addItem(Model $purchasable, array $options, bool $isGift, int $priceCents): CartItemData
    {
        $type = $purchasable->getMorphClass();
        $key = self::itemKey($type, $purchasable->getKey(), $isGift, $options);

        $cart = $this->getSessionCart();

        if (isset($cart[$key])) {
            // Dedup: add ripetuto = no-op che riallinea lo snapshot prezzo.
            $cart[$key]['price_cents'] = $priceCents;
        } else {
            $cart[$key] = [
                'type' => $type,
                'id' => $purchasable->getKey(),
                'is_gift' => $isGift,
                'price_cents' => $priceCents,
                'options' => $options,
            ];
        }

        session()->put(self::SESSION_KEY, $cart);

        return CartItemData::fromSessionEntry($key, $cart[$key], $purchasable);
    }

    /**
     * Sostituisce options e prezzo della riga. La chiave è l'hash del contenuto,
     * quindi viene ricalcolata: una eventuale riga identica già presente viene
     * assorbita (dedup naturale).
     */
    public function updateItem(string|int $key, array $options, int $priceCents, Model $purchasable): CartItemData
    {
        $cart = $this->getSessionCart();
        $entry = $cart[$key] ?? null;

        if ($entry === null) {
            throw new InvalidArgumentException("Riga carrello inesistente: {$key}");
        }

        unset($cart[$key]);

        $entry['options'] = $options;
        $entry['price_cents'] = $priceCents;

        $newKey = self::itemKey($entry['type'], $entry['id'], (bool) $entry['is_gift'], $options);
        $cart[$newKey] = $entry;

        session()->put(self::SESSION_KEY, $cart);

        return CartItemData::fromSessionEntry($newKey, $entry, $purchasable);
    }

    /** Rimozione idempotente: chiave assente = no-op. */
    public function removeItem(string|int $key): void
    {
        $cart = $this->getSessionCart();

        unset($cart[$key]);

        session()->put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Righe presentate, filtrabili per flusso regalo (null = tutte). Prodotti
     * bulk-loaded per tipo; le entry orfane vengono saltate.
     *
     * @return Collection<int, CartItemData>
     */
    public function items(?bool $gift = null): Collection
    {
        $entries = collect($this->getSessionCart())
            ->when($gift !== null, fn (Collection $all): Collection => $all->filter(
                fn (array $entry): bool => (bool) $entry['is_gift'] === $gift,
            ));

        // Un findMany per tipo morph invece di una query per riga.
        $purchasables = $entries
            ->groupBy('type')
            ->map(fn (Collection $group, string $type) => Relation::getMorphedModel($type)::findMany($group->pluck('id'))->keyBy(
                fn (Model $model) => $model->getKey(),
            ));

        return $entries
            ->map(function (array $entry, string $key) use ($purchasables): ?CartItemData {
                $purchasable = $purchasables[$entry['type']][$entry['id']] ?? null;

                return $purchasable !== null ? CartItemData::fromSessionEntry($key, $entry, $purchasable) : null;
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return $this->items()->count();
    }

    public function total(?bool $gift = null): int
    {
        return (int) $this->items($gift)->sum(fn (CartItemData $item): int => $item->priceCents);
    }

    /** Entry grezza della riga (per il merge/update del manager), null se assente. */
    public function findEntry(string|int $key): ?array
    {
        return $this->getSessionCart()[$key] ?? null;
    }

    /**
     * Array grezzo delle entry di sessione: usato da MergeCartOnLogin per
     * riversare le righe guest nello storage database.
     */
    public function getSessionCart(): array
    {
        return session()->get(self::SESSION_KEY, []);
    }

    /** Chiave riga deterministica: stesso contenuto (options canonicalizzate) = stessa riga. */
    public static function itemKey(string $type, int $id, bool $isGift, array $options): string
    {
        return md5($type.':'.$id.':'.(int) $isGift.':'.json_encode($options));
    }
}
