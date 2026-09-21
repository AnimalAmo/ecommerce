<?php

namespace App\Http\Controllers\Webhook;

use App\Services\Mail\MailgunWebhookSignature;
use App\Services\Mail\RecordMailgunEvent;
use App\Services\Newsletter\NewsletterFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riceve gli eventi di consegna Mailgun: verifica la firma e passa l'evento ai
 * service — il registro delle consegne e, per rimbalzi, reclami e aperture,
 * la newsletter. 403 su firma non valida, 200 su tutto il resto — un non-2xx
 * fa ritentare Mailgun per ore, e un evento che non ci interessa non è un
 * errore.
 */
class MailgunWebhookController
{
    public function __invoke(
        Request $request,
        MailgunWebhookSignature $signature,
        RecordMailgunEvent $recorder,
        NewsletterFeedback $newsletter,
    ): JsonResponse {
        if (! $signature->isValid((array) $request->input('signature', []))) {
            return response()->json(['error' => 'invalid signature'], 403);
        }

        $event = (array) $request->input('event-data', []);

        $delivery = $recorder->record($event);
        $handled = $newsletter->handle($event);

        return response()->json(['status' => $delivery === null && ! $handled ? 'ignored' : 'ok']);
    }
}
