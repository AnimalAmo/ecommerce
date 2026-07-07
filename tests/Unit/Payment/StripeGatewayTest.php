<?php

namespace Tests\Unit\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConfigurationException;
use App\Models\OrderPayment\OrderPayment;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    private MockInterface $paymentIntents;

    private MockInterface $refunds;

    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentIntents = Mockery::mock(PaymentIntentService::class);
        $this->refunds = Mockery::mock(RefundService::class);

        // StripeClient::__get delega a getService(): basta stubbare quello.
        $client = Mockery::mock(StripeClient::class);
        $client->shouldReceive('getService')->with('paymentIntents')->andReturn($this->paymentIntents);
        $client->shouldReceive('getService')->with('refunds')->andReturn($this->refunds);

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
            ])
            ->andReturn($this->intent('pi_new'));

        $session = $this->gateway->initPaymentSession(47600, PaymentMethod::Card);

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
                'payment_method_types' => ['klarna'],
            ])
            ->andReturn($this->intent('pi_existing'));

        $session = $this->gateway->initPaymentSession(20000, PaymentMethod::Klarna, [
            'payment_intent_id' => 'pi_existing',
        ]);

        $this->assertSame('pi_existing', $session['payment_intent_id']);
    }

    public function test_init_rejects_methods_not_handled_by_stripe(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->gateway->initPaymentSession(1000, PaymentMethod::Paypal);
    }

    // ── capture (riverifica server-side) ────────────────────────────────

    public function test_capture_succeeds_when_the_intent_is_verified(): void
    {
        $this->paymentIntents->shouldReceive('retrieve')
            ->once()
            ->with('pi_123')
            ->andReturn($this->intent('pi_123', status: 'succeeded', amountReceived: 47600));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600);

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
            ->with('pi_123')
            ->andReturn($this->intent('pi_123', status: 'succeeded', amountReceived: 100));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600);

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
            ->with('pi_123')
            ->andReturn($this->intent('pi_123', status: 'requires_payment_method', amountReceived: 47600));

        $result = $this->gateway->captureFromCheckout(['payment_intent_id' => 'pi_123'], 47600);

        $this->assertFalse($result->succeeded);
        // Nessun incasso avvenuto: niente da stornare.
        $this->assertFalse($result->fundsCaptured);
    }

    public function test_capture_fails_without_a_payment_intent_id(): void
    {
        $this->paymentIntents->shouldNotReceive('retrieve');

        $result = $this->gateway->captureFromCheckout([], 47600);

        $this->assertFalse($result->succeeded);
    }

    // ── refund ──────────────────────────────────────────────────────────

    public function test_refund_creates_a_stripe_refund_by_payment_intent(): void
    {
        $this->refunds->shouldReceive('create')
            ->once()
            ->with(['payment_intent' => 'pi_123', 'amount' => 4760])
            ->andReturn(Refund::constructFrom(['id' => 're_1']));

        $this->gateway->refund('pi_123', 4760);
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
}
