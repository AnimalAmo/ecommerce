<?php

namespace Tests\Unit\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConfigurationException;
use App\Models\OrderPayment\OrderPayment;
use App\Models\Partner\PartnerProfile;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\StripeConnectService;
use App\Services\Payment\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Stripe\Account;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Refund;
use Stripe\Service\AccountService;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\PaymentMethodService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    /** Account connesso del venditore: ogni addebito nasce lì, mai sulla piattaforma. */
    private const ACCOUNT = 'acct_partner';

    private MockInterface $paymentIntents;

    private MockInterface $refunds;

    private MockInterface $paymentMethods;

    private MockInterface $accounts;

    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentIntents = Mockery::mock(PaymentIntentService::class);
        $this->refunds = Mockery::mock(RefundService::class);
        $this->paymentMethods = Mockery::mock(PaymentMethodService::class);
        $this->accounts = Mockery::mock(AccountService::class);

        // StripeClient::__get delega a getService(): basta stubbare quello.
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($this->paymentIntents);
        $client->shouldReceive('getService')->with('refunds')->andReturn($this->refunds);
        $client->shouldReceive('getService')->with('paymentMethods')->andReturn($this->paymentMethods);
        $client->shouldReceive('getService')->with('accounts')->andReturn($this->accounts);

        // Il gateway delega la sincronizzazione dell'account al servizio Connect,
        // che condivide lo stesso StripeClient.
        $this->app->instance(StripeConnectService::class, new StripeConnectService($client));

        $this->gateway = new StripeGateway($client);

        config(['payment.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    // ── init ────────────────────────────────────────────────────────────

    public function test_init_creates_a_payment_intent_with_the_method_types(): void
    {
        $this->paymentIntents->shouldReceive('create')
            ->once()
            ->with([
                'amount' => 47600,
                'currency' => 'eur',
                'payment_method_types' => ['card'],
                'application_fee_amount' => 4760,
            ], ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_new'));

        $session = $this->gateway->initPaymentSession(47600, PaymentMethod::Card, $this->context(['application_fee_amount' => 4760]));

        $this->assertSame([
            'client_secret' => 'pi_new_secret',
            'payment_intent_id' => 'pi_new',
        ], $session);
    }

    public function test_init_updates_the_existing_payment_intent_from_context(): void
    {
        $this->paymentIntents->shouldReceive('update')
            ->once()
            ->with('pi_existing', [
                'amount' => 20000,
                'payment_method_types' => ['card'],
                'application_fee_amount' => 2000,
            ], ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_existing'));

        $session = $this->gateway->initPaymentSession(20000, PaymentMethod::GooglePay, $this->context([
            'payment_intent_id' => 'pi_existing',
            'application_fee_amount' => 2000,
        ]));

        $this->assertSame('pi_existing', $session['payment_intent_id']);
    }

    // ── capture (riverifica server-side) ────────────────────────────────

    public function test_capture_succeeds_when_the_intent_is_verified(): void
    {
        $this->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->with('pi_123', [], ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_123', status: 'succeeded', amountReceived: 47600));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600, self::ACCOUNT);

        $this->assertTrue($result->succeeded);
        $this->assertSame('pi_123', $result->gatewaySessionId);
        $this->assertSame('pi_123', $result->transactionId);
        $this->assertSame('stripe', $result->provider);
        // Il client_secret non deve mai finire in provider_response.
        $this->assertArrayNotHasKey('client_secret', $result->providerResponse);
    }

    public function test_capture_fails_on_amount_mismatch(): void
    {
        $this->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->with('pi_123', [], ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_123', status: 'succeeded', amountReceived: 100));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600, self::ACCOUNT);

        // Incassato ma NON valido: fundsCaptured + transaction id per lo
        // storno immediato del chiamante (mai soldi orfani).
        $this->assertFalse($result->succeeded);
        $this->assertTrue($result->fundsCaptured);
        $this->assertSame('pi_123', $result->transactionId);
        $this->assertSame('stripe', $result->provider);
        $this->assertSame(100, $result->capturedAmountCents);
        $this->assertSame(__('payment.errors.amount_changed'), $result->errorMessage);
    }

    public function test_capture_fails_when_the_intent_is_not_succeeded(): void
    {
        $this->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->with('pi_123', [], ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_123', status: 'requires_payment_method', amountReceived: 47600));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600, self::ACCOUNT);

        $this->assertFalse($result->succeeded);
        // Nessun incasso avvenuto: niente da stornare.
        $this->assertFalse($result->fundsCaptured);
    }

    public function test_capture_fails_without_a_payment_intent_id(): void
    {
        $this->paymentIntents->shouldNotReceive('retrieve');

        $result = $this->gateway->captureFromCheckout([], 47600, self::ACCOUNT);

        $this->assertFalse($result->succeeded);
    }

    // ── refund ──────────────────────────────────────────────────────────

    public function test_refund_creates_a_stripe_refund_by_payment_intent(): void
    {
        $this->refunds->shouldReceive('create')
            ->once()
            ->with(
                ['payment_intent' => 'pi_123', 'amount' => 4760, 'refund_application_fee' => true],
                ['stripe_account' => self::ACCOUNT],
            )
            ->andReturn(Refund::constructFrom(['id' => 're_1']));

        $this->gateway->refund('pi_123', 4760, self::ACCOUNT);
    }

    // ── webhook ─────────────────────────────────────────────────────────

    public function test_webhook_with_valid_signature_completes_a_pending_payment(): void
    {
        $payment = OrderPayment::factory()->create([
            'gateway_session_id' => 'pi_123',
            'status' => PaymentStatus::Pending,
        ]);

        $handled = $this->handleSignedWebhook($this->succeededEvent('pi_123'));

        $this->assertNotNull($handled);
        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame('pi_123', $payment->transaction_id);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_webhook_is_idempotent_when_the_payment_is_already_completed(): void
    {
        $paidAt = now()->subDay()->startOfSecond();
        $payment = OrderPayment::factory()->completed()->create([
            'gateway_session_id' => 'pi_123',
            'paid_at' => $paidAt,
        ]);

        $handled = $this->handleSignedWebhook($this->succeededEvent('pi_123'));

        $this->assertTrue($handled->is($payment));
        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertTrue($payment->paid_at->equalTo($paidAt));
        $this->assertSame(['status' => 'succeeded'], $payment->provider_response);
    }

    public function test_webhook_with_invalid_signature_throws(): void
    {
        $this->expectException(SignatureVerificationException::class);

        $this->gateway->handleWebhook($this->succeededEvent('pi_123'), [
            'stripe-signature' => ['t=1,v1=invalid-signature'],
            'raw_body' => [json_encode($this->succeededEvent('pi_123'))],
        ]);
    }

    public function test_webhook_with_unknown_intent_logs_a_warning_and_returns_null(): void
    {
        Log::spy();

        $handled = $this->handleSignedWebhook($this->succeededEvent('pi_unknown'));

        $this->assertNull($handled);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_webhook_payment_failed_marks_the_payment_failed(): void
    {
        $payment = OrderPayment::factory()->create([
            'gateway_session_id' => 'pi_123',
            'status' => PaymentStatus::Pending,
        ]);

        $event = $this->succeededEvent('pi_123');
        $event['type'] = 'payment_intent.payment_failed';
        $event['data']['object']['status'] = 'requires_payment_method';

        $this->handleSignedWebhook($event);

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
    }

    public function test_webhook_ignores_unrelated_event_types(): void
    {
        $event = $this->succeededEvent('pi_123');
        $event['type'] = 'charge.refunded';

        $this->assertNull($this->handleSignedWebhook($event));
    }

    public function test_webhook_without_secret_throws_a_configuration_exception(): void
    {
        config(['payment.stripe.webhook_secret' => '']);

        $this->expectException(PaymentConfigurationException::class);

        $this->gateway->handleWebhook([], []);
    }

    // ── container / factory ─────────────────────────────────────────────

    public function test_resolving_the_client_without_secret_throws_a_configuration_exception(): void
    {
        config(['payment.stripe.secret' => '']);

        $this->expectException(PaymentConfigurationException::class);

        $this->app->make(StripeClient::class);
    }

    public function test_the_factory_resolves_stripe_for_card_methods(): void
    {
        config(['payment.stripe.secret' => 'sk_test_dummy']);

        $gateway = $this->app->make(PaymentGatewayFactory::class)->make(PaymentMethod::Card);

        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    // ── helper ──────────────────────────────────────────────────────────

    private function intent(string $id, string $status = 'succeeded', int $amountReceived = 47600): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id' => $id,
            'object' => 'payment_intent',
            'client_secret' => "{$id}_secret",
            'status' => $status,
            'amount' => $amountReceived,
            'amount_received' => $amountReceived,
            'currency' => 'eur',
        ]);
    }

    private function succeededEvent(string $intentId): array
    {
        return [
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $intentId,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 47600,
                    'amount_received' => 47600,
                    'currency' => 'eur',
                ],
            ],
        ];
    }

    /** Firma di test: header 't=...,v1=hash_hmac(sha256, t.payload, secret)'. */
    private function handleSignedWebhook(array $event): ?OrderPayment
    {
        $json = json_encode($event);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', "{$timestamp}.{$json}", self::WEBHOOK_SECRET);

        return $this->gateway->handleWebhook($event, [
            'stripe-signature' => [$signature],
            'raw_body' => [$json],
        ]);
    }

    // ── webhook Connect ─────────────────────────────────────────────────

    public function test_account_updated_allinea_i_flag_del_profilo(): void
    {
        $profile = PartnerProfile::factory()->create([
            'stripe_account_id' => self::ACCOUNT,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $this->accounts->shouldReceive('retrieve')
            ->once()
            ->with(self::ACCOUNT)
            ->andReturn(Account::constructFrom([
                'id' => self::ACCOUNT,
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'requirements' => ['currently_due' => []],
            ]));

        // Nessun OrderPayment coinvolto: l'evento riguarda l'account, non un incasso.
        $this->assertNull($this->handleSignedWebhook([
            'id' => 'evt_account',
            'type' => 'account.updated',
            'account' => self::ACCOUNT,
            'data' => ['object' => ['id' => self::ACCOUNT]],
        ]));

        $profile->refresh();

        $this->assertTrue($profile->stripe_charges_enabled);
        $this->assertTrue($profile->stripe_payouts_enabled);
        $this->assertSame([], $profile->stripe_requirements_due);
    }

    public function test_un_incasso_di_un_account_connesso_viene_riconciliato(): void
    {
        $payment = OrderPayment::factory()->create([
            'gateway_session_id' => 'pi_connect',
            'status' => PaymentStatus::Pending,
        ]);

        // Con i direct charges l'evento nasce sull'account del partner: l'id
        // del PaymentIntent resta l'aggancio, ed è unico.
        $event = $this->succeededEvent('pi_connect');
        $event['account'] = self::ACCOUNT;

        $this->assertNotNull($this->handleSignedWebhook($event));
        $this->assertSame(PaymentStatus::Completed, $payment->fresh()->status);
    }

    // ── direct charges ──────────────────────────────────────────────────

    public function test_sotto_soglia_la_provvigione_non_compare_nella_richiesta(): void
    {
        $this->paymentIntents->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(fn (array $params): bool => ! array_key_exists('application_fee_amount', $params)),
                ['stripe_account' => self::ACCOUNT],
            )
            ->andReturn($this->intent('pi_small'));

        // null = nessuna provvigione dovuta: il parametro va omesso, non messo
        // a zero (Stripe lo rifiuterebbe con invalid_request_error).
        $this->gateway->initPaymentSession(4000, PaymentMethod::Card, $this->context(['application_fee_amount' => null]));
    }

    public function test_senza_account_connesso_il_pagamento_non_parte(): void
    {
        $this->paymentIntents->shouldNotReceive('create');

        $this->expectException(InvalidArgumentException::class);

        // Mai un addebito sul conto della piattaforma: è il punto del modello.
        $this->gateway->initPaymentSession(10000, PaymentMethod::Card);
    }

    public function test_la_carta_salvata_viene_clonata_sull_account_del_partner(): void
    {
        $this->paymentMethods->shouldReceive('create')
            ->once()
            ->with(
                ['customer' => 'cus_platform', 'payment_method' => 'pm_platform'],
                ['stripe_account' => self::ACCOUNT],
            )
            ->andReturn(StripePaymentMethod::constructFrom(['id' => 'pm_cloned']));

        $this->paymentIntents->shouldReceive('create')
            ->once()
            ->with(
                // Il customer resta alla piattaforma: sul PI va solo il
                // payment method clonato sull'account del venditore.
                Mockery::on(fn (array $params): bool => $params['payment_method'] === 'pm_cloned'
                    && ! array_key_exists('customer', $params)),
                ['stripe_account' => self::ACCOUNT],
            )
            ->andReturn($this->intent('pi_saved'));

        $this->gateway->initPaymentSession(12000, PaymentMethod::Card, $this->context([
            'customer_id' => 'cus_platform',
            'payment_method_id' => 'pm_platform',
            'application_fee_amount' => 1200,
        ]));
    }

    public function test_aggiornare_la_sessione_non_riclona_la_carta(): void
    {
        $this->paymentMethods->shouldNotReceive('create');

        $this->paymentIntents->shouldReceive('update')
            ->once()
            ->with('pi_existing', Mockery::type('array'), ['stripe_account' => self::ACCOUNT])
            ->andReturn($this->intent('pi_existing'));

        // Il payment method è già allegato al PI: riclonarlo a ogni cambio di
        // importo creerebbe un PaymentMethod nuovo per ogni tasto premuto.
        $this->gateway->initPaymentSession(12000, PaymentMethod::Card, $this->context([
            'payment_intent_id' => 'pi_existing',
            'customer_id' => 'cus_platform',
            'payment_method_id' => 'pm_platform',
        ]));
    }

    /** Context di una sessione: sempre con l'account del venditore. */
    private function context(array $extra = []): array
    {
        return array_merge(['stripe_account_id' => self::ACCOUNT], $extra);
    }
}
