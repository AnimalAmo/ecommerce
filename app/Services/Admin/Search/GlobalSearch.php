<?php

namespace App\Services\Admin\Search;

use App\Models\Order\Order;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Il campo "Cerca schede, iscritti, ordini…" in testa al pannello.
 *
 * Tre ricerche, ognuna con la definizione della sua schermata, così un
 * risultato trovato qui si ritrova anche nell'elenco completo:
 *  - schede: la ricerca del Catalogo (nome, località, partner), che vede
 *    anche le schede sospese o in attesa;
 *  - iscritti: come l'elenco Iscritti, ogni parola in nome, cognome o email,
 *    superadmin esclusi;
 *  - ordini: solo per numero ("42", "ORD-42", "ORD-000042"), il numero esatto
 *    per primo.
 *
 * Ogni gruppo restituisce i primi LIMIT risultati e il totale.
 */
class GlobalSearch
{
    public const MIN_LENGTH = 2;

    public const LIMIT = 8;

    public function __construct(private readonly CatalogAdmin $catalog) {}

    public function isSearchable(string $term): bool
    {
        return mb_strlen(trim($term)) >= self::MIN_LENGTH;
    }

    /** Un termine senza cifre non può essere un numero d'ordine. */
    public function searchesOrders(string $term): bool
    {
        return preg_match('/\d/', $term) === 1;
    }

    /** @return array{items: Collection<int, Model>, total: int} */
    public function catalog(string $term): array
    {
        $page = $this->catalog->paginate(['search' => trim($term)], self::LIMIT);

        return ['items' => $page->getCollection(), 'total' => $page->total()];
    }

    /** @return array{items: Collection<int, User>, total: int} */
    public function users(string $term): array
    {
        $query = User::query()->whereDoesntHave('roles', fn (Builder $role) => $role->where('name', 'superadmin'));

        foreach (preg_split('/\s+/', mb_strtolower(trim($term)), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $like = '%'.$word.'%';

            $query->where(fn (Builder $q) => $q
                ->whereRaw('lower(users.email) like ?', [$like])
                ->orWhereRaw('lower(users.first_name) like ?', [$like])
                ->orWhereRaw('lower(users.last_name) like ?', [$like]));
        }

        return [
            'items' => (clone $query)->with('roles')->latest('users.created_at')->latest('users.id')->limit(self::LIMIT)->get(),
            'total' => $query->count(),
        ];
    }

    /**
     * Ordini per numero. Senza cifre nel termine non si cerca nemmeno: un nome
     * non è un numero d'ordine, e il cliente si trova fra gli iscritti.
     *
     * @return array{items: Collection<int, Order>, total: int}
     */
    public function orders(string $term): array
    {
        $term = trim($term);

        if (! $this->searchesOrders($term)) {
            return ['items' => collect(), 'total' => 0];
        }

        $query = Order::query();
        $exact = null;

        if (preg_match('/^(?:ord-?)?(\d+)$/i', $term, $match) === 1) {
            $exact = Order::formatOrderNumber((int) $match[1]);
            $query->where(fn (Builder $q) => $q
                ->where('order_number', $exact)
                ->orWhere('order_number', 'like', '%'.$match[1].'%'));
        } else {
            $query->where('order_number', 'like', '%'.mb_strtoupper($term).'%');
        }

        $items = (clone $query)
            ->with('user')
            ->when($exact !== null, fn (Builder $q) => $q->orderByRaw('case when order_number = ? then 0 else 1 end', [$exact]))
            ->latest('created_at')
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();

        return ['items' => $items, 'total' => $query->count()];
    }
}
