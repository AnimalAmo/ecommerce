<?php

namespace App\Http\Controllers\Webhook;

use App\Services\Payment\PaypalGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Riceve i webhook PayPal: passa payload + header (con raw body) al gateway.
 * 200 se il gateway gestisce o ignora l'evento (null incluso), 400 su firma
 * invalida o configurazione mancante.
 */
class PaypalWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            app(PaypalGateway::class)->handleWebhook(
                $request->all(),
                array_merge($request->headers->all(), [
                    'raw_body' => [$request->getContent()],
                ]),
            );
        } catch (Throwable $exception) {
            Log::error('PayPal webhook rifiutato', ['error' => $exception->getMessage()]);

            return response()->json(['error' => 'invalid webhook'], 400);
        }

        return response()->json(['status' => 'ok']);
    }
}
