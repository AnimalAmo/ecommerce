<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;

/**
 * Firma dei webhook Mailgun: HMAC-SHA256 di timestamp+token con la signing key
 * del dominio (Mailgun → Webhooks → HTTP webhook signing key).
 *
 * La finestra sul timestamp non è un dettaglio: senza, una richiesta valida
 * catturata una volta resta riproducibile per sempre, e chiunque potrebbe
 * marcare come "fallite" le consegne riuscite.
 */
class MailgunWebhookSignature
{
    private const TOLERANCE_SECONDS = 900;

    /** @param  array<string, mixed>  $signature */
    public function isValid(array $signature): bool
    {
        $key = (string) config('services.mailgun.webhook_signing_key');

        if ($key === '') {
            Log::warning('Webhook Mailgun ricevuto senza MAILGUN_WEBHOOK_SIGNING_KEY configurata');

            return false;
        }

        $timestamp = (string) ($signature['timestamp'] ?? '');
        $token = (string) ($signature['token'] ?? '');
        $received = (string) ($signature['signature'] ?? '');

        if ($timestamp === '' || $token === '' || $received === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.$token, $key), $received);
    }
}
