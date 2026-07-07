<?php

namespace App\Data\Checkout;

/**
 * Esito della verifica server-side del pagamento (captureFromCheckout).
 * Port da matsuri-nerd: value object immutabile, nessuna dipendenza Eloquent.
 *
 * fundsCaptured distingue il "non incassato" dall'"incassato ma non valido"
 * (importo/valuta diversi dall'atteso): nel secondo caso i soldi sono
 * transitati e il chiamante DEVE stornare col transactionId presente anche
 * sulla failure.
 */
class CheckoutCaptureResult
{
    public function __construct(
        public readonly bool $succeeded,
        public readonly ?string $gatewaySessionId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $provider = null,
        public readonly ?array $providerResponse = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $fundsCaptured = false,
        /** Importo realmente incassato (per lo storno pieno sui mismatch). */
        public readonly ?int $capturedAmountCents = null,
    ) {}

    public static function success(
        string $gatewaySessionId,
        string $transactionId,
        string $provider,
        array $providerResponse,
    ): self {
        return new self(
            succeeded: true,
            gatewaySessionId: $gatewaySessionId,
            transactionId: $transactionId,
            provider: $provider,
            providerResponse: $providerResponse,
            fundsCaptured: true,
        );
    }

    public static function failure(string $errorMessage): self
    {
        return new self(
            succeeded: false,
            errorMessage: $errorMessage,
        );
    }

    /** Incasso avvenuto ma NON valido (amount/currency mismatch): da stornare subito. */
    public static function capturedButInvalid(
        string $errorMessage,
        string $gatewaySessionId,
        string $transactionId,
        string $provider,
        ?int $capturedAmountCents = null,
    ): self {
        return new self(
            succeeded: false,
            gatewaySessionId: $gatewaySessionId,
            transactionId: $transactionId,
            provider: $provider,
            errorMessage: $errorMessage,
            fundsCaptured: true,
            capturedAmountCents: $capturedAmountCents,
        );
    }
}
