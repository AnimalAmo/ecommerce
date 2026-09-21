<?php

namespace App\Services\Admin\Money;

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Models\Order\Order;
use App\Models\OrderPayout\OrderPayout;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Incassi visti dal pannello: quanto è entrato in un periodo, come si è diviso
 * fra partner e AnimalAmo, e a che punto sono i bonifici.
 *
 * Legge `orders` e il registro `order_payouts` e non lo modifica mai: le righe
 * le scrivono il checkout (CreateOrderPayoutsPipe), `payouts:reconcile-net`
 * (il netto vero), `payouts:release` (il bonifico) e `payouts:retry`.
 *
 * Due cose da non inventare, perché il registro non le dice:
 *  - una riga con `net_reconciled_at` nullo ha un netto PROVVISORIO (lordo meno
 *    provvigione, o quello letto al capture): il numero giusto arriva dalla
 *    balance transaction, e lo si dice invece di spacciarlo per definitivo;
 *  - "emesso" è l'unico esito che conosciamo di un bonifico: Stripe ha creato
 *    il payout. Se poi sia arrivato in banca lo sa solo la Dashboard di Stripe.
 *
 * Il periodo si ancora alla data dell'ordine per i numeri di vendita, e alla
 * data dell'evento (emissione, fallimento, maturazione) per i bonifici.
 */
class PayoutLedger
{
    public const RELEASED = 'released';

    public const SCHEDULED = 'scheduled';

    public const WAITING = 'waiting';

    public const RETRYING = 'retrying';

    public const FAILED = 'failed';

    /** Mesi proposti nel selettore, al massimo. */
    private const MAX_MONTHS = 24;

    /**
     * Ordini pagati del periodo e il loro incasso. La home usa questo stesso
     * metodo per "Ordini del mese": i due numeri non possono divergere.
     *
     * @return array{orders: int, gross: int}
     */
    public function sales(Period $period): array
    {
        $row = $this->paidOrders($period)
            ->selectRaw('count(*) as orders, coalesce(sum(total_cents), 0) as gross')
            ->first();

        return ['orders' => (int) $row->orders, 'gross' => (int) $row->gross];
    }

    /**
     * I numeri riassuntivi del periodo.
     *
     * - `partners`: il netto delle righe dei partner (quanto resta sul loro
     *   saldo Stripe dopo provvigione e commissioni Stripe);
     * - `platform`: le provvigioni (application fee) sulle righe dei partner;
     * - `directSales`: le righe `platform_only`, vendute da AnimalAmo senza
     *   partner: non sono provvigioni, si dicono a parte;
     * - `toRelease`: il netto non ancora bonificato (in attesa o fallito);
     * - `unsplitOrders`: ordini pagati senza righe di registro, cioè precedenti
     *   ai pagamenti divisi: stanno nel lordo e in nessun altro numero.
     *
     * @return array{orders: int, gross: int, partnersGross: int, partners: int, platform: int, directSales: int, toRelease: int, provisional: int, unsplitOrders: int, nextRelease: ?CarbonImmutable}
     */
    public function totals(Period $period): array
    {
        $split = $this->partnerRows($period)
            ->selectRaw('coalesce(sum(order_payouts.gross_cents), 0) as gross')
            ->selectRaw('coalesce(sum(order_payouts.net_cents), 0) as net')
            ->selectRaw('coalesce(sum(order_payouts.commission_cents), 0) as commission')
            ->first();

        $pending = fn (): QueryBuilder => $this->partnerRows($period)->where('order_payouts.status', PayoutStatus::Pending->value);
        $nextRelease = $pending()->min('order_payouts.release_at');

        return [
            ...$this->sales($period),
            'partnersGross' => (int) $split->gross,
            'partners' => (int) $split->net,
            'platform' => (int) $split->commission,
            'directSales' => (int) $this->ledgerRows($period)
                ->where('order_payouts.status', PayoutStatus::PlatformOnly->value)
                ->sum('order_payouts.gross_cents'),
            'toRelease' => (int) $this->partnerRows($period)
                ->whereIn('order_payouts.status', [PayoutStatus::Pending->value, PayoutStatus::Failed->value])
                ->sum('order_payouts.net_cents'),
            'provisional' => $pending()->whereNull('order_payouts.net_reconciled_at')->count(),
            'unsplitOrders' => $this->paidOrders($period)
                ->whereNotExists(fn (QueryBuilder $query) => $query
                    ->select(DB::raw(1))
                    ->from('order_payouts')
                    ->whereColumn('order_payouts.order_id', 'orders.id'))
                ->count(),
            'nextRelease' => $nextRelease === null ? null : CarbonImmutable::parse($nextRelease, (string) config('app.timezone')),
        ];
    }

    /**
     * Venduto, netto e provvigione per partner, dal più venduto.
     *
     * @return Collection<int, array{partnerId: int, name: string, orders: int, gross: int, net: int, commission: int, provisional: bool, stripeAccount: ?string}>
     */
    public function byPartner(Period $period): Collection
    {
        $rows = $this->partnerRows($period)
            ->groupBy('order_payouts.partner_user_id')
            ->select('order_payouts.partner_user_id')
            ->selectRaw('count(distinct order_payouts.order_id) as orders')
            ->selectRaw('sum(order_payouts.gross_cents) as gross')
            ->selectRaw('sum(order_payouts.net_cents) as net')
            ->selectRaw('sum(order_payouts.commission_cents) as commission')
            ->selectRaw(
                'sum(case when order_payouts.status = ? and order_payouts.net_reconciled_at is null then 1 else 0 end) as provisional',
                [PayoutStatus::Pending->value],
            )
            ->selectRaw('max(order_payouts.stripe_account_id) as stripe_account')
            ->orderByDesc('gross')
            ->get();

        $names = $this->partnerNames($rows->pluck('partner_user_id'));

        return $rows->map(fn (object $row): array => [
            'partnerId' => (int) $row->partner_user_id,
            'name' => $names[$row->partner_user_id] ?? '—',
            'orders' => (int) $row->orders,
            'gross' => (int) $row->gross,
            'net' => (int) $row->net,
            'commission' => (int) $row->commission,
            'provisional' => (int) $row->provisional > 0,
            'stripeAccount' => $row->stripe_account,
        ])->values();
    }

    /**
     * I bonifici del periodo, raggruppati come li raggruppa `payouts:release`:
     * le righe emesse insieme condividono lo `stripe_payout_id`, quelle
     * tentate insieme la chiave di idempotenza. Le righe mai tentate si
     * raggruppano per account e giorno di maturazione — un'approssimazione:
     * il bonifico vero le unirà a quelle mature nello stesso giro.
     *
     * Ogni gruppo sta nel periodo della sua data: emissione, ultimo
     * fallimento o maturazione. Dalla più recente.
     *
     * @return Collection<int, array{key: string, state: string, partner: string, stripeAccount: ?string, amount: int, rows: int, date: CarbonImmutable, attempts: int, error: ?string, uncertain: bool, reason: ?string}>
     */
    public function transfers(Period $period): Collection
    {
        [$from, $to] = $period->bounds();

        return OrderPayout::query()
            ->with('partner.partnerProfile')
            ->where('status', '!=', PayoutStatus::PlatformOnly->value)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $q) => $q
                    ->where('status', PayoutStatus::Released->value)
                    ->whereBetween('released_at', [$from, $to]))
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', PayoutStatus::Failed->value)
                    ->whereBetween('failed_at', [$from, $to]))
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', PayoutStatus::Pending->value)
                    ->where('payout_attempts', '>', 0)
                    ->whereBetween('failed_at', [$from, $to]))
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', PayoutStatus::Pending->value)
                    ->where('payout_attempts', 0)
                    ->whereBetween('release_at', [$from, $to])))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (OrderPayout $row): string => $this->transferKey($row))
            ->map(fn (Collection $rows, string $key): array => $this->transfer($key, $rows))
            ->sortByDesc(fn (array $transfer): int => $transfer['date']->getTimestamp())
            ->values();
    }

    /**
     * Bonifici fermi, di qualunque periodo: `payouts:release` li ha dichiarati
     * falliti e non ci riprova più da solo. Serve una mano umana.
     *
     * @return array{groups: int, amount: int}
     */
    public function stuck(): array
    {
        $failed = OrderPayout::query()
            ->where('status', PayoutStatus::Failed->value)
            ->get(['id', 'partner_user_id', 'stripe_account_id', 'payout_idempotency_key', 'net_cents']);

        return [
            'groups' => $failed->groupBy(fn (OrderPayout $row): string => $row->payout_idempotency_key
                ?? $row->stripe_account_id
                ?? 'partner-'.$row->partner_user_id)->count(),
            'amount' => (int) $failed->sum('net_cents'),
        ];
    }

    /**
     * Voci del selettore: i mesi dal primo ordine pagato a oggi, poi "Ultimi
     * 12 mesi". Un mese scelto a mano dall'URL e più vecchio resta in elenco.
     *
     * @return array<string, string>
     */
    public function periodOptions(?Period $selected = null): array
    {
        $first = DB::table('orders')->where('status', OrderStatus::Paid->value)->min('created_at');
        $current = Period::current();
        $oldest = $first === null
            ? $current->start
            : CarbonImmutable::parse($first, (string) config('app.timezone'))->setTimezone(Period::timezone())->startOfMonth();

        $options = [];

        for ($month = $current->start; $month->greaterThanOrEqualTo($oldest) && count($options) < self::MAX_MONTHS; $month = $month->subMonthNoOverflow()) {
            $period = Period::month($month);
            $options[$period->key] = $period->label();
        }

        if ($selected !== null && ! isset($options[$selected->key]) && $selected->isMonth()) {
            $options[$selected->key] = $selected->label();
        }

        $options[Period::LAST_12_MONTHS] = Period::lastTwelveMonths()->label();

        return $options;
    }

    /**
     * Le righe di registro degli ordini pagati nel periodo, per l'esportazione.
     *
     * @return Collection<int, OrderPayout>
     */
    public function ledgerFor(Period $period): Collection
    {
        [$from, $to] = $period->bounds();

        return OrderPayout::query()
            ->with(['order', 'orderItem', 'partner.partnerProfile'])
            ->whereHas('order', fn (Builder $query) => $query
                ->where('status', OrderStatus::Paid->value)
                ->whereBetween('created_at', [$from, $to]))
            ->orderBy('order_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Ordini pagati del periodo senza righe di registro (precedenti ai
     * pagamenti divisi).
     *
     * @return Collection<int, Order>
     */
    public function unsplitOrdersFor(Period $period): Collection
    {
        [$from, $to] = $period->bounds();

        return Order::query()
            ->where('status', OrderStatus::Paid->value)
            ->whereBetween('created_at', [$from, $to])
            ->whereDoesntHave('payouts')
            ->orderBy('id')
            ->get();
    }

    /** Nome da mostrare per il beneficiario di una riga: ragione sociale, o nome e cognome. */
    public function partnerLabel(OrderPayout $row): string
    {
        return $row->partner?->partnerProfile?->business_name ?: ($row->partner?->name ?: '—');
    }

    private function paidOrders(Period $period): QueryBuilder
    {
        return DB::table('orders')
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereBetween('orders.created_at', $period->bounds());
    }

    /** Righe di registro degli ordini pagati nel periodo. */
    private function ledgerRows(Period $period): QueryBuilder
    {
        return DB::table('order_payouts')
            ->join('orders', 'orders.id', '=', 'order_payouts.order_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereBetween('orders.created_at', $period->bounds());
    }

    /** Solo le righe che hanno un partner: le `platform_only` restano fuori. */
    private function partnerRows(Period $period): QueryBuilder
    {
        return $this->ledgerRows($period)->where('order_payouts.status', '!=', PayoutStatus::PlatformOnly->value);
    }

    /** @return array<int, string> */
    private function partnerNames(Collection $ids): array
    {
        return DB::table('users')
            ->leftJoin('partner_profiles', 'partner_profiles.user_id', '=', 'users.id')
            ->whereIn('users.id', $ids->filter()->all())
            ->get(['users.id', 'users.first_name', 'users.last_name', 'partner_profiles.business_name'])
            ->mapWithKeys(fn (object $row): array => [
                $row->id => $row->business_name ?: trim($row->first_name.' '.$row->last_name),
            ])
            ->all();
    }

    private function transferState(OrderPayout $row): string
    {
        return match (true) {
            $row->status === PayoutStatus::Released => self::RELEASED,
            $row->status === PayoutStatus::Failed => self::FAILED,
            $row->payout_attempts > 0 => self::RETRYING,
            $row->release_at?->isFuture() === true => self::SCHEDULED,
            default => self::WAITING,
        };
    }

    private function transferKey(OrderPayout $row): string
    {
        $state = $this->transferState($row);
        $account = $row->stripe_account_id ?? 'partner-'.$row->partner_user_id;

        return $state.':'.match ($state) {
            self::RELEASED => $row->stripe_payout_id,
            self::FAILED, self::RETRYING => $row->payout_idempotency_key ?? $account,
            default => $account.':'.$row->release_at?->setTimezone(Period::timezone())->toDateString(),
        };
    }

    /**
     * @param  Collection<int, OrderPayout>  $rows
     * @return array{key: string, state: string, partner: string, stripeAccount: ?string, amount: int, rows: int, date: CarbonImmutable, attempts: int, error: ?string, uncertain: bool, reason: ?string}
     */
    private function transfer(string $key, Collection $rows): array
    {
        /** @var OrderPayout $first */
        $first = $rows->first();
        $state = $this->transferState($first);

        $date = match ($state) {
            self::RELEASED => $first->released_at,
            self::FAILED, self::RETRYING => $first->failed_at,
            default => $first->release_at,
        } ?? $first->updated_at;

        $failing = in_array($state, [self::FAILED, self::RETRYING], true);

        return [
            'key' => $key,
            'state' => $state,
            'partner' => $this->partnerLabel($first),
            'stripeAccount' => $first->stripe_account_id,
            'amount' => (int) $rows->sum('net_cents'),
            'rows' => $rows->count(),
            'date' => CarbonImmutable::instance($date),
            'attempts' => (int) $rows->max('payout_attempts'),
            'error' => $failing ? $first->last_error : null,
            // Il prefisso lo scrive ReleaseMaturedPayouts::recordFailure quando
            // Stripe non ha risposto: il bonifico può essere partito o no.
            'uncertain' => $state === self::FAILED && str_starts_with((string) $first->last_error, 'ESITO IGNOTO'),
            'reason' => $state === self::WAITING ? $this->waitingReason($rows) : null,
        ];
    }

    /**
     * Perché una riga matura non è ancora partita: le tre condizioni che
     * `payouts:release` pretende (account Stripe, account abilitato ai
     * bonifici, netto confermato). Se valgono tutte, la paga il giro del
     * mattino dopo.
     *
     * @param  Collection<int, OrderPayout>  $rows
     */
    private function waitingReason(Collection $rows): string
    {
        /** @var OrderPayout $first */
        $first = $rows->first();

        return match (true) {
            $first->stripe_account_id === null => 'no_account',
            $first->partner?->partnerProfile?->canBePaid() !== true => 'not_payable',
            $rows->contains(fn (OrderPayout $row): bool => $row->net_reconciled_at === null) => 'provisional',
            default => 'next_run',
        };
    }
}
