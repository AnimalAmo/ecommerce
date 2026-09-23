<?php

namespace App\Services\Admin\Dashboard;

use App\Models\ContactMessage\ContactMessage;
use App\Models\Partner\PartnerApplication;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\AdminCounters;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Money\PayoutLedger;
use App\Services\Admin\Money\Period;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * I numeri della home del pannello: cosa c'è da fare, i riassunti del sito,
 * le ultime schede e cosa è arrivato dai moduli pubblici.
 *
 * Le definizioni che appartengono ad altri moduli stanno qui in un metodo
 * ciascuna, così si riallineano in un punto solo: "Iscritti" è la schermata
 * del modulo Persone (tutti gli account tranne i superadmin), "Ordini del
 * mese" è la stessa query di Incassi.
 */
class DashboardOverview
{
    public function __construct(
        private readonly AdminCounters $counters,
        private readonly PayoutLedger $ledger,
    ) {}

    /** 'morning' | 'afternoon' | 'evening', secondo l'ora italiana. */
    public function greeting(): string
    {
        $hour = $this->now()->hour;

        return match (true) {
            $hour >= 5 && $hour < 13 => 'morning',
            $hour >= 13 && $hour < 18 => 'afternoon',
            default => 'evening',
        };
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now(Period::timezone());
    }

    /**
     * I riquadri "da fare". Le approvazioni si mostrano solo a moderazione
     * accesa, o se qualcosa è rimasto in attesa da quando lo era: altrimenti
     * sarebbero uno zero fisso.
     *
     * @return array{approvals: int, oldestApprovalDays: ?int, showApprovals: bool, applications: int, messages: int, reviews: int, flaggedPosts: int}
     */
    public function todo(): array
    {
        $approvals = $this->counters->pendingApprovals();

        return [
            'approvals' => $approvals,
            'oldestApprovalDays' => $this->oldestApprovalDays(),
            'showApprovals' => (bool) config('admin.moderation') || $approvals > 0,
            'applications' => $this->counters->openApplications(),
            'messages' => $this->counters->openMessages(),
            'reviews' => $this->counters->pendingReviews(),
            'flaggedPosts' => $this->counters->flaggedPosts(),
        ];
    }

    /**
     * Schede visibili sul sito (approvate e non sospese) e schede sospese,
     * sulle tre famiglie. Query builder e non model: lo scope di visibilità
     * nasconderebbe proprio le sospese.
     *
     * @return array{published: int, suspended: int}
     */
    public function catalogCounts(): array
    {
        $counts = ['published' => 0, 'suspended' => 0];

        foreach (AdminCounters::CATALOG_TABLES as $table) {
            $counts['published'] += DB::table($table)
                ->where('approval_status', Structure::APPROVAL_APPROVED)
                ->whereNull('suspended_at')
                ->count();
            $counts['suspended'] += DB::table($table)->whereNotNull('suspended_at')->count();
        }

        return $counts;
    }

    /**
     * Iscritti: ogni account tranne i superadmin, come l'elenco "Iscritti" del
     * modulo Persone (UserDirectory, su un altro branch al momento della
     * scrittura). Se quella definizione cambia, cambia qui.
     *
     * @return array{total: int, recent: int}
     */
    public function subscribers(): array
    {
        $base = fn (): Builder => User::query()
            ->whereDoesntHave('roles', fn (Builder $role) => $role->where('name', 'superadmin'));

        return [
            'total' => $base()->count(),
            'recent' => $base()->where('users.created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /**
     * Partner attivi (ruolo partner, account attivo e non anonimizzato), e
     * quanti di loro non hanno nessuna scheda a catalogo, in qualunque stato:
     * sono i partner che si sono iscritti e non hanno mai pubblicato.
     *
     * @return array{active: int, withoutListings: int}
     */
    public function partners(): array
    {
        $active = fn (): Builder => User::query()
            ->whereHas('roles', fn (Builder $role) => $role->where('name', 'partner'))
            ->where('users.is_active', true)
            ->whereNull('users.anonymized_at');

        $withoutListings = $active();

        foreach (AdminCounters::CATALOG_TABLES as $table) {
            $withoutListings->whereNotExists(fn (QueryBuilder $query) => $query
                ->select(DB::raw(1))
                ->from($table)
                ->whereColumn("{$table}.user_id", 'users.id'));
        }

        return ['active' => $active()->count(), 'withoutListings' => $withoutListings->count()];
    }

    /**
     * Ordini pagati e incassato del mese in corso: la query di Incassi.
     *
     * @return array{orders: int, gross: int}
     */
    public function monthSales(): array
    {
        return $this->ledger->sales(Period::current());
    }

    /**
     * Prenotazioni del mese da pagare in struttura: la query di Incassi, fuori
     * da "Ordini del mese" perché quei soldi non passano da AnimalAmo.
     *
     * @return array{count: int, value_cents: int}
     */
    public function monthOnSiteBookings(): array
    {
        return $this->ledger->onSiteBookings(Period::current());
    }

    /**
     * Le ultime schede create, delle tre famiglie, anche sospese o in attesa:
     * il badge di stato dice il resto.
     *
     * @return Collection<int, Model>
     */
    public function latestListings(int $limit = 4): Collection
    {
        return collect(CatalogAdmin::FAMILIES)
            ->flatMap(function (array $family, string $alias) use ($limit): Collection {
                $with = $alias === 'structure' ? ['user.partnerProfile', 'region'] : ['user.partnerProfile'];

                return $family[0]::withHidden()
                    ->with($with)
                    ->latest('created_at')
                    ->latest('id')
                    ->limit($limit)
                    ->get();
            })
            ->sortByDesc(fn (Model $item): int => $item->created_at?->getTimestamp() ?? 0)
            ->take($limit)
            ->values();
    }

    /**
     * "Arrivato dal sito": messaggi del modulo contatti e candidature
     * partner, i più recenti, esclusi gli archiviati. Il link apre la voce
     * nella schermata Contatti e candidature (modulo Persone: `tab` e `id`
     * sono i suoi parametri di URL).
     *
     * @return Collection<int, array{kind: string, id: int, who: string, what: string, at: CarbonImmutable, url: string}>
     */
    public function inbox(int $limit = 4): Collection
    {
        $messages = ContactMessage::query()
            ->whereNull('archived_at')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (ContactMessage $message): array => [
                'kind' => 'message',
                'id' => (int) $message->id,
                'who' => trim($message->first_name.' '.$message->last_name),
                'what' => Str::limit(Str::squish((string) $message->message), 90),
                'at' => CarbonImmutable::instance($message->created_at),
                'url' => route('admin.inbox', ['id' => $message->id]),
            ]);

        $applications = PartnerApplication::query()
            ->whereNull('archived_at')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (PartnerApplication $application): array => [
                'kind' => 'application',
                'id' => (int) $application->id,
                'who' => trim($application->first_name.' '.$application->last_name),
                'what' => __('admin-dashboard.home.inbox.application_summary', [
                    'business' => $application->business_name,
                    'offer' => $application->offer_type,
                    'city' => $application->city,
                ]),
                'at' => CarbonImmutable::instance($application->created_at),
                'url' => route('admin.inbox', ['tab' => 'applications', 'id' => $application->id]),
            ]);

        return $messages
            ->concat($applications)
            ->sortByDesc(fn (array $entry): int => $entry['at']->getTimestamp())
            ->take($limit)
            ->values();
    }

    /** Giorni di attesa della scheda più vecchia da approvare, o null se non ce n'è. */
    private function oldestApprovalDays(): ?int
    {
        $oldest = collect(AdminCounters::CATALOG_TABLES)
            ->map(fn (string $table) => DB::table($table)
                ->where('approval_status', Structure::APPROVAL_PENDING)
                ->min(DB::raw('coalesce(approval_requested_at, created_at)')))
            ->filter()
            ->min();

        if ($oldest === null) {
            return null;
        }

        return (int) floor(CarbonImmutable::parse($oldest, (string) config('app.timezone'))->diffInDays(now()));
    }
}
