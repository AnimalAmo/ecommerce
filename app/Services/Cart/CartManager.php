<?php

namespace App\Services\Cart;

use App\Data\Cart\CartData;
use App\Data\Cart\CartItemData;
use App\Exceptions\CartValidationException;
use App\Services\Availability\AvailabilityService;
use App\Services\Partner\PartnerOwnerResolver;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Facciata unica del carrello (singleton via CartServiceProvider): sceglie lo
 * storage per chiamata (guest = sessione, autenticato = db) e concentra le
 * regole di scrittura — whitelist morph, options canonicalizzate (ksort
 * ricorsivo), disponibilità verificata e prezzo sempre quotato server-side.
 * I componenti Livewire restano adapter UI (pattern FavoriteService).
 */
class CartManager implements CartStorageInterface
{
    /** Alias morph acquistabili (sottoinsieme catalogo della mappa in AppServiceProvider). */
    public const PURCHASABLE_TYPES = ['structure', 'event', 'smartbox_package'];

    /** Campi ammessi nei metadati regalo (options['gift']). */
    private const GIFT_FIELDS = ['dedication', 'message', 'recipient_email'];

    public function __construct(
        private readonly SessionCartStorage $sessionStorage,
        private readonly DatabaseCartStorage $databaseStorage,
        private readonly AvailabilityService $availability,
        private readonly BookingPricingService $pricing,
        private readonly PartnerOwnerResolver $owners,
    ) {}

    public function get(): CartData
    {
        return $this->driver()->get();
    }

    public function addItem(string $type, int $id, array $options, bool $isGift): CartItemData
    {
        $purchasable = $this->resolvePurchasable($type, $id);
        $options = self::canonicalize($options);

        // Senza proprietario non esiste un conto Stripe su cui far nascere
        // l'incasso: il prodotto non è vendibile, e va fermato qui.
        $partnerUserId = $this->owners->ownerIdFor($purchasable);

        if ($partnerUserId === null) {
            throw CartValidationException::productWithoutOwner();
        }

        $this->availability->ensureAvailable($purchasable, $options);
        $priceCents = $this->pricing->quote($purchasable, $options);

        return $this->driver()->addItem($purchasable, $options, $isGift, $priceCents, $partnerUserId);
    }

    /** Fonde le options nella riga (le chiavi non passate restano), poi rivalida e riprezza. */
    public function updateItem(string|int $key, array $options): CartItemData
    {
        $entry = $this->driver()->findEntry($key);

        // Riga sparita o di un altro utente: stesso 404 del prodotto inesistente.
        abort_unless($entry !== null, 404);

        $purchasable = $this->resolvePurchasable($entry['type'], $entry['id']);
        $options = self::canonicalize(array_merge($entry['options'], $options));

        $this->availability->ensureAvailable($purchasable, $options);
        $priceCents = $this->pricing->quote($purchasable, $options);

        return $this->driver()->updateItem($key, $options, $priceCents, $purchasable);
    }

    /**
     * Aggiorna i metadati regalo della riga (dedica/messaggio/email destinatario,
     * stringhe vuote normalizzate a null): non toccano il prezzo, niente riquotazione.
     */
    public function updateGift(string|int $key, array $gift): void
    {
        $entry = $this->driver()->findEntry($key);

        abort_unless($entry !== null, 404);

        $purchasable = $this->resolvePurchasable($entry['type'], $entry['id']);

        $options = $entry['options'];
        $options['gift'] = array_merge($options['gift'] ?? [], self::normalizeGift($gift));

        $this->driver()->updateItem($key, self::canonicalize($options), $entry['price_cents'], $purchasable);
    }

    public function removeItem(string|int $key): void
    {
        $this->driver()->removeItem($key);
    }

    public function clear(): void
    {
        $this->driver()->clear();
    }

    public function items(?bool $gift = null): Collection
    {
        return $this->driver()->items($gift);
    }

    public function count(): int
    {
        return $this->driver()->count();
    }

    public function total(?bool $gift = null): int
    {
        return $this->driver()->total($gift);
    }

    /**
     * Options in forma canonica (ksort ricorsivo): stesso contenuto = stesso
     * json = stessa chiave riga, qualunque sia l'ordine di arrivo dai widget.
     */
    public static function canonicalize(array $options): array
    {
        ksort($options);

        foreach ($options as &$value) {
            if (is_array($value)) {
                $value = self::canonicalize($value);
            }
        }

        return $options;
    }

    /** Storage per chiamata: db se autenticato, sessione da guest (come matsuri). */
    private function driver(): SessionCartStorage|DatabaseCartStorage
    {
        return Auth::check() ? $this->databaseStorage : $this->sessionStorage;
    }

    /** Whitelist alias + esistenza (pattern FavoriteService: 400 alias invalido, 404 prodotto sparito). */
    private function resolvePurchasable(string $type, int $id): Model
    {
        // Solo alias della morph map enforced (mai class-string).
        abort_unless(in_array($type, self::PURCHASABLE_TYPES, true), 400);

        $model = Relation::getMorphedModel($type);
        $purchasable = $model::find($id);

        abort_unless($purchasable !== null, 404);

        return $purchasable;
    }

    /** Solo i campi regalo ammessi, trim e stringhe vuote a null (niente default fake). */
    private static function normalizeGift(array $gift): array
    {
        $normalized = [];

        foreach (self::GIFT_FIELDS as $field) {
            if (! array_key_exists($field, $gift)) {
                continue;
            }

            $value = is_string($gift[$field]) ? trim($gift[$field]) : $gift[$field];
            $normalized[$field] = $value === '' ? null : $value;
        }

        return $normalized;
    }
}
