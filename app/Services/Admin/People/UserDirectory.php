<?php

namespace App\Services\Admin\People;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Elenco "Iscritti" del pannello: la stessa query serve la tabella e
 * l'esportazione in Excel, così i filtri non possono divergere.
 *
 * Sono esclusi i superadmin (non sono clienti). La newsletter si legge da
 * `newsletter_subscribers` (per user_id o per email: chi si è iscritto dal
 * footer prima di registrarsi non ha lo user_id), non dal vecchio flag
 * `users.newsletter`, che non ha prova del consenso. Ordini e speso contano
 * solo gli ordini pagati: i pending non sono incassi e gli annullati non lo
 * sono più.
 */
class UserDirectory
{
    public const NEWSLETTER_FILTERS = ['all', 'with', 'without'];

    public const STATUS_FILTERS = ['all', 'active', 'inactive', 'anonymized'];

    public const PERIOD_FILTERS = ['always', '30d', 'year'];

    public const ROLE_FILTERS = ['all', 'client', 'partner'];

    public const SORTS = ['name', 'created_at', 'orders', 'spent'];

    /** Stati della riga newsletter che contano come "ha chiesto la newsletter". */
    private const REQUESTED = ['confirmed', 'pending'];

    /**
     * Filtri normalizzati: un valore sconosciuto (URL modificato a mano) torna
     * al default invece di produrre una query vuota o un errore.
     *
     * @param  array<string, mixed>  $input
     * @return array{q: string, newsletter: string, status: string, period: string, role: string, sort: string, dir: string}
     */
    public function normalize(array $input): array
    {
        $pick = fn (string $key, array $allowed, string $default): string => in_array($input[$key] ?? null, $allowed, true) ? $input[$key] : $default;

        return [
            'q' => trim((string) ($input['q'] ?? '')),
            'newsletter' => $pick('newsletter', self::NEWSLETTER_FILTERS, 'all'),
            'status' => $pick('status', self::STATUS_FILTERS, 'all'),
            'period' => $pick('period', self::PERIOD_FILTERS, 'always'),
            'role' => $pick('role', self::ROLE_FILTERS, 'all'),
            'sort' => $pick('sort', self::SORTS, 'created_at'),
            'dir' => $pick('dir', ['asc', 'desc'], 'desc'),
        ];
    }

    /**
     * Utenti filtrati e ordinati, con le colonne calcolate:
     * `newsletter_state` (confirmed|pending|null), `paid_orders_count`, `spent_cents`.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    public function query(array $filters): Builder
    {
        $filters = $this->normalize($filters);

        $query = $this->base()
            ->select('users.*')
            ->selectSub($this->newsletterStateSub(), 'newsletter_state')
            ->selectSub($this->paidOrders()->selectRaw('count(*)'), 'paid_orders_count')
            ->selectSub($this->paidOrders()->selectRaw('coalesce(sum(total_cents), 0)'), 'spent_cents')
            ->with('roles');

        // Ogni parola deve comparire in nome, cognome o email: "mario rossi"
        // trova Mario Rossi senza concatenare colonne (|| su MySQL è un OR).
        foreach (preg_split('/\s+/', mb_strtolower($filters['q']), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $term = '%'.$word.'%';

            $query->where(function (Builder $q) use ($term): void {
                $q->whereRaw('lower(users.email) like ?', [$term])
                    ->orWhereRaw('lower(users.first_name) like ?', [$term])
                    ->orWhereRaw('lower(users.last_name) like ?', [$term]);
            });
        }

        match ($filters['newsletter']) {
            'with' => $query->whereExists($this->subscriberRows(self::REQUESTED)),
            'without' => $query->whereNotExists($this->subscriberRows(self::REQUESTED)),
            default => null,
        };

        match ($filters['status']) {
            'active' => $query->where('users.is_active', true)->whereNull('users.anonymized_at'),
            'inactive' => $query->where('users.is_active', false)->whereNull('users.anonymized_at'),
            'anonymized' => $query->whereNotNull('users.anonymized_at'),
            default => null,
        };

        match ($filters['period']) {
            '30d' => $query->where('users.created_at', '>=', now()->subDays(30)),
            'year' => $query->where('users.created_at', '>=', now()->startOfYear()),
            default => null,
        };

        match ($filters['role']) {
            'partner' => $query->whereHas('roles', fn (Builder $r) => $r->where('name', 'partner')),
            'client' => $query->whereDoesntHave('roles', fn (Builder $r) => $r->where('name', 'partner')),
            default => null,
        };

        $direction = $filters['dir'];

        match ($filters['sort']) {
            'name' => $query->orderBy('users.last_name', $direction)->orderBy('users.first_name', $direction),
            'orders' => $query->orderBy('paid_orders_count', $direction),
            'spent' => $query->orderBy('spent_cents', $direction),
            default => $query->orderBy('users.created_at', $direction),
        };

        return $query->orderBy('users.id', $direction);
    }

    /** Totali del sottotitolo: utenti registrati e quanti hanno chiesto la newsletter. */
    public function totals(): array
    {
        return [
            'users' => $this->base()->count(),
            'newsletter' => $this->base()->whereExists($this->subscriberRows(self::REQUESTED))->count(),
        ];
    }

    /** Stato newsletter di un singolo utente (confirmed|pending|unsubscribed|…|null). */
    public function newsletterState(User $user): ?string
    {
        return $this->base()->whereKey($user->id)
            ->selectSub($this->newsletterStateSub(), 'state')
            ->value('state');
    }

    /** '€ 1.240' come nel design; '—' per chi non ha speso. */
    public static function money(int $cents): string
    {
        if ($cents === 0) {
            return '—';
        }

        return '€ '.number_format($cents / 100, $cents % 100 === 0 ? 0 : 2, ',', '.');
    }

    /** @return Builder<User> */
    private function base(): Builder
    {
        return User::query()->whereDoesntHave('roles', fn (Builder $r) => $r->where('name', 'superadmin'));
    }

    private function paidOrders(): QueryBuilder
    {
        return DB::table('orders')
            ->whereColumn('orders.user_id', 'users.id')
            ->where('orders.status', OrderStatus::Paid->value);
    }

    /** @param  list<string>|null  $statuses */
    private function subscriberRows(?array $statuses = null): QueryBuilder
    {
        return DB::table('newsletter_subscribers')
            ->where(function (QueryBuilder $q): void {
                $q->whereColumn('newsletter_subscribers.user_id', 'users.id')
                    ->orWhereRaw('newsletter_subscribers.email = lower(users.email)');
            })
            ->when($statuses !== null, fn (QueryBuilder $q) => $q->whereIn('newsletter_subscribers.status', $statuses));
    }

    /** Lo stato "migliore" tra le righe dell'utente: confermato batte in attesa, che batte il resto. */
    private function newsletterStateSub(): QueryBuilder
    {
        return $this->subscriberRows()
            ->select('newsletter_subscribers.status')
            ->orderByRaw("case newsletter_subscribers.status when 'confirmed' then 0 when 'pending' then 1 else 2 end")
            ->limit(1);
    }
}
