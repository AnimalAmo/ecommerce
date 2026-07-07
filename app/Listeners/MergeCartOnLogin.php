<?php

namespace App\Listeners;

use App\Exceptions\CartValidationException;
use App\Services\Cart\CartManager;
use App\Services\Cart\SessionCartStorage;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Al login riversa il carrello guest di sessione in quello database
 * (auto-discovery: nessuna registrazione manuale, come i listener matsuri).
 * Ogni riga ripassa da CartManager::addItem, quindi viene rivalidata e
 * riprezzata; le righe non più valide (data chiusa, capienza, prodotto
 * sparito) vengono scartate silenziosamente (solo log). Sessione già vuota
 * (es. re-login da ProfileSecurity) = no-op.
 */
class MergeCartOnLogin
{
    public function __construct(
        private readonly SessionCartStorage $sessionStorage,
        private readonly CartManager $cart,
    ) {}

    public function handle(Login $event): void
    {
        $entries = $this->sessionStorage->getSessionCart();

        if ($entries === []) {
            return;
        }

        foreach ($entries as $entry) {
            try {
                // Auth::check() è già true quando Login viene emesso: il
                // manager scrive nello storage database (dedup incluso).
                $this->cart->addItem(
                    $entry['type'],
                    $entry['id'],
                    $entry['options'] ?? [],
                    (bool) ($entry['is_gift'] ?? false),
                );
            } catch (CartValidationException|HttpException $exception) {
                Log::info('Riga carrello guest scartata al merge', [
                    'user_id' => $event->user->getAuthIdentifier(),
                    'entry' => $entry,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        $this->sessionStorage->clear();
    }
}
