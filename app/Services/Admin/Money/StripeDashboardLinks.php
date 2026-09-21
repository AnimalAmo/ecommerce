<?php

namespace App\Services\Admin\Money;

/**
 * Link alla Stripe Dashboard della piattaforma.
 *
 * Con i direct charges pagamenti e bonifici vivono sull'account connesso del
 * partner: dalla piattaforma si arriva alla sua scheda sotto Connect, che ne
 * mostra saldo, pagamenti e bonifici. Il singolo payout di un account
 * Standard non ha un indirizzo raggiungibile dalla piattaforma, per questo il
 * link è all'account e non al bonifico.
 *
 * Con una chiave di test la Dashboard vuole il prefisso /test: senza, il link
 * apre la modalità live e l'account "non esiste".
 */
class StripeDashboardLinks
{
    private const BASE = 'https://dashboard.stripe.com';

    public function account(string $accountId): string
    {
        return $this->base().'/connect/accounts/'.rawurlencode($accountId);
    }

    /** Elenco degli account connessi: il punto d'ingresso per tutti i bonifici. */
    public function accounts(): string
    {
        return $this->base().'/connect/accounts/overview';
    }

    private function base(): string
    {
        $secret = (string) config('payment.stripe.secret');

        return str_starts_with($secret, 'sk_test_') || str_starts_with($secret, 'rk_test_')
            ? self::BASE.'/test'
            : self::BASE;
    }
}
