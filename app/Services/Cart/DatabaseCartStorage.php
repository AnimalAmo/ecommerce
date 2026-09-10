<?php

namespace App\Services\Cart;

use App\Data\Cart\CartData;
use App\Data\Cart\CartItemData;
use App\Models\Cart\Cart as CartModel;
use App\Models\CartItem\CartItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Storage carrello dell'utente autenticato: una riga carts a testa (user_id
 * unique) + righe cart_items. Le letture usano resolveCart() (nessuna riga
 * carts creata da un semplice render); solo le scritture creano il carrello.
 * Il dedup (purchasable + options canonicalizzate + is_gift) è applicativo.
 */
class DatabaseCartStorage
{
    public function get(): CartData
    {
        $items = $this->items();

        return new CartData(
            id: $this->resolveCart()?->id,
            items: $items,
            count: $items->count(),
            totalCents: (int) $items->sum(fn (CartItemData $item): int => $item->priceCents),
        );
    }

    /** Aggiunge la riga; riga identica già presente = no-op che riallinea lo snapshot prezzo. */
    public function addItem(Model $purchasable, array $options, bool $isGift, int $priceCents, ?int $partnerUserId = null): CartItemData
    {
        $cart = $this->resolveOrCreateCart();

        // Dedup applicativo: confronto in PHP sulle options canonicalizzate.
        // Anche il lato db va ricanonicalizzato: il tipo JSON di mysql riordina
        // le chiavi a modo suo (per lunghezza), rompendo il confronto stretto.
        $existing = $cart->items()
            ->where('purchasable_type', $purchasable->getMorphClass())
            ->where('purchasable_id', $purchasable->getKey())
            ->where('is_gift', $isGift)
            ->get()
            ->first(fn (CartItem $item): bool => CartManager::canonicalize($item->options ?? []) === $options);

        if ($existing !== null) {
            $existing->update(['price_cents' => $priceCents]);

            return CartItemData::fromModel($existing->setRelation('purchasable', $purchasable));
        }

        $item = $cart->items()->create([
            'purchasable_type' => $purchasable->getMorphClass(),
            'purchasable_id' => $purchasable->getKey(),
            'partner_user_id' => $partnerUserId,
            'is_gift' => $isGift,
            'price_cents' => $priceCents,
            'options' => $options,
        ]);

        return CartItemData::fromModel($item->setRelation('purchasable', $purchasable));
    }

    /** Sostituisce options e prezzo della riga (chiave = id cart_items, scoped sull'utente). */
    public function updateItem(string|int $key, array $options, int $priceCents, Model $purchasable): CartItemData
    {
        $item = $this->findItem($key);

        if ($item === null) {
            throw new InvalidArgumentException("Riga carrello inesistente: {$key}");
        }

        $item->update([
            'options' => $options,
            'price_cents' => $priceCents,
        ]);

        return CartItemData::fromModel($item->setRelation('purchasable', $purchasable));
    }

    /** Rimozione idempotente, scoped sul carrello dell'utente (chiavi altrui = no-op). */
    public function removeItem(string|int $key): void
    {
        $this->findItem($key)?->delete();
    }

    /** Svuota le righe mantenendo la riga carts. */
    public function clear(): void
    {
        $this->resolveCart()?->items()->delete();
    }

    /**
     * Righe presentate, filtrabili per flusso regalo (null = tutte); le righe
     * il cui prodotto è sparito dal catalogo vengono saltate (morph senza FK).
     *
     * @return Collection<int, CartItemData>
     */
    public function items(?bool $gift = null): Collection
    {
        $cart = $this->resolveCart();

        if ($cart === null) {
            return new Collection;
        }

        return $cart->items()
            ->when($gift !== null, fn ($query) => $query->where('is_gift', $gift))
            ->with('purchasable')
            ->orderBy('id')
            ->get()
            ->filter(fn (CartItem $item): bool => $item->purchasable !== null)
            ->map(fn (CartItem $item): CartItemData => CartItemData::fromModel($item))
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

    /** Entry grezza della riga (per il merge/update del manager), null se assente o di altri. */
    public function findEntry(string|int $key): ?array
    {
        $item = $this->findItem($key);

        if ($item === null) {
            return null;
        }

        return [
            'type' => $item->purchasable_type,
            'id' => $item->purchasable_id,
            'is_gift' => $item->is_gift,
            'price_cents' => $item->price_cents,
            'options' => $item->options ?? [],
        ];
    }

    /** Riga cart_items dell'utente corrente (ownership by scoping), null se assente. */
    private function findItem(string|int $key): ?CartItem
    {
        return $this->resolveCart()?->items()->whereKey($key)->first();
    }

    /** Carrello dell'utente per le letture: mai firstOrCreate (niente righe vuote a ogni render). */
    private function resolveCart(): ?CartModel
    {
        return CartModel::where('user_id', Auth::id())->first();
    }

    /** Carrello dell'utente per le scritture: creato al primo bisogno. */
    private function resolveOrCreateCart(): CartModel
    {
        return CartModel::firstOrCreate(['user_id' => Auth::id()]);
    }
}
