<?php

namespace App\Livewire\Concerns;

use App\Enums\OrderPaymentMode;
use App\Exceptions\CartValidationException;
use App\Services\Cart\CartManager;
use App\Services\Partner\PartnerPaymentModeService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Model;

/**
 * Ingresso unico del catalogo nel carrello: le cinque schede di dettaglio
 * (struttura, servizio, attività, evento, smartbox) e l'aggiunta rapida delle
 * griglie (AddsEventToCart) passano tutte da addCatalogProductToCart().
 *
 * Difetto C7 (audit 27/09/2026, corretto il 28/09/2026): la guardia sul
 * pagamento diretto stava solo nelle griglie e nei preferiti. Nelle schede di
 * un partner che incassa in struttura la CTA non c'è, ma `addToCart()` arriva
 * dal payload del client: una chiamata forgiata metteva la riga in carrello, e
 * da quel momento CartManager::guardSinglePartner legava il carrello a quel
 * partner e rifiutava ogni prodotto di un altro venditore («un ordine, un
 * venditore»), finché il cliente non indovinava di dover cancellare una riga
 * che non aveva voluto. Cinque copie della guardia sarebbero state cinque
 * posti in cui dimenticarla alla prossima scheda: sta qui, una volta.
 *
 * Perché qui e non nel CartManager (stessa decisione di FavoriteService, che
 * ha la sua copia perché è un service e non un componente): addItem() lo usa
 * anche il merge del carrello al login (MergeCartOnLogin), che riporta righe
 * già scelte, e il carrello è la base del ramo «prenota e paga in struttura»
 * del checkout. Lì la guardia taglierebbe quella modalità anche quando la
 * cliente deciderà di riaccenderla — oggi è spenta da config, non cancellata.
 * Il catalogo invece la CTA non la disegna mai per chi incassa in struttura,
 * con o senza kill-switch: la guardia rispecchia esattamente quello che la
 * pagina offre.
 *
 * Il metodo è protected di proposito: un metodo pubblico di un componente
 * Livewire si chiama dal client con gli argomenti che vuole.
 */
trait AddsCatalogProductToCart
{
    /**
     * Aggiunge il prodotto al carrello e avvisa il badge. Qualunque rifiuto
     * (pagamento diretto, disponibilità, un ordine un venditore) diventa un
     * toast danger e la riga non entra.
     *
     * @param  Model  $purchasable  Structure, Event o SmartboxPackage già risolto dal componente
     * @param  array<string, mixed>  $options  options del widget, nel vocabolario della famiglia
     * @return bool true solo se la riga è entrata: il chiamante apre il pop-up o il toast di conferma
     */
    protected function addCatalogProductToCart(Model $purchasable, array $options, bool $isGift = false): bool
    {
        if (app(PartnerPaymentModeService::class)->forPurchasable($purchasable) === OrderPaymentMode::OnSite) {
            // Il regalo tiene il suo messaggio, lo stesso di CartManager::guardGiftIsPaidOnline:
            // la smartbox aperta con ?regalo=1 prima che il partner passasse al pagamento diretto.
            $refusal = $isGift
                ? CartValidationException::giftRequiresOnlinePayment()
                : CartValidationException::notPurchasable();

            Flux::toast(text: $refusal->getMessage(), variant: 'danger');

            return false;
        }

        try {
            // Alias dalla morph map (enforced): 'structure', 'event', 'smartbox_package'.
            app(CartManager::class)->addItem($purchasable->getMorphClass(), (int) $purchasable->getKey(), $options, $isGift);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return false;
        }

        $this->dispatch('cart-updated');

        return true;
    }
}
