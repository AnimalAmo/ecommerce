<?php

namespace App\Http\Controllers\Webhook;

use App\Services\Payment\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Riceve i webhook Stripe: passa raw body + header al gateway. 200 se il
 * gateway gestisce o ignora l'evento (null incluso), 400 su firma invalida
 * o configurazione mancante. Il gateway è risolto dentro il try: anche la
 * PaymentConfigurationException del bind StripeClient diventa un 400.
 */
class StripeWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            app(StripeGateway::class)->handleWebhook(
                $request->all(),
                array_merge($request->headers->all(), [
                    'raw_body' => [$request->getContent()],
                ]),
            );
        } catch (Throwable $exception) {
            Log::error('Stripe webhook rifiutato', ['error' => $exception->getMessage()]);

            return response()->json(['error' => 'invalid webhook'], 400);
        }

        return response()->json(['status' => 'ok']);
    }
}
