<?php

namespace App\Services\Cart;

use App\Data\Cart\CartData;
use App\Data\Cart\CartItemData;
use App\Models\Cart\Cart as CartModel;
use App\Models\CartItem\CartItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Storage carrello dell'utente autenticato: una riga carts a testa (user_id
 * unique) + righe cart_items. Le letture usano resolveCart() (nessuna riga
 * carts creata da un semplice render); solo le scritture creano il carrello.
 * Il dedup (purchasable + options canonicalizzate + is_gift) è applicativo.
 *
 * Una riga il cui prodotto esce dal catalogo viene tolta davvero e detta al
 * cliente (CartNotice), per due strade (difetto C9, audit 28/09/2026):
 * subito, dagli eventi del modello (withdrawIfHidden, registrato in
 * CartServiceProvider), e comunque alla prima lettura (items), che copre chi
 * scrive `withheld_at`/`suspended_at` senza passare dal modello.
 */
class DatabaseCartStorage
{
    public function __construct(
        private readonly CartNotice $notices,
    ) {}

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

    /**
     * Quante di queste righe sono ancora nel carrello dell'utente, lette con
     * FOR UPDATE: dentro una transaction la seconda lettura concorrente aspetta
     * che la prima finisca, e poi vede le righe che quella ha tolto.
     *
     * @param  list<int|string>  $keys
     */
    public function lockItems(array $keys): int
    {
        return $this->resolveCart()?->items()->whereKey($keys)->lockForUpdate()->get(['id'])->count() ?? 0;
    }

    /** Svuota le righe mantenendo la riga carts. */
    public function clear(): void
    {
        $this->resolveCart()?->items()->delete();
    }

    /**
     * Righe presentate, filtrabili per flusso regalo (null = tutte).
     *
     * Difetto C9 (audit 28/09/2026): le righe il cui prodotto era uscito dal
     * catalogo venivano saltate in silenzio e restavano a database per
     * sempre — il cliente trovava un totale più basso senza una parola. Ora
     * la lettura le riconosce (la relazione purchasable non ha più lo scope
     * di catalogo), le cancella e le annota sul carrello per l'avviso. Si
     * controlla tutto il carrello, non solo il flusso chiesto: una riga
     * regalo fantasma non deve aspettare che qualcuno apra la vista regalo.
     *
     * @return Collection<int, CartItemData>
     */
    public function items(?bool $gift = null): Collection
    {
        $cart = $this->resolveCart();

        if ($cart === null) {
            return new Collection;
        }

        [$sellable, $gone] = $cart->items()
            ->with('purchasable')
            ->orderBy('id')
            ->get()
            ->partition(fn (CartItem $item): bool => $item->purchasable?->isVisibleInCatalog() === true);

        if ($gone->isNotEmpty()) {
            $this->withdraw($cart->id, $gone);
        }

        return $sellable
            ->when($gift !== null, fn (Collection $items): Collection => $items->filter(
                fn (CartItem $item): bool => $item->is_gift === $gift,
            ))
            ->map(fn (CartItem $item): CartItemData => CartItemData::fromModel($item))
            ->values();
    }

    /**
     * Un prodotto appena salvato non è più vendibile (ritirato, sospeso, non
     * più approvato): va tolto da TUTTI i carrelli a database, subito, con
     * l'avviso per ciascun cliente. Chiamato dall'evento `updated` dei modelli
     * di catalogo (CartServiceProvider).
     *
     * Niente controllo su quale colonna sia cambiata: se il prodotto è
     * nascosto, nessun carrello deve tenerlo — anche le righe rimaste da un
     * ritiro scritto con una query secca (la migrazione del 27/09) vengono
     * pulite alla prima modifica successiva della scheda.
     *
     * La domanda «è ancora in vetrina?» la fa CatalogVisibleScope a database,
     * non isVisibleInCatalog() sugli attributi in memoria: un modello creato
     * nella stessa richiesta non ha i default di colonna (approval_status
     * resta null), e al primo update — basta l'increment dei posti prenotati —
     * risulterebbe nascosto e verrebbe tolto da tutti i carrelli.
     */
    public function withdrawIfHidden(Model $product): void
    {
        if ($product->newQuery()->whereKey($product->getKey())->exists()) {
            return;
        }

        $this->withdrawProduct($product);
    }

    /**
     * Toglie un prodotto da tutti i carrelli a database e lo annota su
     * ciascuno. Anche per un prodotto appena cancellato (evento `deleted`):
     * il modello in memoria ha ancora il titolo da nominare.
     */
    public function withdrawProduct(Model $product): void
    {
        $items = CartItem::query()
            ->where('purchasable_type', $product->getMorphClass())
            ->where('purchasable_id', $product->getKey())
            ->get(['id', 'cart_id']);

        if ($items->isEmpty()) {
            return;
        }

        $entry = CartNotice::leftCatalog($product);

        DB::transaction(function () use ($items, $entry): void {
            CartItem::query()->whereKey($items->modelKeys())->delete();

            // Carrelli in ordine di id: rememberForCart li blocca uno a uno
            // fino al commit, e due ritiri concorrenti che li prendessero in
            // ordine diverso andrebbero in deadlock su MySQL (review del
            // 28/09/2026: l'ordine di lettura segue l'indice del prodotto).
            foreach ($items->pluck('cart_id')->unique()->sort()->values() as $cartId) {
                $this->notices->rememberForCart($cartId, [$entry]);
            }
        });
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

    /**
     * Cancella le righe fantasma trovate in lettura e le annota sul carrello.
     * L'avviso si scrive solo se la cancellazione ha tolto qualcosa: due
     * letture concorrenti non lo raddoppiano.
     *
     * @param  EloquentCollection<int, CartItem>  $gone
     */
    private function withdraw(int $cartId, EloquentCollection $gone): void
    {
        $deleted = CartItem::query()->whereKey($gone->modelKeys())->delete();

        if ($deleted > 0) {
            $this->notices->rememberForCart(
                $cartId,
                $gone->map(fn (CartItem $item): array => CartNotice::leftCatalog($item->purchasable))->values()->all(),
            );
        }
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
