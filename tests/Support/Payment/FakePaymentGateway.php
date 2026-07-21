<?php

namespace Tests\Support\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Enums\PaymentMethod;
use App\Models\OrderPayment\OrderPayment;
use RuntimeException;

/**
 * Gateway fittizio per i test del checkout: nessuna rete, esito capture
 * configurabile, chiamate registrate (init/capture/refund) per le assert.
 * Va bindato nel container al posto di StripeGateway.
 */
class FakePaymentGateway implements PaymentGatewayInterface
{
    public bool $captureSucceeds = true;

    /** Simula un errore API all'init: sessione nulla, checkout in paymentUnavailable. */
    public bool $initThrows = false;

    /** Come sopra ma SOLO col contesto carta salvata (pm staccato su Stripe). */
    public bool $initThrowsWithSavedCard = false;

    /** Simula l'"incassato ma non valido" (importo/valuta cambiati): failure con fundsCaptured. */
    public bool $captureAmountMismatch = false;

    /** Importo "realmente incassato" riportato nel mismatch. */
    public int $mismatchCapturedAmountCents = 12300;

    /** @var list<array{amount_cents: int, method: string, context: array}> */
    public array $initCalls = [];

    /** @var list<array{payload: array, expected_amount_cents: int}> */
    public array $captureCalls = [];

    /** @var list<array{transaction_id: string, amount_cents: int}> */
    public array $refundCalls = [];

    public function initPaymentSession(int $amountCents, PaymentMethod $method, array $context = []): array
    {
        $this->initCalls[] = [
            'amount_cents' => $amountCents,
            'method' => $method->value,
            'context' => $context,
        ];

        if ($this->initThrows || ($this->initThrowsWithSavedCard && isset($context['payment_method_id']))) {
            throw new RuntimeException('init failed');
        }

        return [
            'client_secret' => 'cs_fake_secret',
            'payment_intent_id' => 'pi_fake_1',
        ];
    }

    public function captureFromCheckout(array $payload, int $expectedAmountCents): CheckoutCaptureResult
    {
        $this->captureCalls[] = [
            'payload' => $payload,
            'expected_amount_cents' => $expectedAmountCents,
        ];

        if ($this->captureAmountMismatch) {
            return CheckoutCaptureResult::capturedButInvalid(
                errorMessage: __('payment.errors.amount_changed'),
                gatewaySessionId: 'pi_fake_1',
                transactionId: 'pi_fake_1',
                provider: 'fake',
                capturedAmountCents: $this->mismatchCapturedAmountCents,
            );
        }

        if (! $this->captureSucceeds) {
            return CheckoutCaptureResult::failure(__('payment.errors.capture_failed'));
        }

        return CheckoutCaptureResult::success(
            gatewaySessionId: 'pi_fake_1',
            transactionId: 'pi_fake_1',
            provider: 'fake',
            providerResponse: ['status' => 'succeeded'],
        );
    }

    public function refund(string $transactionId, int $amountCents): void
    {
        $this->refundCalls[] = [
            'transaction_id' => $transactionId,
            'amount_cents' => $amountCents,
        ];
    }

    public function handleWebhook(array $payload, array $headers): ?OrderPayment
    {
        return null;
    }
}
