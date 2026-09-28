<?php

namespace App\Services\Cart;

use App\Models\Cart\Cart as CartModel;
use App\Models\Scopes\CatalogVisibleScope;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Avviso al cliente delle righe tolte dal carrello senza che le togliesse lui.
 *
 * Difetto C9 (audit 28/09/2026): una riga il cui prodotto usciva dal catalogo
 * (ritirato con `withheld_at`, sospeso con `suspended_at`, non più approvato o
 * cancellato) spariva dal carrello senza una parola e restava a database per
 * sempre; al login le righe ospite scartate dal merge finivano in un
 * `Log::info`. Il cliente trovava un totale diverso e nessuna spiegazione.
 * Ora chi toglie la riga lascia qui una voce (prodotto + motivo), e la pagina
 * Carrello la mostra una volta.
 *
 * Dove vive l'avviso: accanto al carrello che descrive.
 * - Carrello a database (utente autenticato) → colonna `carts.notice`. Le sue
 *   righe possono sparire nella richiesta di qualcun altro (l'admin che
 *   sospende, il ritiro di una smartbox): la sessione del cliente lì non c'è,
 *   e l'avviso deve aspettarlo anche per giorni.
 * - Carrello ospite in sessione, e le righe ospite scartate al merge del
 *   login → `session('cart_notice')`, accanto a `session('cart')`: sono
 *   righe che vivevano in sessione, e l'avviso nasce sempre nella richiesta
 *   del cliente stesso.
 * Un flash non basterebbe: il cliente può non passare dal carrello nella
 * richiesta successiva, e il messaggio andrebbe perso con la riga.
 *
 * Una voce è `{title: array<locale, string>, reason: string, message: ?string}`:
 * il titolo resta con tutte le traduzioni perché la voce a database può essere
 * scritta nella lingua dell'admin e letta in quella del cliente.
 */
class CartNotice
{
    /** Chiave di sessione dell'avviso ospite (vedi SessionCartStorage::SESSION_KEY). */
    public const SESSION_KEY = 'cart_notice';

    /** Smartbox ritirata perché il partner non incassa online: «non si compra più online». */
    public const REASON_WITHHELD = 'withheld';

    /** Sospeso, non approvato o cancellato: «non è più disponibile». */
    public const REASON_UNAVAILABLE = 'unavailable';

    /** Riga ospite rifiutata al merge del login: il motivo è il messaggio di CartValidationException. */
    public const REASON_NOT_MERGED = 'not_merged';

    /**
     * Voce d'avviso per un prodotto (null = cancellato, titolo sconosciuto).
     *
     * @return array{title: array<string, string>, reason: string, message: ?string}
     */
    public static function entry(?Model $product, string $reason, ?string $message = null): array
    {
        return [
            'title' => self::titles($product),
            'reason' => $reason,
            'message' => $message,
        ];
    }

    /**
     * Voce d'avviso per un prodotto uscito dal catalogo: il ritiro della
     * piattaforma ha una spiegazione sua, tutto il resto è «non disponibile»
     * (al cliente non serve sapere se l'ha sospeso l'amministrazione).
     *
     * @return array{title: array<string, string>, reason: string, message: ?string}
     */
    public static function leftCatalog(?Model $product): array
    {
        $withheld = $product !== null
            && $product->exists
            && $product->isWithheld()
            && ! $product->isSuspended();

        return self::entry($product, $withheld ? self::REASON_WITHHELD : self::REASON_UNAVAILABLE);
    }

    /**
     * Prodotto di una riga, anche se è uscito dal catalogo: senza lo scope di
     * visibilità, come `OrderItem::purchasable()`, per poterlo nominare
     * nell'avviso. Null se l'alias non è acquistabile o il prodotto non esiste.
     */
    public static function findProduct(string $type, int $id): ?Model
    {
        if (! in_array($type, CartManager::PURCHASABLE_TYPES, true)) {
            return null;
        }

        return Relation::getMorphedModel($type)::withoutGlobalScope(CatalogVisibleScope::class)->find($id);
    }

    /**
     * Annota le voci per il carrello ospite / il merge (richiesta del cliente).
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public function remember(array $entries): void
    {
        if ($entries === []) {
            return;
        }

        session()->put(self::SESSION_KEY, self::merge(session()->get(self::SESSION_KEY, []), $entries));
    }

    /**
     * Annota le voci sul carrello a database: il cliente le troverà alla
     * prossima visita al carrello, da qualunque richiesta siano nate. Lock
     * sulla riga carts: l'aggiunta dell'admin e la lettura del cliente non si
     * pestano i piedi.
     *
     * @param  list<array<string, mixed>>  $entries
     */
    public function rememberForCart(int $cartId, array $entries): void
    {
        if ($entries === []) {
            return;
        }

        DB::transaction(function () use ($cartId, $entries): void {
            $cart = CartModel::query()->whereKey($cartId)->lockForUpdate()->first();

            $cart?->forceFill(['notice' => self::merge($cart->notice ?? [], $entries)])->save();
        });
    }

    /**
     * Svuota l'avviso del visitatore corrente (sessione e, se autenticato, il
     * suo carrello a database) e lo restituisce come frasi pronte da mostrare.
     * Dopo la chiamata l'avviso non c'è più: si mostra una volta.
     *
     * @return list<string>
     */
    public function pull(): array
    {
        $entries = session()->pull(self::SESSION_KEY, []);

        if (Auth::check()) {
            $entries = [...$entries, ...$this->pullFromDatabaseCart()];
        }

        return array_values(array_unique(array_map(self::sentence(...), $entries)));
    }

    /** @return list<array<string, mixed>> */
    private function pullFromDatabaseCart(): array
    {
        // Il caso comune (nessun avviso) costa una query sola, senza transaction.
        if (! CartModel::query()->where('user_id', Auth::id())->whereNotNull('notice')->exists()) {
            return [];
        }

        return DB::transaction(function (): array {
            $cart = CartModel::query()->where('user_id', Auth::id())->lockForUpdate()->first();
            $entries = $cart?->notice ?? [];

            $cart?->forceFill(['notice' => null])->save();

            return $entries;
        });
    }

    /** Frase dell'avviso nella lingua corrente. */
    private static function sentence(array $entry): string
    {
        $title = self::localizedTitle($entry['title'] ?? []);

        return match (true) {
            $title === null => __('cart.notice.untitled'),
            $entry['reason'] === self::REASON_WITHHELD => __('cart.notice.withheld', ['title' => $title]),
            $entry['reason'] === self::REASON_NOT_MERGED => trim(__('cart.notice.not_merged', [
                'title' => $title,
                'reason' => $entry['message'] ?? '',
            ])),
            default => __('cart.notice.unavailable', ['title' => $title]),
        };
    }

    /** Titolo nella lingua corrente, poi in quella di fallback, poi il primo che c'è. */
    private static function localizedTitle(array $titles): ?string
    {
        $titles = array_filter($titles, fn (mixed $title): bool => is_string($title) && $title !== '');

        if ($titles === []) {
            return null;
        }

        return $titles[app()->getLocale()] ?? $titles[config('app.fallback_locale')] ?? reset($titles);
    }

    /**
     * Tutte le traduzioni del nome del prodotto (Structure ha `name`, eventi e
     * smartbox `title`).
     *
     * @return array<string, string>
     */
    private static function titles(?Model $product): array
    {
        if ($product === null || ! method_exists($product, 'getTranslations')) {
            return [];
        }

        return $product->getTranslations($product instanceof Structure ? 'name' : 'title');
    }

    /**
     * Voci esistenti + nuove senza doppioni: due righe dello stesso prodotto
     * (date diverse) danno una frase sola.
     *
     * @param  list<array<string, mixed>>  $current
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private static function merge(array $current, array $entries): array
    {
        $merged = [];

        foreach ([...$current, ...$entries] as $entry) {
            $merged[md5(json_encode($entry))] = $entry;
        }

        return array_values($merged);
    }
}
