<?php

namespace App\Services\Partner\Publishing;

use App\Exceptions\PartnerNotPayableException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Services\Admin\Catalog\CatalogAdmin;
use Illuminate\Database\Eloquent\Model;

/**
 * Entry point della pipeline di pubblicazione: smista il draft completato al
 * publisher della sua famiglia e torna la riga catalogo prodotta/aggiornata.
 *
 * MVP: pubblicazione automatica al completamento del wizard. La spec prevede
 * l'approvazione del superadmin — quando arriverà la moderazione questo entry
 * point andrà spostato dietro un gate (es. status='approved').
 */
class DraftPublisher
{
    public function __construct(
        private readonly StructurePublisher $structures,
        private readonly EventPublisher $events,
        private readonly SmartboxPublisher $smartboxes,
    ) {}

    /**
     * Null quando il draft non è pubblicabile (vedi isPublishable).
     *
     * @throws PartnerNotPayableException partner online con onboarding Stripe incompleto, senza profilo, o smartbox di chi non incassa online
     */
    public function publish(StructureDraft $draft): ?Model
    {
        if (! $this->isPublishable($draft)) {
            return null;
        }

        // La bozza è pronta ma il partner si fa pagare online e non ha un
        // account connesso: il prodotto sarebbe invendibile e il checkout
        // esploderebbe invece di degradare a commissione zero. Chi si fa
        // pagare direttamente (22/09/2026) pubblica senza Stripe. Senza
        // profilo non si sa nemmeno come verrebbe pagato: si rifiuta, come prima.
        $profile = $draft->user?->partnerProfile;

        if ($profile === null) {
            throw PartnerNotPayableException::onboardingIncomplete();
        }

        $family = $draft->family();

        // Il gate è per famiglia: la smartbox è un cofanetto prepagato e vuole
        // l'incasso online (richiesta della cliente, 27/09/2026). Il messaggio
        // cambia con la causa: chi incassa fuori non deve leggere «completa il
        // collegamento Stripe», che gli farebbe cercare un onboarding a metà.
        if (! $profile->canPublishFamily($family)) {
            throw $family === 'smartbox' && ! $profile->requiresOnlinePayment()
                ? PartnerNotPayableException::smartboxRequiresOnlinePayment()
                : PartnerNotPayableException::onboardingIncomplete();
        }

        $published = match ($family) {
            'attivita' => $this->events->publish($draft),
            'smartbox' => $this->smartboxes->publish($draft),
            default => $this->structures->publish($draft),
        };

        // Una bozza è un servizio solo. Se ha cambiato famiglia (lo step del
        // tipo la riscrive dal 28/09/2026, difetto F5), la riga dell'altra
        // famiglia è la versione vecchia dello stesso servizio: lasciata lì,
        // un evento diventato hotel restava prenotabile come evento (tester,
        // 28/09/2026).
        $this->removeRows($draft, except: $published::class);

        return $published;
    }

    /** Rimuove le righe catalogo del draft (eliminazione da "I miei servizi"). */
    public function unpublish(StructureDraft $draft): void
    {
        // Tutte le famiglie, non solo quella attuale: dopo un cambio di ramo la
        // riga può essere rimasta nell'altra (vedi publish()).
        $this->removeRows($draft);
    }

    /**
     * Toglie dal catalogo le righe collegate alla bozza, in ogni famiglia
     * tranne $except. Gli ordini restano: order_items conserva titolo, foto
     * e prezzo, come per la cancellazione dal pannello.
     *
     * Una riga con prenotazioni future non si cancella: si ritira dalla
     * vetrina (review del 28/09/2026). Cancellata, le sue prenotazioni
     * sparivano da «Prenotazioni» del partner, che le legge attraverso la riga
     * viva, e il dettaglio col contatto del cliente rispondeva 403: un
     * cliente che ha pagato, e un partner che non lo sa. Ritirata, esce dal
     * sito e dai carrelli ma resta leggibile a chi la deve onorare. È la
     * stessa soglia che blocca la cancellazione dal pannello.
     *
     * @param  class-string<Model>|null  $except
     */
    private function removeRows(StructureDraft $draft, ?string $except = null): void
    {
        foreach ([Structure::class, Event::class, SmartboxPackage::class] as $model) {
            if ($model === $except) {
                continue;
            }

            $row = $model::withHidden()->firstWhere('structure_draft_id', $draft->id);

            if ($row === null) {
                continue;
            }

            if (app(CatalogAdmin::class)->bookings($row)['future'] > 0) {
                $row->update(['withheld_at' => $row->withheld_at ?? now()]);

                continue;
            }

            // Il pivot amenityables non ha cascade sul lato morph: pulizia esplicita.
            $row->amenities()->detach();
            $row->delete();
        }
    }

    /**
     * Requisiti minimi per andare a catalogo: gli step finali del wizard sono
     * raggiungibili via URL diretto (rotte pubbliche), quindi un draft può
     * arrivare "completed" saltando gli step — senza questo gate finirebbero
     * card vuote sul B2C pubblico.
     */
    private function isPublishable(StructureDraft $draft): bool
    {
        if (blank($draft->getTranslation('name', 'it'))) {
            return false;
        }

        return match ($draft->family()) {
            // Data obbligatoria solo per gli eventi: un'attività o un servizio
            // professionale (dog sitter, toelettatore) può non averne una
            // (risposta della cliente, 27/09/2026), e pretenderla lo teneva
            // fuori dal catalogo per sempre.
            'attivita' => $draft->type !== 'eventi' || $draft->date_start !== null,
            'smartbox' => filled($draft->price),
            default => filled($draft->rooms),
        };
    }
}
