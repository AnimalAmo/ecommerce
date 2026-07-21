<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConfigurationException;
use App\Models\OrderPayment\OrderPayment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Webhook;

/**
 * Gateway Stripe (carta, Apple/Google Pay via ECE). Capture-first:
 * il PaymentIntent nasce al checkout senza ordine, il JS lo conferma e
 * captureFromCheckout RIVERIFICA server-side (mai fidarsi del client).
 * Il client è iniettato dal PaymentServiceProvider (testabile).
 */
class StripeGateway implements PaymentGatewayInterface
{
    /** Campi del PaymentIntent persistiti in provider_response (mai il client_secret). */
    private const SAFE_INTENT_FIELDS = [
        'id',
        'object',
        'status',
        'amount',
        'amount_received',
        'currency',
        'payment_method_types',
        'created',
    ];

    public function __construct(private readonly StripeClient $client) {}

    public function initPaymentSession(int $amountCents, PaymentMethod $method, array $context = []): array
    {
        $types = $method->stripePaymentMethodTypes();

        // Carta salvata: il PI nasce già intestato al customer e con il
        // payment method allegato, così il client conferma col solo client
        // secret (nessun Element, nessun dato carta nel browser). Gli id
        // arrivano SEMPRE dal server (utente autenticato), mai dal payload.
        $saved = array_filter([
            'customer' => $context['customer_id'] ?? null,
            'payment_method' => $context['payment_method_id'] ?? null,
        ]);

        $intent = isset($context['payment_intent_id'])
            ? $this->client->paymentIntents->update($context['payment_intent_id'], [
                'amount' => $amountCents,
                'payment_method_types' => $types,
                ...$saved,
            ])
            : $this->client->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'payment_method_types' => $types,
                ...$saved,
            ]);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    public function captureFromCheckout(array $payload, int $expectedAmountCents): CheckoutCaptureResult
    {
        $paymentIntentId = (string) ($payload['payment_intent_id'] ?? '');

        if ($paymentIntentId === '') {
            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        try {
            $intent = $this->client->paymentIntents->retrieve($paymentIntentId);
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe capture: retrieve del PaymentIntent fallito', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $exception->getMessage(),
            ]);

            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        if ($intent->status !== 'succeeded') {
            Log::warning('Stripe capture: verifica server-side fallita', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $intent->status,
                'amount_received' => $intent->amount_received,
                'expected_amount' => $expectedAmountCents,
                'currency' => $intent->currency,
            ]);

            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        if ($intent->amount_received !== $expectedAmountCents || $intent->currency !== 'eur') {
            // Incassato ma NON valido (carrello cambiato in un'altra tab fra
            // init e conferma, PI manomesso): i soldi sono transitati — il
            // chiamante DEVE stornare (fundsCaptured + transaction id presenti).
            Log::warning('Stripe capture: incassato ma importo/valuta non validi', [
                'payment_intent_id' => $paymentIntentId,
                'amount_received' => $intent->amount_received,
                'expected_amount' => $expectedAmountCents,
                'currency' => $intent->currency,
            ]);

            return CheckoutCaptureResult::capturedButInvalid(
                errorMessage: __('payment.errors.amount_changed'),
                gatewaySessionId: $intent->id,
                transactionId: $intent->id,
                provider: 'stripe',
                capturedAmountCents: (int) $intent->amount_received,
            );
        }

        return CheckoutCaptureResult::success(
            gatewaySessionId: $intent->id,
            transactionId: $intent->id,
            provider: 'stripe',
            providerResponse: Arr::only($intent->toArray(), self::SAFE_INTENT_FIELDS),
        );
    }

    public function refund(string $transactionId, int $amountCents): void
    {
        $this->client->refunds->create([
            'payment_intent' => $transactionId,
            'amount' => $amountCents,
        ]);
    }

    public function handleWebhook(array $payload, array $headers): ?OrderPayment
    {
        $secret = (string) config('payment.stripe.webhook_secret');

        if ($secret === '') {
            throw PaymentConfigurationException::missing('stripe');
        }

        // Firma verificata sul raw body (il json re-encodato non matcherebbe).
        $event = Webhook::constructEvent(
            $headers['raw_body'][0] ?? json_encode($payload),
            $headers['stripe-signature'][0] ?? $headers['Stripe-Signature'][0] ?? '',
            $secret,
        );

        return match ($event->type) {
            'payment_intent.succeeded' => $this->completeFromIntent($event->data->object),
            'payment_intent.payment_failed' => $this->failFromIntent($event->data->object),
            default => null,
        };
    }

    /** Riconciliazione idempotente: completa il pagamento solo se non lo è già. */
    private function completeFromIntent(StripeObject $intent): ?OrderPayment
    {
        $payment = $this->findPayment($intent);

        if (! $payment) {
            return null;
        }

        if ($payment->status === PaymentStatus::Completed) {
            return $payment;
        }

        $payment->update([
            'status' => PaymentStatus::Completed,
            'transaction_id' => $intent->id,
            'provider_response' => Arr::only($intent->toArray(), self::SAFE_INTENT_FIELDS),
            'paid_at' => now(),
        ]);

        return $payment->fresh();
    }

    private function failFromIntent(StripeObject $intent): ?OrderPayment
    {
        $payment = $this->findPayment($intent);

        if (! $payment) {
            return null;
        }

        // Già incassato (capture o webhook succeeded): non degradare lo stato.
        if ($payment->status === PaymentStatus::Completed) {
            return $payment;
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'provider_response' => Arr::only($intent->toArray(), self::SAFE_INTENT_FIELDS),
        ]);

        return $payment->fresh();
    }

    private function findPayment(StripeObject $intent): ?OrderPayment
    {
        $payment = OrderPayment::query()
            ->where('gateway_session_id', $intent->id)
            ->first();

        if (! $payment) {
            // PI mai registrato lato app (limite capture-first): refund
            // manuale da dashboard.
            Log::warning('Stripe webhook: PaymentIntent sconosciuto', [
                'payment_intent_id' => $intent->id,
            ]);
        }

        return $payment;
    }
}
