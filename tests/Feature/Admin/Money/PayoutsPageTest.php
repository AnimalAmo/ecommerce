<?php

namespace Tests\Feature\Admin\Money;

use App\Enums\PayoutStatus;
use App\Livewire\Admin\Money\Payouts;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayout\OrderPayout;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayoutsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->travelTo(CarbonImmutable::parse('2026-09-21 08:00:00', 'UTC'));
    }

    public static function routes(): array
    {
        return [
            'incassi' => ['admin.payouts'],
            'esportazione' => ['admin.payouts.export'],
        ];
    }

    #[DataProvider('routes')]
    public function test_a_guest_is_sent_to_the_panel_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('routes')]
    public function test_a_customer_is_refused(string $route): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route($route))->assertForbidden();
    }

    #[DataProvider('routes')]
    public function test_a_partner_is_refused(string $route): void
    {
        $this->actingAsActivePartner();

        $this->get(route($route))->assertForbidden();
    }

    #[DataProvider('routes')]
    public function test_a_superadmin_is_let_in(string $route): void
    {
        $this->actingAsSuperadmin();

        $this->get(route($route))->assertOk();
    }

    public function test_the_page_shows_the_period_numbers_the_partners_and_the_transfers(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partner('Lamasu Wellness', 'acct_lamasu');

        $this->sale('2026-09-02 10:00', $partner, 10000, 1000, 8800, [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_1',
            'released_at' => $this->rome('2026-09-16 06:00'),
        ]);
        $this->sale('2026-09-12 10:00', $partner, 5000, 500, 4350, ['release_at' => $this->rome('2026-09-26 10:00')]);

        $this->get(route('admin.payouts'))
            ->assertOk()
            ->assertSee('Incassato a settembre')
            ->assertSee(Format::money(15000))
            ->assertSee(Format::money(13150))
            ->assertSee(Format::money(1500))
            ->assertSee('il primo matura il 26 set 2026')
            ->assertSee('Lamasu Wellness')
            ->assertSee('Emesso')
            ->assertSee('In maturazione')
            ->assertSee('https://dashboard.stripe.com/connect/accounts/acct_lamasu', false)
            ->assertSee(route('admin.payouts.export', ['period' => '2026-09']), false);
    }

    public function test_the_stripe_links_point_to_test_mode_with_a_test_key(): void
    {
        config(['payment.stripe.secret' => 'sk_test_123']);
        $this->actingAsSuperadmin();

        $this->sale('2026-09-02 10:00', $this->partner('Lamasu', 'acct_lamasu'), 1000, 0, 950, [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_1',
            'released_at' => $this->rome('2026-09-16 06:00'),
        ]);

        $this->get(route('admin.payouts'))
            ->assertSee('https://dashboard.stripe.com/test/connect/accounts/acct_lamasu', false);
    }

    public function test_choosing_another_month_changes_the_numbers(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partner('Lamasu Wellness', 'acct_lamasu');
        $this->sale('2026-09-02 10:00', $partner, 10000, 1000, 8800);
        $this->sale('2026-08-02 10:00', $this->partner('Cascina Brescia', 'acct_cascina'), 7700, 700, 6800);

        Livewire::test(Payouts::class)
            ->assertSet('period', '2026-09')
            ->assertSee('Lamasu Wellness')
            ->assertDontSee('Cascina Brescia')
            ->set('period', '2026-08')
            ->assertSee('Incassato ad agosto')
            ->assertSee('Cascina Brescia')
            ->assertDontSee('Lamasu Wellness')
            ->set('period', 'last-year-please')
            ->assertSet('period', '2026-09');
    }

    public function test_a_matured_payout_waiting_for_the_net_says_so(): void
    {
        $this->actingAsSuperadmin();
        $this->sale('2026-08-20 10:00', $this->partner('Lamasu', 'acct_lamasu'), 5000, 500, 4500, [
            'release_at' => $this->rome('2026-09-10 10:00'),
            'net_reconciled_at' => null,
        ]);

        $this->get(route('admin.payouts', ['period' => '2026-09']))
            ->assertSee('In attesa')
            ->assertSee('aspetta il netto confermato da Stripe');
    }

    public function test_stuck_payouts_raise_a_notice(): void
    {
        $this->actingAsSuperadmin();
        $this->sale('2026-06-02 10:00', $this->partner('Cascina', 'acct_cascina'), 2200, 200, 2000, [
            'status' => PayoutStatus::Failed,
            'failed_at' => $this->rome('2026-07-10 06:00'),
            'payout_attempts' => 5,
            'payout_idempotency_key' => 'payout-k1',
        ]);

        $this->get(route('admin.payouts'))
            ->assertSee('Un bonifico è fermo')
            ->assertSee(Format::money(2000));
    }

    public function test_the_export_lists_every_ledger_row_of_the_period(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partner('Lamasu Wellness', 'acct_lamasu');
        $sale = $this->sale('2026-09-02 10:00', $partner, 12345, 1234, 10800, [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_123',
            'released_at' => $this->rome('2026-09-16 06:00'),
        ]);
        $sale->orderItem->update(['title' => 'Hotel Brescia']);
        // Ordine di prima di Connect: nel lordo, senza divisione.
        $legacy = Order::factory()->guest()->paid()->create(['total_cents' => 5000, 'created_at' => $this->rome('2026-09-05 10:00')]);
        // Agosto: fuori.
        $this->sale('2026-08-30 10:00', $partner, 9900, 990, 8700);

        $response = $this->get(route('admin.payouts.export', ['period' => '2026-09']));

        $response->assertOk();
        $this->assertStringContainsString('incassi-2026-09.csv', (string) $response->headers->get('Content-Disposition'));

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $rows = array_map(
            fn (string $line): array => str_getcsv($line, ';', '"', ''),
            array_values(array_filter(explode("\n", $content))),
        );

        $this->assertCount(3, $rows, 'intestazione + due righe');
        $this->assertSame(['Data ordine', 'Ordine', 'Scheda', 'Partner', 'Account Stripe', 'Lordo'], array_slice($rows[0], 0, 6));
        $this->assertSame(
            ['02/09/2026 10:00', $sale->order->order_number, 'Hotel Brescia', 'Lamasu Wellness', 'acct_lamasu', '123,45', '12,34', '108,00', 'sì', 'Bonificato'],
            array_slice($rows[1], 0, 10),
        );
        $this->assertSame('po_123', $rows[1][12]);
        $this->assertSame(
            ['05/09/2026 10:00', $legacy->order_number, '', '', '', '50,00'],
            array_slice($rows[2], 0, 6),
        );
        $this->assertSame('Senza divisione (prima di Connect)', $rows[2][9]);
    }

    // --- dati costruiti a mano -------------------------------------------------

    private function rome(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($time, 'Europe/Rome')->utc();
    }

    private function sale(string $romeTime, User $partner, int $gross, int $commission, int $net, array $payout = []): OrderPayout
    {
        $at = $this->rome($romeTime);
        $order = Order::factory()->guest()->paid()->create(['total_cents' => $gross, 'created_at' => $at, 'updated_at' => $at]);
        $item = OrderItem::factory()->for($order)->create([
            'price_cents' => $gross,
            'purchasable_type' => null,
            'purchasable_id' => null,
            'partner_user_id' => $partner->id,
        ]);

        return OrderPayout::factory()->create(array_merge([
            'order_item_id' => $item->id,
            'partner_user_id' => $partner->id,
            'stripe_account_id' => $partner->partnerProfile->stripe_account_id,
            'gross_cents' => $gross,
            'commission_cents' => $commission,
            'net_cents' => $net,
            'net_reconciled_at' => $at,
        ], $payout));
    }

    private function partner(string $business, string $account): User
    {
        $user = User::factory()->create();
        PartnerProfile::factory()->connected()->for($user)->create([
            'business_name' => $business,
            'stripe_account_id' => $account,
        ]);

        return $user->load('partnerProfile');
    }
}
