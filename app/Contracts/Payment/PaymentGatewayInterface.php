<?php

namespace App\Contracts\Payment;

use App\Data\Checkout\CheckoutCaptureResult;
use App\Enums\PaymentMethod;
use App\Models\OrderPayment\OrderPayment;

/**
 * Contratto unico dei gateway di pagamento (capture-first, step 4).
 * L'importo arriva sempre dal CartManager server-side, MAI dal client.
 */
interface PaymentGatewayInterface
{
    /**
     * Crea (o aggiorna, via $context) la sessione di pagamento lato gateway.
     *
     * @return array{client_secret: string, payment_intent_id: string}
     */
    public function initPaymentSession(int $amountCents, PaymentMethod $method, array $context = []): array;

    /**
     * Riverifica server-side il pagamento confermato dal client
     * (status/importo/valuta): mai fidarsi del payload del browser.
     */
    public function captureFromCheckout(array $payload, int $expectedAmountCents): CheckoutCaptureResult;

    /**
     * Storno pieno per transaction id: usato dal guard sold-out post-capture,
     * quando l'OrderPayment non esiste ancora (rollback della pipeline).
     */
    public function refund(string $transactionId, int $amountCents): void;

    /**
     * Riconciliazione idempotente da webhook. Null = evento ignoto/ignorato
     * (il controller risponde comunque 200).
     */
    public function handleWebhook(array $payload, array $headers): ?OrderPayment;
}
