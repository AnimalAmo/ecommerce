<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConfigurationException;
use App\Models\OrderPayment\OrderPayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Gateway PayPal classico (SDK JS + Orders API v2), port da matsuri adattato
 * a int cents: l'ordine PayPal nasce in init, onApprove il client rimanda il
 * paypal_order_id e captureFromCheckout esegue la capture verificandone
 * status e importo server-side. Http client Laravel (Http::fake nei test).
 */
class PaypalGateway implements PaymentGatewayInterface
{
    /** Campi della risposta ordine persistiti in provider_response (mai il payer). */
    private const SAFE_ORDER_FIELDS = ['id', 'status', 'purchase_units'];

    private readonly string $clientId;

    private readonly string $secret;

    private readonly string $baseUrl;

    private ?string $accessToken = null;

    public function __construct()
    {
        $this->clientId = (string) config('payment.paypal.client_id');
        $this->secret = (string) config('payment.paypal.secret');
        $this->baseUrl = config('payment.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        if ($this->clientId === '' || $this->secret === '') {
            throw PaymentConfigurationException::missing('paypal');
        }
    }

    public function initPaymentSession(int $amountCents, PaymentMethod $method, array $context = []): array
    {
        $response = $this->request()->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'CART-'.uniqid(),
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $this->formatAmount($amountCents),
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            $this->logAndThrow('initPaymentSession', $response);
        }

        return ['paypal_order_id' => $response->json('id')];
    }

    public function captureFromCheckout(array $payload, int $expectedAmountCents): CheckoutCaptureResult
    {
        $paypalOrderId = (string) ($payload['paypal_order_id'] ?? '');

        if ($paypalOrderId === '') {
            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        try {
            $response = $this->request()->post("/v2/checkout/orders/{$paypalOrderId}/capture");
        } catch (Throwable $exception) {
            Log::warning('PayPal capture: chiamata fallita', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $exception->getMessage(),
            ]);

            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        $capture = $response->json('purchase_units.0.payments.captures.0');

        if (! $response->successful()
            || $response->json('status') !== 'COMPLETED'
            || ! is_array($capture)) {
            Log::warning('PayPal capture: verifica server-side fallita', [
                'paypal_order_id' => $paypalOrderId,
                'http_status' => $response->status(),
                'order_status' => $response->json('status'),
                'capture_amount' => $capture['amount'] ?? null,
                'expected_amount' => $this->formatAmount($expectedAmountCents),
            ]);

            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        if (($capture['amount']['currency_code'] ?? null) !== 'EUR'
            || ($capture['amount']['value'] ?? null) !== $this->formatAmount($expectedAmountCents)) {
            // Ordine COMPLETED con capture presente: i soldi sono transitati
            // ma per un importo/valuta non attesi — il chiamante DEVE stornare.
            Log::warning('PayPal capture: incassato ma importo/valuta non validi', [
                'paypal_order_id' => $paypalOrderId,
                'capture_amount' => $capture['amount'] ?? null,
                'expected_amount' => $this->formatAmount($expectedAmountCents),
            ]);

            return CheckoutCaptureResult::capturedButInvalid(
                errorMessage: __('payment.errors.amount_changed'),
                gatewaySessionId: $paypalOrderId,
                transactionId: $capture['id'] ?? $paypalOrderId,
                provider: 'paypal',
                capturedAmountCents: ($capture['amount']['currency_code'] ?? null) === 'EUR'
                    ? (int) round(((float) ($capture['amount']['value'] ?? 0)) * 100)
                    : null,
            );
        }

        return CheckoutCaptureResult::success(
            gatewaySessionId: $paypalOrderId,
            transactionId: $capture['id'] ?? $paypalOrderId,
            provider: 'paypal',
            providerResponse: Arr::only($response->json(), self::SAFE_ORDER_FIELDS),
        );
    }

    public function refund(string $transactionId, int $amountCents): void
    {
        $response = $this->request()->post("/v2/payments/captures/{$transactionId}/refund", [
            'amount' => [
                'currency_code' => 'EUR',
                'value' => $this->formatAmount($amountCents),
            ],
        ]);

        if (! $response->successful()) {
            $this->logAndThrow('refund', $response, ['capture_id' => $transactionId]);
        }
    }

    public function handleWebhook(array $payload, array $headers): ?OrderPayment
    {
        $this->verifyWebhookSignature($payload, $headers);

        if (($payload['event_type'] ?? '') !== 'PAYMENT.CAPTURE.COMPLETED') {
            return null;
        }

        $paypalOrderId = $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! $paypalOrderId) {
            Log::warning('PayPal webhook: order_id mancante nella capture', [
                'event_id' => $payload['id'] ?? null,
            ]);

            return null;
        }

        $payment = OrderPayment::query()
            ->where('gateway_session_id', $paypalOrderId)
            ->first();

        if (! $payment) {
            Log::warning('PayPal webhook: ordine PayPal sconosciuto', [
                'paypal_order_id' => $paypalOrderId,
            ]);

            return null;
        }

        // Riconciliazione idempotente: il flusso capture-first di norma
        // ha già completato il pagamento prima dell'arrivo del webhook.
        if ($payment->status === PaymentStatus::Completed) {
            return $payment;
        }

        $payment->update([
            'status' => PaymentStatus::Completed,
            'transaction_id' => $payload['resource']['id'] ?? $paypalOrderId,
            'provider_response' => Arr::only($payload, ['id', 'event_type', 'resource']),
            'paid_at' => now(),
        ]);

        return $payment->fresh();
    }

    /**
     * Verifica firma via API PayPal. Senza PAYPAL_WEBHOOK_ID la verifica è
     * saltata con warning (comportamento matsuri); esito != SUCCESS → 400.
     */
    private function verifyWebhookSignature(array $payload, array $headers): void
    {
        $webhookId = (string) config('payment.paypal.webhook_id');

        if ($webhookId === '') {
            Log::warning('PayPal webhook: PAYPAL_WEBHOOK_ID non configurato, firma non verificata');

            return;
        }

        $response = $this->request()->post('/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'][0] ?? '',
            'cert_url' => $headers['paypal-cert-url'][0] ?? '',
            'transmission_id' => $headers['paypal-transmission-id'][0] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'][0] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'][0] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => $payload,
        ]);

        if ($response->json('verification_status') !== 'SUCCESS') {
            throw new RuntimeException('PayPal webhook signature verification failed.');
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->resolveAccessToken());
    }

    private function resolveAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            $this->logAndThrow('resolveAccessToken', $response);
        }

        return $this->accessToken = (string) $response->json('access_token');
    }

    /** PayPal vuole stringhe decimali: i cents restano int fino al confine API. */
    private function formatAmount(int $amountCents): string
    {
        return number_format($amountCents / 100, 2, '.', '');
    }

    private function logAndThrow(string $action, Response $response, array $context = []): never
    {
        Log::error("PayPal {$action} failed", array_merge($context, [
            'status' => $response->status(),
            'body' => $response->json(),
        ]));

        throw new RuntimeException("PayPal {$action} failed with status {$response->status()}.");
    }
}
