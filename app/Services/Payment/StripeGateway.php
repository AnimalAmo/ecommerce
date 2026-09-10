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
use InvalidArgumentException;
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
        $options = $this->accountOptions($context['stripe_account_id'] ?? null);

        // Provvigione trattenuta all'origine. Sotto soglia il parametro va
        // OMESSO: Stripe vuole un application_fee_amount "positive and less
        // than the amount of the charge", e lo zero è un invalid_request_error.
        $fee = $context['application_fee_amount'] ?? null;
        $applicationFee = $fee !== null && $fee > 0 ? ['application_fee_amount' => $fee] : [];

        if (isset($context['payment_intent_id'])) {
            // Aggiornamento: il payment method già allegato resta dov'è, e la
            // provvigione si ricalcola perché l'importo può essere cambiato.
            $intent = $this->client->paymentIntents->update($context['payment_intent_id'], [
                'amount' => $amountCents,
                'payment_method_types' => $types,
                ...$applicationFee,
            ], $options);

            return [
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ];
        }

        $intent = $this->client->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'eur',
            'payment_method_types' => $types,
            ...$this->savedCardParams($context, $options),
            ...$applicationFee,
        ], $options);

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    /**
     * Carta salvata su un direct charge: il PaymentMethod è della piattaforma
     * e non è utilizzabile sull'account connesso, va clonato lì
     * (`/connect/direct-charges-multiple-accounts`). Il customer NON si passa:
     * appartiene alla piattaforma, il PaymentMethod clonato no.
     */
    private function savedCardParams(array $context, array $options): array
    {
        if (! isset($context['customer_id'], $context['payment_method_id'])) {
            return [];
        }

        $cloned = $this->client->paymentMethods->create([
            'customer' => $context['customer_id'],
            'payment_method' => $context['payment_method_id'],
        ], $options);

        return ['payment_method' => $cloned->id];
    }

    /**
     * Header Stripe-Account del venditore. Obbligatorio: senza, l'incasso
     * nascerebbe sul conto della piattaforma — esattamente ciò che il modello
     * fiscale concordato esclude.
     */
    private function accountOptions(?string $stripeAccountId): array
    {
        if ($stripeAccountId === null || $stripeAccountId === '') {
            throw new InvalidArgumentException('Direct charge senza account connesso: manca stripe_account_id.');
        }

        return ['stripe_account' => $stripeAccountId];
    }

    public function captureFromCheckout(array $payload, int $expectedAmountCents, ?string $stripeAccountId = null): CheckoutCaptureResult
    {
        $paymentIntentId = (string) ($payload['payment_intent_id'] ?? '');

        if ($paymentIntentId === '') {
            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        try {
            $intent = $this->client->paymentIntents->retrieve(
                $paymentIntentId,
                [],
                $this->accountOptions($stripeAccountId),
            );
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

    /**
     * Storno emesso come l'account connesso (è lì che vive l'addebito).
     * refund_application_fee: senza, la provvigione resta ad AnimalAmo e a
     * perderla è il partner. Rimborso totale = fee intera, parziale = quota
     * proporzionale, che con un'aliquota unica per ordine è l'importo giusto.
     */
    public function refund(string $transactionId, int $amountCents, ?string $stripeAccountId = null): void
    {
        $this->client->refunds->create([
            'payment_intent' => $transactionId,
            'amount' => $amountCents,
            'refund_application_fee' => true,
        ], $this->accountOptions($stripeAccountId));
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
            // Evento dell'account connesso, non di un incasso: aggiorna
            // l'anagrafica del partner e non tocca nessun OrderPayment.
            'account.updated' => $this->syncConnectedAccount($event->data->object),
            default => null,
        };
    }

    /**
     * Specchia su partner_profiles lo stato dell'account connesso. Torna null:
     * nessun pagamento è coinvolto, e il controller risponde comunque 200.
     */
    private function syncConnectedAccount(StripeObject $account): ?OrderPayment
    {
        app(StripeConnectService::class)->syncAccountState((string) $account->id);

        return null;
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
