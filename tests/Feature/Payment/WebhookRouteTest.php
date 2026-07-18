<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Models\OrderPayment\OrderPayment;
use App\Models\PaymentGateway\PaymentGateway;
use App\Providers\PaymentServiceProvider;
use App\Services\Payment\PaymentGatewayService;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WebhookRouteTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_route_test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.stripe.secret' => 'sk_test_dummy',
            'payment.stripe.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    public function test_the_stripe_webhook_route_exists_and_rejects_unsigned_calls(): void
    {
        $this->seed(PaymentGatewaySeeder::class);
        $this->registerWebhookRoutes();

        // La rotta risponde (niente 404); senza firma valida → 400.
        $this->postJson('/webhooks/stripe', ['foo' => 'bar'])->assertStatus(400);
    }

    public function test_a_signed_stripe_webhook_completes_the_pending_payment(): void
    {
        $this->seed(PaymentGatewaySeeder::class);
        $this->registerWebhookRoutes();

        $payment = OrderPayment::factory()->create([
            'gateway_session_id' => 'pi_route_1',
            'status' => PaymentStatus::Pending,
        ]);

        $this->postSignedStripeWebhook('pi_route_1')->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame('pi_route_1', $payment->transaction_id);
    }

    public function test_a_signed_webhook_for_an_unknown_intent_still_returns_200(): void
    {
        $this->seed(PaymentGatewaySeeder::class);
        $this->registerWebhookRoutes();

        $this->postSignedStripeWebhook('pi_never_seen')->assertOk();
    }

    public function test_no_route_is_registered_for_a_disabled_gateway(): void
    {
        PaymentGateway::query()->create(['code' => 'stripe', 'name' => 'Stripe', 'is_enabled' => false, 'sort_order' => 0]);
        $this->registerWebhookRoutes();

        $this->postJson('/webhooks/stripe', [])->assertNotFound();
    }

    public function test_webhooks_are_exempt_from_csrf_validation(): void
    {
        $middleware = $this->app->make(ValidateCsrfToken::class);

        $this->assertContains('webhooks/*', $middleware->getExcludedPaths());
    }

    // ── helper ──────────────────────────────────────────────────────────

    /**
     * Il provider si avvia prima delle migrations del test (tabella assente,
     * nessuna rotta): dopo il seed si ripete la registrazione delle rotte.
     */
    private function registerWebhookRoutes(): void
    {
        $this->app->make(PaymentGatewayService::class)->clearCache();
        (new PaymentServiceProvider($this->app))->boot();
    }

    private function postSignedStripeWebhook(string $intentId): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_route_1',
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
        ]);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }
}
