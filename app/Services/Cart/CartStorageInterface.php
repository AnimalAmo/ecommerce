<?php

namespace App\Services\Cart;

use App\Data\Cart\CartData;
use App\Data\Cart\CartItemData;
use Illuminate\Support\Collection;

/**
 * Contratto pubblico del carrello (implementato da CartManager): tutti gli
 * importi sono integer cents, la chiave riga ($key) è l'id cart_items per
 * l'utente autenticato e l'hash md5 dell'entry per il guest in sessione.
 *
 * I due storage concreti (SessionCartStorage/DatabaseCartStorage) NON
 * implementano questa interface: ricevono il prezzo già quotato dal manager
 * (mai dal client) e restano dettagli interni.
 */
interface CartStorageInterface
{
    /** Fotografia completa del carrello (righe non filtrate, totale in cents). */
    public function get(): CartData;

    /**
     * Aggiunge una riga ($type = alias morph whitelisted): options
     * canonicalizzate, disponibilità verificata e prezzo quotato server-side.
     * Riga già presente (stesso purchasable + options + is_gift) = no-op che
     * aggiorna il prezzo.
     */
    public function addItem(string $type, int $id, array $options, bool $isGift): CartItemData;

    /** Fonde le options nella riga, poi rivalida disponibilità e riprezza. */
    public function updateItem(string|int $key, array $options): CartItemData;

    /** Aggiorna i metadati regalo (dedication/message/recipient_email) di una riga gift. */
    public function updateGift(string|int $key, array $gift): void;

    /** Rimuove la riga (idempotente: chiave assente = no-op). */
    public function removeItem(string|int $key): void;

    /** Svuota il carrello. */
    public function clear(): void;

    /**
     * Righe presentate (CartItemData), filtrabili per flusso: true = solo
     * regalo, false = solo normali, null = tutte (usi interni: merge, count).
     *
     * @return Collection<int, CartItemData>
     */
    public function items(?bool $gift = null): Collection;

    /** Numero di righe (quantity fissa 1: "N articoli" = righe). */
    public function count(): int;

    /** Totale in cents del set filtrato (stessa semantica di items()). */
    public function total(?bool $gift = null): int;
}
