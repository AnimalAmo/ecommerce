<?php

namespace App\Listeners;

use App\Exceptions\CartValidationException;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartNotice;
use App\Services\Cart\SessionCartStorage;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Al login riversa il carrello guest di sessione in quello database
 * (auto-discovery: nessuna registrazione manuale, come i listener matsuri).
 * Ogni riga ripassa da CartManager::addItem, quindi viene rivalidata e
 * riprezzata; le righe non più valide (data chiusa, capienza, prodotto
 * sparito, venditore diverso da quello già in carrello) vengono scartate.
 * Sessione già vuota (es. re-login da ProfileSecurity) = no-op.
 *
 * Difetto C9 (audit 28/09/2026): le righe scartate finivano solo in un
 * `Log::info`, e il cliente che accedeva trovava un carrello più corto di
 * quello che aveva riempito senza sapere perché — tipicamente per «un ordine,
 * un venditore», quando il suo carrello salvato era di un altro partner. Ora
 * ogni riga scartata diventa una voce dell'avviso (CartNotice, in sessione:
 * erano righe del carrello ospite), che la pagina Carrello mostra una volta.
 * Il log resta, per chi indaga.
 */
class MergeCartOnLogin
{
    public function __construct(
        private readonly SessionCartStorage $sessionStorage,
        private readonly CartManager $cart,
        private readonly CartNotice $notices,
    ) {}

    public function handle(Login $event): void
    {
        $entries = $this->sessionStorage->getSessionCart();

        if ($entries === []) {
            return;
        }

        // Il manager sceglie lo storage su Auth::check(), e il merge deve finire
        // nel carrello a database. SessionGuard::login() però emette Login PRIMA
        // di setUser(): di solito la guard ritrova l'utente dall'id appena messo
        // in sessione, ma dopo un logout nella stessa richiesta resta sul flag
        // loggedOut e risponde «ospite» — il merge riscriverebbe le righe nella
        // sessione che sta per svuotare, senza rifiutarne né avvisarne nessuna.
        // L'utente è nell'evento: lo si dà alla guard prima di riversare
        // (login() lo rifà subito dopo, è idempotente).
        Auth::guard($event->guard)->setUser($event->user);

        $dropped = [];

        foreach ($entries as $entry) {
            try {
                // Da qui Auth::check() è true: il manager scrive nello storage
                // database (dedup incluso).
                $this->cart->addItem(
                    $entry['type'],
                    $entry['id'],
                    $entry['options'] ?? [],
                    (bool) ($entry['is_gift'] ?? false),
                );
            } catch (CartValidationException|HttpException $exception) {
                $dropped[] = self::noticeFor($entry, $exception);

                Log::info('Riga carrello guest scartata al merge', [
                    'user_id' => $event->user->getAuthIdentifier(),
                    'entry' => $entry,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        $this->sessionStorage->clear();

        // Dopo clear(): l'avviso vive in 'cart_notice', fuori dalle righe.
        $this->notices->remember($dropped);
    }

    /**
     * Voce d'avviso di una riga scartata. CartValidationException porta già il
     * motivo detto al cliente (date chiuse, posti finiti, un solo venditore);
     * un 404 è un prodotto uscito dal catalogo, e si spiega come tale.
     */
    private static function noticeFor(array $entry, CartValidationException|HttpException $exception): array
    {
        $product = CartNotice::findProduct((string) $entry['type'], (int) $entry['id']);

        if ($exception instanceof CartValidationException) {
            return CartNotice::entry($product, CartNotice::REASON_NOT_MERGED, $exception->getMessage());
        }

        return CartNotice::leftCatalog($product);
    }
}
