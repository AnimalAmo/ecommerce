<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Codici dei gateway abilitati (tabella payment_gateways): filtrano le righe
 * metodo al checkout e pilotano la registrazione delle rotte webhook.
 */
class PaymentGatewayService
{
    private const CACHE_KEY = 'payment_gateways.enabled';

    private const CACHE_TTL_SECONDS = 300;

    /** @return list<string> */
    public function enabledCodes(): array
    {
        try {
            // Guardia pre-migrazione (boot del provider a tabella assente).
            if (! Schema::hasTable('payment_gateways')) {
                return [];
            }

            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
                return PaymentGateway::query()
                    ->enabled()
                    ->ordered()
                    ->pluck('code')
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
