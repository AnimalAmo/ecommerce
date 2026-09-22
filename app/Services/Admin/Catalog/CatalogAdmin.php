<?php

namespace App\Services\Admin\Catalog;

use App\Enums\OrderStatus;
use App\Mail\CatalogModerationMail;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Il catalogo visto dal pannello: le tre famiglie (strutture, attività ed
 * eventi, smartbox) in un elenco solo, con filtri e stati amministrativi.
 *
 * Le tre tabelle hanno colonne diverse (name/title, regione solo sulle
 * strutture): l'elenco è una UNION delle sole chiavi comuni, paginata in SQL,
 * e le righe della pagina vengono poi caricate come model veri. Così ordinare
 * e paginare 3 tabelle costa una query, non tre elenchi fusi in PHP.
 */
class CatalogAdmin
{
    /** Alias della morph map → [model, tabella, colonna del nome]. */
    public const FAMILIES = [
        'structure' => [Structure::class, 'structures', 'name'],
        'event' => [Event::class, 'events', 'title'],
        'smartbox_package' => [SmartboxPackage::class, 'smartbox_packages', 'title'],
    ];

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CHANGES = 'changes_requested';

    /**
     * @param  array{search?: string, partner?: string, family?: string, region?: string, status?: string}  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $page = DB::query()
            ->fromSub($this->union($filters), 'catalog')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $models = $this->hydrate(collect($page->items()));

        return $page->setCollection($models);
    }

    /** Stesso elenco senza paginazione, per l'esportazione. */
    public function all(array $filters): Collection
    {
        $rows = DB::query()
            ->fromSub($this->union($filters), 'catalog')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->hydrate($rows);
    }

    /** @return array{total: int, suspended: int, pending: int} */
    public function totals(): array
    {
        $totals = ['total' => 0, 'suspended' => 0, 'pending' => 0];

        foreach (self::FAMILIES as [, $table]) {
            $totals['total'] += DB::table($table)->count();
            $totals['suspended'] += DB::table($table)->whereNotNull('suspended_at')->count();
            $totals['pending'] += DB::table($table)->where('approval_status', Structure::APPROVAL_PENDING)->count();
        }

        return $totals;
    }

    /** Partner che hanno almeno una scheda: le voci del filtro "Tutti i partner". */
    public function partnerOptions(): Collection
    {
        $ids = collect(self::FAMILIES)
            ->flatMap(fn (array $family) => DB::table($family[1])->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->unique();

        return DB::table('users')
            ->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
            ->whereIn('users.id', $ids)
            ->orderByRaw('coalesce(partner_profiles.business_name, users.last_name)')
            ->get(['users.id', 'users.first_name', 'users.last_name', 'partner_profiles.business_name'])
            ->mapWithKeys(fn (object $row) => [$row->id => $row->business_name ?: trim($row->first_name.' '.$row->last_name)]);
    }

    public function find(string $family, int $id): Model
    {
        abort_unless(isset(self::FAMILIES[$family]), 404);

        /** @var class-string<Model> $class */
        $class = self::FAMILIES[$family][0];

        return $class::withHidden()->with(['user.partnerProfile'])->findOrFail($id);
    }

    /** Stato amministrativo unico, quello del badge: in attesa e modifiche vincono sulla sospensione. */
    public function status(Model $item): string
    {
        return match (true) {
            $item->approval_status === Structure::APPROVAL_PENDING => self::STATUS_PENDING,
            $item->approval_status === Structure::APPROVAL_CHANGES_REQUESTED => self::STATUS_CHANGES,
            $item->suspended_at !== null => self::STATUS_SUSPENDED,
            default => self::STATUS_PUBLISHED,
        };
    }

    public function family(Model $item): string
    {
        return $item->getMorphClass();
    }

    public function name(Model $item, string $locale = 'it'): string
    {
        $column = self::FAMILIES[$this->family($item)][2];

        return (string) ($item->getTranslation($column, $locale, false) ?: $item->getTranslation($column, 'it', false));
    }

    public function partnerName(Model $item): string
    {
        $user = $item->user;

        if ($user === null) {
            return __('admin-catalog.platform');
        }

        return $user->partnerProfile?->business_name ?: $user->name;
    }

    /** Regione per le strutture, località per il resto (eventi e smartbox non hanno regione). */
    public function place(Model $item): string
    {
        return (string) ($item->location ?? '') ?: '—';
    }

    public function regionName(Model $item): ?string
    {
        return $item instanceof Structure ? $item->region?->name : null;
    }

    /**
     * Prenotazioni valide sulla scheda: pagate online o confermate da pagare
     * in struttura. Contano entrambe, perché in entrambi i casi il cliente
     * si presenterà.
     *
     * @return array{total: int, future: int}
     */
    public function bookings(Model $item): array
    {
        $booked = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.purchasable_type', $this->family($item))
            ->where('order_items.purchasable_id', $item->getKey())
            ->whereIn('orders.status', array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::bookingStatuses()));

        return [
            'total' => (clone $booked)->count(),
            'future' => $this->futureBookingsQuery($item, $booked)->count(),
        ];
    }

    public function favoritesCount(Model $item): int
    {
        return DB::table('favorites')
            ->where('favoritable_type', $this->family($item))
            ->where('favoritable_id', $item->getKey())
            ->count();
    }

    public function averageRating(Model $item): ?float
    {
        $avg = DB::table('reviews')
            ->where('reviewable_type', $this->family($item))
            ->where('reviewable_id', $item->getKey())
            ->where('status', 'published')
            ->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function suspend(Model $item): void
    {
        $item->forceFill(['suspended_at' => now()])->save();
    }

    public function reactivate(Model $item): void
    {
        $item->forceFill(['suspended_at' => null])->save();
    }

    /**
     * Motivo per cui la scheda non si può cancellare, o null se si può.
     *
     * Bloccata con prenotazioni future (pagate o confermate in struttura):
     * devono restare onorabili e leggibili dal partner. Lo smartbox non ha una data: conta
     * come futura ogni vendita ancora dentro la validità del cofanetto.
     */
    public function deletionBlocker(Model $item): ?string
    {
        $future = $this->bookings($item)['future'];

        if ($future === 0) {
            return null;
        }

        return trans_choice('admin-catalog.blocker', $future);
    }

    /**
     * Cancellazione definitiva. Toglie anche ciò che la referenzia senza
     * vincolo di integrità (carrelli, preferiti, FAQ, recensioni, servizi) e
     * la bozza del partner, così "I miei servizi" non la ripubblica. Gli ordini
     * restano: order_items conserva titolo, foto e prezzo.
     *
     * @throws CatalogItemLocked
     */
    public function delete(Model $item): void
    {
        if (($blocker = $this->deletionBlocker($item)) !== null) {
            throw new CatalogItemLocked($blocker);
        }

        DB::transaction(function () use ($item): void {
            $family = $this->family($item);
            $id = $item->getKey();

            DB::table('cart_items')->where('purchasable_type', $family)->where('purchasable_id', $id)->delete();
            DB::table('favorites')->where('favoritable_type', $family)->where('favoritable_id', $id)->delete();
            DB::table('faqs')->where('faqable_type', $family)->where('faqable_id', $id)->delete();
            DB::table('reviews')->where('reviewable_type', $family)->where('reviewable_id', $id)->delete();

            $item->amenities()->detach();
            $draft = $item->draft;
            $item->delete();
            $draft?->delete();
        });
    }

    /**
     * Correzione dei campi pubblicati. Nome e descrizione (e il prezzo di
     * eventi e smartbox) finiscono anche nella bozza del partner: se il
     * partner ripubblica dal wizard, non cancella la correzione.
     *
     * Il prezzo di una struttura invece nasce dalle camere del wizard (la più
     * economica): la bozza non ha un campo in cui scriverlo, e una
     * ripubblicazione lo ricalcola. La scheda lo dice accanto al campo.
     *
     * @param  array{name: array<string, string>, description: array<string, string>, price_cents: ?int, supplement_cents?: ?int, region_id?: ?int, cancellation_policy_days?: ?int}  $data
     */
    public function update(Model $item, array $data): void
    {
        $nameColumn = self::FAMILIES[$this->family($item)][2];
        $name = array_filter($data['name'], fn ($value) => filled($value));
        $description = array_filter($data['description'], fn ($value) => filled($value));

        DB::transaction(function () use ($item, $data, $nameColumn, $name, $description): void {
            $item->setTranslations($nameColumn, $name);
            $item->setTranslations('description', $description);
            $item->cancellation_policy_days = $data['cancellation_policy_days'] ?? $item->cancellation_policy_days;

            if ($item instanceof Structure) {
                $item->price_cents = $data['price_cents'] ?? 0;
                $item->price_from_cents = $data['price_cents'] ?? 0;
                $item->animal_supplement_cents = $data['supplement_cents'] ?? 0;
                $item->region_id = $data['region_id'] ?? null;
            } elseif ($item instanceof Event) {
                $item->price_cents = $data['price_cents'];
                $item->is_free = ($data['price_cents'] ?? 0) === 0;
            } else {
                $item->price_cents = $data['price_cents'] ?? 0;
                $item->price_from_cents = $data['price_cents'] ?? 0;
            }

            $item->save();

            $draft = $item->draft;

            if ($draft !== null) {
                $draft->setTranslations('name', $name);
                $draft->setTranslations('description', $description);

                if ($item instanceof Event && $data['price_cents'] !== null) {
                    $draft->price_per_person = number_format($data['price_cents'] / 100, 2, '.', '');
                } elseif ($item instanceof SmartboxPackage) {
                    $draft->price = number_format(($data['price_cents'] ?? 0) / 100, 2, '.', '');
                }

                $draft->save();
            }
        });
    }

    /** Schede in attesa di approvazione, dalla più vecchia. */
    public function pendingApprovals(): Collection
    {
        return collect(self::FAMILIES)
            ->flatMap(fn (array $family) => $family[0]::withHidden()
                ->with(['user.partnerProfile'])
                ->where('approval_status', Structure::APPROVAL_PENDING)
                ->get())
            ->sortBy(fn (Model $item) => $item->approval_requested_at ?? $item->created_at)
            ->values();
    }

    public function approve(Model $item): void
    {
        $item->forceFill([
            'approval_status' => Structure::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approval_note' => null,
        ])->save();

        $this->notifyPartner($item, CatalogModerationMail::APPROVED);
    }

    /** "Chiedi modifiche": la scheda resta fuori dal sito finché il partner non la ripubblica. */
    public function requestChanges(Model $item, string $note): void
    {
        $item->forceFill([
            'approval_status' => Structure::APPROVAL_CHANGES_REQUESTED,
            'approval_note' => $note,
        ])->save();

        $this->notifyPartner($item, CatalogModerationMail::CHANGES_REQUESTED);
    }

    /** Le schede della piattaforma (senza partner) non hanno nessuno da avvisare. */
    private function notifyPartner(Model $item, string $outcome): void
    {
        $user = $item->user;

        if ($user === null) {
            return;
        }

        Mail::to($user->email)->send((new CatalogModerationMail(
            outcome: $outcome,
            partnerName: $user->first_name ?: $this->partnerName($item),
            itemName: $this->name($item),
            note: $item->approval_note,
            link: route('partner.services'),
        ))->locale('it'));
    }

    /**
     * @param  array{search?: string, partner?: string, family?: string, region?: string, status?: string}  $filters
     */
    private function union(array $filters): QueryBuilder
    {
        $parts = collect(self::FAMILIES)
            ->filter(fn (array $family, string $alias) => blank($filters['family'] ?? null) || $filters['family'] === $alias)
            // La regione esiste solo sulle strutture: filtrarla esclude le altre famiglie.
            ->filter(fn (array $family, string $alias) => blank($filters['region'] ?? null) || $alias === 'structure')
            ->map(fn (array $family, string $alias) => $this->part($alias, $family[1], $family[2], $filters))
            ->values();

        if ($parts->isEmpty()) {
            // Nessuna famiglia compatibile con i filtri: una select vuota con le stesse colonne.
            return $this->part('structure', 'structures', 'name', $filters)->whereRaw('1 = 0');
        }

        return $parts->slice(1)->reduce(fn (QueryBuilder $union, QueryBuilder $part) => $union->unionAll($part), $parts->first());
    }

    private function part(string $alias, string $table, string $nameColumn, array $filters): QueryBuilder
    {
        $query = DB::table($table)->select([
            DB::raw("'{$alias}' as family"),
            "{$table}.id",
            "{$table}.created_at",
        ]);

        if (filled($search = trim((string) ($filters['search'] ?? '')))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

            $query->where(function (QueryBuilder $query) use ($table, $nameColumn, $like): void {
                // Il nome è JSON {"it": …, "en": …}: il LIKE sul testo grezzo trova entrambe le lingue.
                $query->where("{$table}.{$nameColumn}", 'like', $like)
                    // Gli smartbox non hanno località.
                    ->when($table !== 'smartbox_packages', fn (QueryBuilder $q) => $q->orWhere("{$table}.location", 'like', $like))
                    ->orWhereIn("{$table}.user_id", DB::table('users')
                        ->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
                        ->where(fn (QueryBuilder $q) => $q
                            ->where('partner_profiles.business_name', 'like', $like)
                            ->orWhere('users.first_name', 'like', $like)
                            ->orWhere('users.last_name', 'like', $like))
                        ->select('users.id'));
            });
        }

        if (filled($filters['partner'] ?? null)) {
            $filters['partner'] === 'platform'
                ? $query->whereNull("{$table}.user_id")
                : $query->where("{$table}.user_id", (int) $filters['partner']);
        }

        if (filled($filters['region'] ?? null)) {
            $query->where("{$table}.region_id", (int) $filters['region']);
        }

        match ($filters['status'] ?? null) {
            self::STATUS_PUBLISHED => $query->where('approval_status', Structure::APPROVAL_APPROVED)->whereNull('suspended_at'),
            self::STATUS_SUSPENDED => $query->whereNotNull('suspended_at'),
            self::STATUS_PENDING => $query->where('approval_status', Structure::APPROVAL_PENDING),
            self::STATUS_CHANGES => $query->where('approval_status', Structure::APPROVAL_CHANGES_REQUESTED),
            default => null,
        };

        return $query;
    }

    /** Righe della UNION → model veri, nell'ordine della pagina. */
    private function hydrate(Collection $rows): Collection
    {
        $loaded = $rows->groupBy('family')->map(function (Collection $group, string $alias) {
            $with = ['user.partnerProfile'];

            if ($alias === 'structure') {
                $with[] = 'region';
            }

            return self::FAMILIES[$alias][0]::withHidden()->with($with)->findMany($group->pluck('id'))->keyBy('id');
        });

        return $rows
            ->map(fn (object $row) => $loaded[$row->family][$row->id] ?? null)
            ->filter()
            ->values();
    }

    /** $booked arriva già filtrato su OrderStatus::bookingStatuses() da bookings(). */
    private function futureBookingsQuery(Model $item, QueryBuilder $booked): QueryBuilder
    {
        $query = clone $booked;

        if ($item instanceof SmartboxPackage) {
            $months = (int) ($item->validity_months ?: 18);

            return $query->where('orders.created_at', '>=', now()->subMonths($months));
        }

        if ($item instanceof Event) {
            // Evento a data fissa: futuro finché non è finito.
            $end = $item->ends_at ?? $item->starts_at;

            return $end === null || $end->isFuture() ? $query : $query->whereRaw('1 = 0');
        }

        return $query->where(fn (QueryBuilder $q) => $q
            ->where('order_items.booked_until', '>=', now())
            ->orWhere(fn (QueryBuilder $q) => $q->whereNull('order_items.booked_until')->where('order_items.booked_from', '>=', now())));
    }
}
