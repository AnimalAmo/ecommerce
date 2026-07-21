<?php

namespace Tests\Unit\Payment;

use App\Models\User;
use App\Services\Payment\SavedPaymentMethodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Service\CustomerService;
use Stripe\Service\PaymentMethodService;
use Stripe\Service\SetupIntentService;
use Stripe\SetupIntent;
use Stripe\StripeClient;
use Tests\TestCase;

class SavedPaymentMethodServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $setupIntents;

    private MockInterface $paymentMethods;

    private MockInterface $customers;

    private SavedPaymentMethodService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupIntents = Mockery::mock(SetupIntentService::class);
        $this->paymentMethods = Mockery::mock(PaymentMethodService::class);
        $this->customers = Mockery::mock(CustomerService::class);

        // StripeClient::__get delega a getService(): basta stubbare quello.
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('setupIntents')->andReturn($this->setupIntents);
        $client->shouldReceive('getService')->with('paymentMethods')->andReturn($this->paymentMethods);
        $client->shouldReceive('getService')->with('customers')->andReturn($this->customers);

        $this->service = new SavedPaymentMethodService($client);
    }

    // ── SetupIntent ─────────────────────────────────────────────────────

    public function test_setup_intent_creates_the_customer_on_first_use(): void
    {
        $user = User::factory()->create(['email' => 'giulia@example.com']);

        $this->customers->shouldReceive('create')
            ->once()
            ->andReturn(Customer::constructFrom(['id' => 'cus_new']));

        $this->setupIntents->shouldReceive('create')
            ->once()
            ->with([
                'customer' => 'cus_new',
                'payment_method_types' => ['card'],
                'usage' => 'off_session',
            ])
            ->andReturn(SetupIntent::constructFrom(['id' => 'seti_1', 'client_secret' => 'seti_1_secret']));

        $session = $this->service->createSetupIntent($user);

        $this->assertSame(['client_secret' => 'seti_1_secret', 'setup_intent_id' => 'seti_1'], $session);
        $this->assertSame('cus_new', $user->fresh()->stripe_customer_id);
    }

    public function test_setup_intent_reuses_an_existing_customer(): void
    {
        $user = $this->userWithCustomer();

        $this->customers->shouldReceive('retrieve')
            ->once()
            ->with('cus_existing')
            ->andReturn(Customer::constructFrom(['id' => 'cus_existing']));
        $this->customers->shouldNotReceive('create');

        $this->setupIntents->shouldReceive('create')
            ->once()
            ->andReturn(SetupIntent::constructFrom(['id' => 'seti_2', 'client_secret' => 'seti_2_secret']));

        $this->service->createSetupIntent($user);

        $this->assertSame('cus_existing', $user->fresh()->stripe_customer_id);
    }

    public function test_a_customer_deleted_on_stripe_is_recreated(): void
    {
        $user = $this->userWithCustomer(['stripe_payment_method_id' => 'pm_old', 'card_last4' => '4242']);

        $this->customers->shouldReceive('retrieve')
            ->once()
            ->andReturn(Customer::constructFrom(['id' => 'cus_existing', 'deleted' => true]));

        $this->customers->shouldReceive('create')
            ->once()
            ->andReturn(Customer::constructFrom(['id' => 'cus_fresh']));

        $this->setupIntents->shouldReceive('create')
            ->once()
            ->andReturn(SetupIntent::constructFrom(['id' => 'seti_3', 'client_secret' => 'seti_3_secret']));

        $this->service->createSetupIntent($user);

        // Il pm del customer sparito non esiste più: colonne ripulite.
        $user->refresh();
        $this->assertSame('cus_fresh', $user->stripe_customer_id);
        $this->assertNull($user->stripe_payment_method_id);
        $this->assertNull($user->card_last4);
    }

    // ── Salvataggio ─────────────────────────────────────────────────────

    public function test_save_persists_the_masked_card_and_sets_it_as_default(): void
    {
        $user = $this->userWithCustomer();

        $this->setupIntents->shouldReceive('retrieve')
            ->once()
            ->with('seti_ok')
            ->andReturn($this->succeededIntent('pm_new'));

        $this->paymentMethods->shouldReceive('retrieve')
            ->once()
            ->with('pm_new')
            ->andReturn($this->cardPaymentMethod('pm_new'));

        $this->customers->shouldReceive('update')
            ->once()
            ->with('cus_existing', ['invoice_settings' => ['default_payment_method' => 'pm_new']]);

        $this->assertTrue($this->service->saveFromSetupIntent($user, 'seti_ok'));

        $user->refresh();
        $this->assertSame('pm_new', $user->stripe_payment_method_id);
        $this->assertSame('visa', $user->card_brand);
        $this->assertSame('4242', $user->card_last4);
        $this->assertSame(12, $user->card_exp_month);
        $this->assertSame(2030, $user->card_exp_year);
        $this->assertSame('Matteo Rossi', $user->card_holder);
        $this->assertSame('12/30', $user->card_expiry);
    }

    public function test_save_detaches_the_previous_card(): void
    {
        $user = $this->userWithCustomer(['stripe_payment_method_id' => 'pm_old', 'card_last4' => '1111']);

        $this->setupIntents->shouldReceive('retrieve')->once()->andReturn($this->succeededIntent('pm_new'));
        $this->paymentMethods->shouldReceive('retrieve')->once()->andReturn($this->cardPaymentMethod('pm_new'));
        $this->customers->shouldReceive('update')->once();

        $this->paymentMethods->shouldReceive('detach')->once()->with('pm_old');

        $this->assertTrue($this->service->saveFromSetupIntent($user, 'seti_ok'));
        $this->assertSame('pm_new', $user->fresh()->stripe_payment_method_id);
    }

    public function test_save_rejects_an_intent_of_another_customer(): void
    {
        $user = $this->userWithCustomer();

        $this->setupIntents->shouldReceive('retrieve')
            ->once()
            ->andReturn(SetupIntent::constructFrom([
                'id' => 'seti_altrui',
                'status' => 'succeeded',
                'customer' => 'cus_someone_else',
                'payment_method' => 'pm_altrui',
            ]));

        $this->paymentMethods->shouldNotReceive('retrieve');

        $this->assertFalse($this->service->saveFromSetupIntent($user, 'seti_altrui'));
        $this->assertNull($user->fresh()->stripe_payment_method_id);
    }

    public function test_save_rejects_an_intent_not_succeeded(): void
    {
        $user = $this->userWithCustomer();

        $this->setupIntents->shouldReceive('retrieve')
            ->once()
            ->andReturn(SetupIntent::constructFrom([
                'id' => 'seti_pending',
                'status' => 'requires_action',
                'customer' => 'cus_existing',
                'payment_method' => 'pm_pending',
            ]));

        $this->paymentMethods->shouldNotReceive('retrieve');

        $this->assertFalse($this->service->saveFromSetupIntent($user, 'seti_pending'));
        $this->assertNull($user->fresh()->stripe_payment_method_id);
    }

    public function test_save_is_impossible_without_a_customer(): void
    {
        $user = User::factory()->create();

        $this->setupIntents->shouldNotReceive('retrieve');

        $this->assertFalse($this->service->saveFromSetupIntent($user, 'seti_ok'));
    }

    // ── Eliminazione ────────────────────────────────────────────────────

    public function test_forget_detaches_the_card_and_clears_the_columns(): void
    {
        $user = $this->userWithCustomer([
            'stripe_payment_method_id' => 'pm_old',
            'card_brand' => 'visa',
            'card_last4' => '4242',
            'card_exp_month' => 12,
            'card_exp_year' => 2030,
            'card_holder' => 'Matteo Rossi',
        ]);

        $this->paymentMethods->shouldReceive('detach')->once()->with('pm_old');

        $this->service->forget($user);

        $user->refresh();
        $this->assertNull($user->stripe_payment_method_id);
        $this->assertNull($user->card_last4);
        $this->assertNull($user->card_holder);
        // Il customer resta: l'utente potrà salvare una nuova carta.
        $this->assertSame('cus_existing', $user->stripe_customer_id);
    }

    // ── Helper ──────────────────────────────────────────────────────────

    private function userWithCustomer(array $attributes = []): User
    {
        $user = User::factory()->create();

        $user->forceFill(array_merge(['stripe_customer_id' => 'cus_existing'], $attributes))->save();

        return $user;
    }

    private function succeededIntent(string $paymentMethodId): SetupIntent
    {
        return SetupIntent::constructFrom([
            'id' => 'seti_ok',
            'status' => 'succeeded',
            'customer' => 'cus_existing',
            'payment_method' => $paymentMethodId,
        ]);
    }

    private function cardPaymentMethod(string $id): PaymentMethod
    {
        return PaymentMethod::constructFrom([
            'id' => $id,
            'card' => ['brand' => 'visa', 'last4' => '4242', 'exp_month' => 12, 'exp_year' => 2030],
            'billing_details' => ['name' => 'Matteo Rossi'],
        ]);
    }
}
