<?php

namespace App\Http\Controllers\Webhook;

use App\Services\Mail\MailgunWebhookSignature;
use App\Services\Mail\RecordMailgunEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riceve gli eventi di consegna Mailgun: verifica la firma e passa l'evento al
 * service. 403 su firma non valida, 200 su tutto il resto — un non-2xx fa
 * ritentare Mailgun per ore, e un evento che non ci interessa non è un errore.
 */
class MailgunWebhookController
{
    public function __invoke(
        Request $request,
        MailgunWebhookSignature $signature,
        RecordMailgunEvent $recorder,
    ): JsonResponse {
        if (! $signature->isValid((array) $request->input('signature', []))) {
            return response()->json(['error' => 'invalid signature'], 403);
        }

        $delivery = $recorder->record((array) $request->input('event-data', []));

        return response()->json(['status' => $delivery === null ? 'ignored' : 'ok']);
    }
}
