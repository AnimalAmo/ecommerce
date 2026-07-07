<?php

namespace Tests\Unit\Payment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConfigurationException;
use App\Models\OrderPayment\OrderPayment;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaypalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaypalGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://api-m.sandbox.paypal.com';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.paypal.client_id' => 'test-client-id',
            'payment.paypal.secret' => 'test-secret',
            'payment.paypal.mode' => 'sandbox',
            'payment.paypal.webhook_id' => null,
        ]);
    }

    // ── init ────────────────────────────────────────────────────────────

    public function test_init_creates_a_paypal_order_with_the_amount_in_euros(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders' => Http::response(['id' => 'PP-ORDER-1', 'status' => 'CREATED']),
        ]);

        $session = (new PaypalGateway)->initPaymentSession(47600, PaymentMethod::Paypal);

        $this->assertSame(['paypal_order_id' => 'PP-ORDER-1'], $session);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::BASE.'/v2/checkout/orders'
                && $request['intent'] === 'CAPTURE'
                && $request['purchase_units'][0]['amount']['value'] === '476.00'
                && $request['purchase_units'][0]['amount']['currency_code'] === 'EUR';
        });
    }

    public function test_init_throws_when_the_order_creation_fails(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders' => Http::response(['error' => 'invalid_request'], 422),
        ]);

        $this->expectException(RuntimeException::class);

        (new PaypalGateway)->initPaymentSession(47600, PaymentMethod::Paypal);
    }

    // ── capture (verifica server-side status + importo) ─────────────────

    public function test_capture_succeeds_when_completed_and_the_amount_matches(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders/*/capture' => Http::response($this->captureResponse('476.00')),
        ]);

        $result = (new PaypalGateway)->captureFromCheckout(['paypal_order_id' => 'PP-ORDER-1'], 47600);

        $this->assertTrue($result->succeeded);
        $this->assertSame('PP-ORDER-1', $result->gatewaySessionId);
        $this->assertSame('CAP-1', $result->transactionId);
        $this->assertSame('paypal', $result->provider);
        // Il payer (PII) non deve finire in provider_response.
        $this->assertArrayNotHasKey('payer', $result->providerResponse);
    }

    public function test_capture_fails_on_amount_mismatch(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders/*/capture' => Http::response($this->captureResponse('10.00')),
        ]);

        $result = (new PaypalGateway)->captureFromCheckout(['paypal_order_id' => 'PP-ORDER-1'], 47600);

        // Incassato ma NON valido: fundsCaptured + transaction id per lo
        // storno immediato del chiamante (mai soldi orfani).
        $this->assertFalse($result->succeeded);
        $this->assertTrue($result->fundsCaptured);
        $this->assertSame('CAP-1', $result->transactionId);
        $this->assertSame('paypal', $result->provider);
        $this->assertSame(1000, $result->capturedAmountCents);
        $this->assertSame(__('payment.errors.amount_changed'), $result->errorMessage);
    }

    public function test_capture_fails_when_the_order_is_not_completed(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders/*/capture' => Http::response(
                $this->captureResponse('476.00', status: 'PENDING'),
            ),
        ]);

        $result = (new PaypalGateway)->captureFromCheckout(['paypal_order_id' => 'PP-ORDER-1'], 47600);

        $this->assertFalse($result->succeeded);
        // Ordine non COMPLETED: nessun incasso, niente da stornare.
        $this->assertFalse($result->fundsCaptured);
    }

    public function test_capture_fails_without_a_paypal_order_id(): void
    {
        Http::fake();

        $result = (new PaypalGateway)->captureFromCheckout([], 47600);

        $this->assertFalse($result->succeeded);
        Http::assertNothingSent();
    }

    public function test_capture_fails_gracefully_when_the_api_errors(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/checkout/orders/*/capture' => Http::response(['name' => 'UNPROCESSABLE_ENTITY'], 422),
        ]);

        $result = (new PaypalGateway)->captureFromCheckout(['paypal_order_id' => 'PP-ORDER-1'], 47600);

        $this->assertFalse($result->succeeded);
    }

    // ── refund ──────────────────────────────────────────────────────────

    public function test_refund_posts_a_capture_refund_with_the_amount(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/payments/captures/*/refund' => Http::response(['id' => 'REF-1', 'status' => 'COMPLETED']),
        ]);

        (new PaypalGateway)->refund('CAP-1', 47600);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === self::BASE.'/v2/payments/captures/CAP-1/refund'
                && $request['amount']['value'] === '476.00'
                && $request['amount']['currency_code'] === 'EUR';
        });
    }

    public function test_refund_throws_when_the_api_rejects_it(): void
    {
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v2/payments/captures/*/refund' => Http::response(['name' => 'INVALID_REQUEST'], 422),
        ]);

        $this->expectException(RuntimeException::class);

        (new PaypalGateway)->refund('CAP-1', 47600);
    }

    // ── webhook ─────────────────────────────────────────────────────────

    /** Webhook firmato e verificato: PAYPAL_WEBHOOK_ID configurato + verifica firma SUCCESS (fail-closed). */
    private function fakeVerifiedSignature(): void
    {
        config(['payment.paypal.webhook_id' => 'WH-ID-1']);
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
    }

    public function test_webhook_completes_a_pending_payment(): void
    {
        $this->fakeVerifiedSignature();
        $payment = $this->pendingPaypalPayment('PP-ORDER-1');

        $handled = (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-ORDER-1'), []);

        $this->assertNotNull($handled);
        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame('CAP-1', $payment->transaction_id);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_webhook_is_idempotent_when_the_payment_is_already_completed(): void
    {
        $this->fakeVerifiedSignature();
        $paidAt = now()->subDay()->startOfSecond();
        $payment = OrderPayment::factory()->completed()->create([
            'payment_method' => PaymentMethod::Paypal,
            'provider' => 'paypal',
            'gateway_session_id' => 'PP-ORDER-1',
            'paid_at' => $paidAt,
        ]);

        $handled = (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-ORDER-1'), []);

        $this->assertTrue($handled->is($payment));
        $this->assertTrue($payment->fresh()->paid_at->equalTo($paidAt));
    }

    public function test_webhook_with_unknown_order_returns_null(): void
    {
        $this->fakeVerifiedSignature();

        $handled = (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-UNKNOWN'), []);

        $this->assertNull($handled);
    }

    public function test_webhook_ignores_unrelated_event_types(): void
    {
        $this->fakeVerifiedSignature();
        $this->pendingPaypalPayment('PP-ORDER-1');

        $event = $this->captureCompletedEvent('PP-ORDER-1');
        $event['event_type'] = 'PAYMENT.CAPTURE.DENIED';

        $this->assertNull((new PaypalGateway)->handleWebhook($event, []));
    }

    public function test_webhook_is_rejected_when_webhook_id_is_not_configured(): void
    {
        // Fail-closed: senza PAYPAL_WEBHOOK_ID un webhook non firmato viene rifiutato
        // (mai accettato/processato) — un POST falso su /webhooks/paypal non completa nulla.
        config(['payment.paypal.webhook_id' => '']);
        Http::fake();
        $payment = $this->pendingPaypalPayment('PP-ORDER-1');

        try {
            (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-ORDER-1'), []);
            $this->fail('Un webhook senza PAYPAL_WEBHOOK_ID configurato deve essere rifiutato.');
        } catch (RuntimeException) {
            // atteso
        }

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_webhook_signature_verification_failure_throws(): void
    {
        config(['payment.paypal.webhook_id' => 'WH-ID-1']);
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE']),
        ]);

        $this->expectException(RuntimeException::class);

        (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-ORDER-1'), []);
    }

    public function test_webhook_with_verified_signature_completes_the_payment(): void
    {
        config(['payment.paypal.webhook_id' => 'WH-ID-1']);
        Http::fake([
            self::BASE.'/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            self::BASE.'/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
        $payment = $this->pendingPaypalPayment('PP-ORDER-1');

        $handled = (new PaypalGateway)->handleWebhook($this->captureCompletedEvent('PP-ORDER-1'), []);

        $this->assertNotNull($handled);
        $this->assertSame(PaymentStatus::Completed, $payment->fresh()->status);
    }

    // ── config / factory ────────────────────────────────────────────────

    public function test_missing_credentials_throw_a_configuration_exception(): void
    {
        config(['payment.paypal.client_id' => '']);

        $this->expectException(PaymentConfigurationException::class);

        new PaypalGateway;
    }

    public function test_the_factory_resolves_paypal_for_the_paypal_method(): void
    {
        $gateway = $this->app->make(PaymentGatewayFactory::class)->make(PaymentMethod::Paypal);

        $this->assertInstanceOf(PaypalGateway::class, $gateway);
    }

    // ── helper ──────────────────────────────────────────────────────────

    private function captureResponse(string $value, string $status = 'COMPLETED'): array
    {
        return [
            'id' => 'PP-ORDER-1',
            'status' => $status,
            'purchase_units' => [
                [
                    'payments' => [
                        'captures' => [
                            [
                                'id' => 'CAP-1',
                                'status' => $status,
                                'amount' => ['currency_code' => 'EUR', 'value' => $value],
                            ],
                        ],
                    ],
                ],
            ],
            'payer' => ['email_address' => 'buyer@example.com'],
        ];
    }

    private function captureCompletedEvent(string $orderId): array
    {
        return [
            'id' => 'WH-EVENT-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP-1',
                'status' => 'COMPLETED',
                'supplementary_data' => [
                    'related_ids' => ['order_id' => $orderId],
                ],
            ],
        ];
    }

    private function pendingPaypalPayment(string $paypalOrderId): OrderPayment
    {
        return OrderPayment::factory()->create([
            'payment_method' => PaymentMethod::Paypal,
            'provider' => 'paypal',
            'gateway_session_id' => $paypalOrderId,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
