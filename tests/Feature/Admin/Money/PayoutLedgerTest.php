<?php

namespace Tests\Feature\Admin\Money;

use App\Enums\OrderStatus;
use App\Enums\PayoutStatus;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayout\OrderPayout;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Admin\Money\PayoutLedger;
use App\Services\Admin\Money\Period;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * I numeri di Incassi, costruiti a mano. Le date si scrivono in ora italiana
 * (il mese della cliente) e si salvano in UTC come fa l'applicazione.
 */
class PayoutLedgerTest extends TestCase
{
    use RefreshDatabase;

    private PayoutLedger $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        // 21 settembre 2026, mattina in Italia.
        $this->travelTo(CarbonImmutable::parse('2026-09-21 08:00:00', 'UTC'));
        $this->ledger = app(PayoutLedger::class);
    }

    public function test_sales_count_the_paid_orders_inside_the_italian_month_edges(): void
    {
        $this->order('2026-08-31 23:59:59', 100);
        $this->order('2026-09-01 00:00:00', 1000);
        $this->order('2026-09-30 23:59:59', 2000);
        $this->order('2026-10-01 00:00:00', 400);
        $this->order('2026-09-10 12:00:00', 800, OrderStatus::Pending);
        $this->order('2026-09-10 12:00:00', 1600, OrderStatus::Cancelled);

        $this->assertSame(['orders' => 2, 'gross' => 3000], $this->ledger->sales(Period::fromKey('2026-09')));
        $this->assertSame(['orders' => 1, 'gross' => 100], $this->ledger->sales(Period::fromKey('2026-08')));
    }

    public function test_totals_split_the_period_between_partners_platform_and_what_is_left_to_pay(): void
    {
        $a = $this->partner('Lamasu Wellness');
        $b = $this->partner('Cascina Brescia');

        $this->sale('2026-09-02 10:00', $a, 10000, 1000, 8800, [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_1',
            'released_at' => $this->rome('2026-09-16 06:00'),
        ]);
        $this->sale('2026-09-12 10:00', $a, 5000, 500, 4350, ['release_at' => $this->rome('2026-10-05 10:00')]);
        $this->sale('2026-09-14 10:00', $b, 3000, 0, 2900, [
            'status' => PayoutStatus::Failed,
            'failed_at' => $this->rome('2026-09-20 06:00'),
            'payout_attempts' => 5,
        ]);
        // Riga di piattaforma: nessun partner, l'incasso è tutto di AnimalAmo.
        $this->sale('2026-09-15 10:00', null, 2000, 0, 2000, ['status' => PayoutStatus::PlatformOnly]);
        // Fuori periodo: non deve entrare in nessun numero.
        $this->sale('2026-08-20 10:00', $a, 7000, 700, 6100);

        $totals = $this->ledger->totals(Period::fromKey('2026-09'));

        $this->assertSame(4, $totals['orders']);
        $this->assertSame(20000, $totals['gross']);
        $this->assertSame(8800 + 4350 + 2900, $totals['partners']);
        $this->assertSame(1500, $totals['platform']);
        $this->assertSame(2000, $totals['directSales']);
        $this->assertSame(4350 + 2900, $totals['toRelease']);
        $this->assertSame(0, $totals['provisional']);
        $this->assertSame(0, $totals['unsplitOrders']);
        $this->assertSame('2026-10-05', $totals['nextRelease']?->setTimezone('Europe/Rome')->toDateString());
    }

    public function test_a_row_not_yet_reconciled_is_reported_as_provisional(): void
    {
        $this->sale('2026-09-12 10:00', $this->partner('Lamasu'), 5000, 500, 4500, ['net_reconciled_at' => null]);
        $this->sale('2026-09-13 10:00', $this->partner('Cascina'), 5000, 500, 4350);

        $this->assertSame(1, $this->ledger->totals(Period::fromKey('2026-09'))['provisional']);
    }

    public function test_orders_before_connect_are_in_the_gross_but_not_in_the_split(): void
    {
        $this->order('2026-09-03 10:00', 12000);
        $this->sale('2026-09-12 10:00', $this->partner('Lamasu'), 5000, 500, 4350);

        $totals = $this->ledger->totals(Period::fromKey('2026-09'));

        $this->assertSame(17000, $totals['gross']);
        $this->assertSame(4350, $totals['partners']);
        $this->assertSame(1, $totals['unsplitOrders']);
    }

    public function test_by_partner_groups_the_rows_and_names_the_business(): void
    {
        $a = $this->partner('Lamasu Wellness');
        $b = $this->partner('Cascina Brescia');

        // Due righe dello stesso ordine contano un ordine solo.
        $order = $this->sale('2026-09-02 10:00', $a, 6000, 600, 5200)->order;
        $item = OrderItem::factory()->for($order)->create(['price_cents' => 4000, 'purchasable_type' => null, 'purchasable_id' => null, 'partner_user_id' => $a->id]);
        OrderPayout::factory()->create([
            'order_item_id' => $item->id,
            'partner_user_id' => $a->id,
            'gross_cents' => 4000, 'commission_cents' => 400, 'net_cents' => 3500,
            'net_reconciled_at' => now(),
        ]);
        $this->sale('2026-09-05 10:00', $a, 1000, 0, 950);
        $this->sale('2026-09-06 10:00', $b, 20000, 2000, 17600, ['net_reconciled_at' => null]);
        $this->sale('2026-09-07 10:00', null, 2000, 0, 2000, ['status' => PayoutStatus::PlatformOnly]);

        $rows = $this->ledger->byPartner(Period::fromKey('2026-09'));

        $this->assertCount(2, $rows, 'la vendita diretta di AnimalAmo non è un partner');
        $this->assertSame(['Cascina Brescia', 'Lamasu Wellness'], $rows->pluck('name')->all(), 'dal più venduto');
        $this->assertSame(
            ['orders' => 2, 'gross' => 11000, 'net' => 9650, 'commission' => 1000, 'provisional' => false],
            collect($rows[1])->only(['orders', 'gross', 'net', 'commission', 'provisional'])->all(),
        );
        $this->assertTrue($rows[0]['provisional']);
    }

    public function test_transfers_group_the_rows_of_the_period_by_payout_with_their_state(): void
    {
        $a = $this->partner('Lamasu Wellness', payable: true);
        $b = $this->partner('Cascina Brescia', payable: true);
        $c = $this->partner('Tre Capitelli', payable: false);

        // Emesso: due righe, un payout solo.
        foreach ([3000, 2000] as $net) {
            $this->sale('2026-08-25 10:00', $a, $net + 300, 300, $net, [
                'stripe_account_id' => 'acct_lamasu',
                'status' => PayoutStatus::Released,
                'stripe_payout_id' => 'po_sept',
                'released_at' => $this->rome('2026-09-15 08:00'),
            ]);
        }
        // Emesso ad agosto: fuori dal periodo.
        $this->sale('2026-07-25 10:00', $a, 1100, 100, 1000, [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_aug',
            'released_at' => $this->rome('2026-08-10 08:00'),
        ]);
        // Fallito per sempre.
        $this->sale('2026-08-26 10:00', $b, 2200, 200, 2000, [
            'stripe_account_id' => 'acct_cascina',
            'status' => PayoutStatus::Failed,
            'failed_at' => $this->rome('2026-09-18 08:00'),
            'payout_attempts' => 5,
            'payout_idempotency_key' => 'payout-k1',
            'last_error' => 'Insufficient funds in Stripe account.',
        ]);
        // Fallito una volta: il giro di domani riprova.
        $this->sale('2026-08-27 10:00', $b, 1100, 100, 1000, [
            'stripe_account_id' => 'acct_cascina',
            'failed_at' => $this->rome('2026-09-19 08:00'),
            'release_at' => $this->rome('2026-09-12 10:00'),
            'payout_attempts' => 2,
            'payout_idempotency_key' => 'payout-k2',
            'last_error' => 'Rate limit.',
        ]);
        // Matura più avanti nel mese.
        $this->sale('2026-09-14 10:00', $a, 4400, 400, 4000, [
            'stripe_account_id' => 'acct_lamasu',
            'release_at' => $this->rome('2026-09-28 10:00'),
        ]);
        // Matura, ma aspetta il netto vero.
        $this->sale('2026-08-28 10:00', $a, 550, 50, 500, [
            'stripe_account_id' => 'acct_lamasu',
            'release_at' => $this->rome('2026-09-11 10:00'),
            'net_reconciled_at' => null,
        ]);
        // Matura, ma il partner non può ricevere bonifici.
        $this->sale('2026-08-28 11:00', $c, 660, 60, 600, [
            'stripe_account_id' => 'acct_capitelli',
            'release_at' => $this->rome('2026-09-11 11:00'),
        ]);
        // Matura a ottobre: fuori dal periodo.
        $this->sale('2026-09-20 10:00', $a, 770, 70, 700, ['release_at' => $this->rome('2026-10-04 10:00')]);

        $transfers = $this->ledger->transfers(Period::fromKey('2026-09'));

        $this->assertSame(
            ['scheduled', 'retrying', 'failed', 'released', 'waiting', 'waiting'],
            $transfers->pluck('state')->all(),
            'dalla data più recente',
        );

        $released = $transfers->firstWhere('state', 'released');
        $this->assertSame(5000, $released['amount']);
        $this->assertSame(2, $released['rows']);
        $this->assertSame('Lamasu Wellness', $released['partner']);
        $this->assertSame('acct_lamasu', $released['stripeAccount']);

        $failed = $transfers->firstWhere('state', 'failed');
        $this->assertSame(5, $failed['attempts']);
        $this->assertSame('Insufficient funds in Stripe account.', $failed['error']);

        $this->assertSame(2, $transfers->firstWhere('state', 'retrying')['attempts']);
        $this->assertSame(
            ['not_payable', 'provisional'],
            $transfers->where('state', 'waiting')->pluck('reason')->values()->all(),
        );
    }

    public function test_a_payout_with_an_unknown_outcome_is_flagged(): void
    {
        $this->sale('2026-08-26 10:00', $this->partner('Cascina'), 2200, 200, 2000, [
            'status' => PayoutStatus::Failed,
            'failed_at' => $this->rome('2026-09-18 08:00'),
            'payout_idempotency_key' => 'payout-k1',
            'last_error' => 'ESITO IGNOTO: Could not connect to Stripe.',
        ]);

        $this->assertTrue($this->ledger->transfers(Period::fromKey('2026-09'))->first()['uncertain']);
    }

    public function test_stuck_payouts_are_counted_whatever_the_period(): void
    {
        $partner = $this->partner('Cascina');

        foreach (['2026-07-10' => 'k-july', '2026-09-18' => 'k-sept'] as $day => $key) {
            foreach ([1000, 500] as $net) {
                $this->sale('2026-06-01 10:00', $partner, $net + 100, 100, $net, [
                    'status' => PayoutStatus::Failed,
                    'failed_at' => $this->rome($day.' 08:00'),
                    'payout_idempotency_key' => $key,
                ]);
            }
        }

        $this->assertSame(['groups' => 2, 'amount' => 3000], $this->ledger->stuck());
    }

    public function test_period_options_run_from_the_first_paid_order_to_this_month(): void
    {
        $this->order('2026-05-10 10:00', 1000, OrderStatus::Pending);
        $this->order('2026-06-30 23:30', 1000);

        $this->assertSame(
            ['2026-09', '2026-08', '2026-07', '2026-06', Period::LAST_12_MONTHS],
            array_keys($this->ledger->periodOptions()),
        );
        $this->assertSame('Settembre 2026', $this->ledger->periodOptions()['2026-09']);
    }

    public function test_period_options_also_start_from_a_first_on_site_booking(): void
    {
        // Partenza con soli partner offline: la prima prenotazione è in struttura, il primo ordine pagato arriva dopo.
        $this->onSiteOrder('2026-06-15 10:00', 9000);
        $this->order('2026-08-10 10:00', 1000);

        $this->assertSame(
            ['2026-09', '2026-08', '2026-07', '2026-06', Period::LAST_12_MONTHS],
            array_keys($this->ledger->periodOptions()),
        );
    }

    public function test_an_unknown_or_future_period_falls_back_to_this_month(): void
    {
        foreach (['2026-13', '2026-10', 'abc', '', null] as $key) {
            $this->assertSame('2026-09', Period::fromKey($key)->key, var_export($key, true));
        }

        $year = Period::fromKey(Period::LAST_12_MONTHS);
        $this->assertSame('2025-10-01 00:00:00', $year->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-30 23:59:59', $year->end->format('Y-m-d H:i:s'));
    }

    public function test_an_on_site_booking_stays_out_of_every_ledger_number(): void
    {
        $this->sale('2026-09-12 10:00', $this->partner('Lamasu'), 5000, 500, 4350);
        $before = $this->ledger->totals(Period::fromKey('2026-09'));

        $this->onSiteOrder('2026-09-14 10:00', 9000);

        // Nessun soldo è passato da AnimalAmo: incassato, divisione e "precedenti a Connect" non cambiano.
        $this->assertEquals($before, $this->ledger->totals(Period::fromKey('2026-09')));
        $this->assertSame(['orders' => 1, 'gross' => 5000], $this->ledger->sales(Period::fromKey('2026-09')));
    }

    public function test_on_site_bookings_are_counted_apart_by_month(): void
    {
        $this->onSiteOrder('2026-08-31 23:59:59', 1000);
        $this->onSiteOrder('2026-09-01 00:00:00', 9000);
        $this->onSiteOrder('2026-09-20 18:00:00', 3000);
        $this->order('2026-09-10 12:00:00', 4000);

        $this->assertSame(['count' => 2, 'value_cents' => 12000], $this->ledger->onSiteBookings(Period::fromKey('2026-09')));
        // Accetta anche un giorno qualunque del mese (CarbonInterface).
        $this->assertSame(['count' => 1, 'value_cents' => 1000], $this->ledger->onSiteBookings(CarbonImmutable::parse('2026-08-15 12:00', 'Europe/Rome')));
    }

    // --- dati costruiti a mano -------------------------------------------------

    private function rome(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($time, 'Europe/Rome')->utc();
    }

    private function order(string $romeTime, int $total, OrderStatus $status = OrderStatus::Paid): Order
    {
        $at = $this->rome($romeTime);

        return Order::factory()->guest()->create([
            'status' => $status,
            'total_cents' => $total,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /** Una vendita pagata con la sua riga di registro. */
    private function sale(string $romeTime, ?User $partner, int $gross, int $commission, int $net, array $payout = []): OrderPayout
    {
        $order = $this->order($romeTime, $gross);
        $item = OrderItem::factory()->for($order)->create([
            'price_cents' => $gross,
            'purchasable_type' => null,
            'purchasable_id' => null,
            'partner_user_id' => $partner?->id,
        ]);

        return OrderPayout::factory()->create(array_merge([
            'order_item_id' => $item->id,
            'partner_user_id' => $partner?->id,
            'stripe_account_id' => $partner?->partnerProfile?->stripe_account_id,
            'gross_cents' => $gross,
            'commission_cents' => $commission,
            'net_cents' => $net,
            'net_reconciled_at' => $order->created_at,
        ], $payout));
    }

    private function partner(string $business, bool $payable = true): User
    {
        $user = User::factory()->create();

        PartnerProfile::factory()->connected()->for($user)->create([
            'business_name' => $business,
            'stripe_payouts_enabled' => $payable,
        ]);

        return $user->load('partnerProfile');
    }

    /** Prenotazione confermata da pagare in struttura, senza pagamento né registro. */
    private function onSiteOrder(string $romeTime, int $total): Order
    {
        $at = $this->rome($romeTime);

        return Order::factory()->guest()->onSite()->create([
            'total_cents' => $total,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}
