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
            $event = $this->eventHints($request);

            Log::error('Stripe webhook rifiutato', [
                'error' => $exception->getMessage(),
                // Con Connect sullo stesso URL vivono DUE endpoint — eventi di
                // piattaforma ed eventi degli account connessi — ognuno col suo
                // `whsec_`. Senza questo header il log non dice quale dei due
                // sta rifiutando, e si finisce a rigenerare il segreto sbagliato.
                // Vuoto (null) = consegna di piattaforma.
                'stripe_account' => $request->header('Stripe-Account'),
                // ATTENZIONE: id e type NON sono autenticati (si veda eventHints).
                'event_id' => $event['id'],
                'event_type' => $event['type'],
            ]);

            return response()->json(['error' => 'invalid webhook'], 400);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Id e tipo dell'evento pescati dal raw body.
     *
     * Qui la firma è già stata rifiutata: il payload NON è passato da
     * `Webhook::constructEvent()`, quindi questi due valori **non sono
     * autenticati** e chiunque può scriverci quello che vuole. Servono a una
     * cosa sola: capire quale consegna sta fallendo e ritrovarla nella dashboard
     * Stripe. Non vanno usati per decidere nulla, né per cercare righe a DB.
     *
     * Il decode è difensivo perché il body può non essere JSON affatto — ed è
     * anzi una delle ragioni per cui la firma non torna: in quel caso le due
     * chiavi restano null e il log dice comunque quale endpoint ha rifiutato.
     *
     * @return array{id: ?string, type: ?string}
     */
    private function eventHints(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return ['id' => null, 'type' => null];
        }

        return [
            'id' => is_string($payload['id'] ?? null) ? $payload['id'] : null,
            'type' => is_string($payload['type'] ?? null) ? $payload['type'] : null,
        ];
    }
}
