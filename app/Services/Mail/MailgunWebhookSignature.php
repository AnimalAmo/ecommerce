<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Cache;
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

    /**
     * La firma copre timestamp e token, non l'evento: dentro la finestra una
     * firma catturata si potrebbe riusare con un evento qualsiasi (e ora un
     * evento disiscrive o sopprime un indirizzo della newsletter). Mailgun
     * consiglia di rifiutare un token già visto. False = già visto.
     *
     * @param  array<string, mixed>  $signature
     */
    public function claimToken(array $signature): bool
    {
        return Cache::add($this->tokenKey($signature), true, self::TOLERANCE_SECONDS * 2);
    }

    /**
     * Elaborazione fallita: il token torna libero, così il nuovo tentativo di
     * Mailgun non viene scambiato per un replay.
     *
     * @param  array<string, mixed>  $signature
     */
    public function releaseToken(array $signature): void
    {
        Cache::forget($this->tokenKey($signature));
    }

    /** @param  array<string, mixed>  $signature */
    private function tokenKey(array $signature): string
    {
        return 'mailgun-webhook-token:'.hash('sha256', (string) ($signature['token'] ?? ''));
    }
}
