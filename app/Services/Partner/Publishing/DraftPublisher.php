<?php

namespace App\Services\Partner\Publishing;

use App\Exceptions\PartnerNotPayableException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
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
     * @throws PartnerNotPayableException onboarding Stripe del partner incompleto
     */
    public function publish(StructureDraft $draft): ?Model
    {
        if (! $this->isPublishable($draft)) {
            return null;
        }

        // La bozza è pronta ma il partner non ha un account connesso: il
        // prodotto sarebbe invendibile e il checkout esploderebbe invece di
        // degradare a commissione zero. Meglio non pubblicare e dirlo.
        if ($draft->user?->partnerProfile?->canBePaid() !== true) {
            throw PartnerNotPayableException::onboardingIncomplete();
        }

        return match ($draft->family()) {
            'attivita' => $this->events->publish($draft),
            'smartbox' => $this->smartboxes->publish($draft),
            default => $this->structures->publish($draft),
        };
    }

    /** Rimuove la riga catalogo del draft (eliminazione da "I miei servizi"). */
    public function unpublish(StructureDraft $draft): void
    {
        $published = match ($draft->family()) {
            'attivita' => Event::query()->firstWhere('structure_draft_id', $draft->id),
            'smartbox' => SmartboxPackage::query()->firstWhere('structure_draft_id', $draft->id),
            default => Structure::query()->firstWhere('structure_draft_id', $draft->id),
        };

        if ($published !== null) {
            // Il pivot amenityables non ha cascade sul lato morph: pulizia esplicita.
            $published->amenities()->detach();
            $published->delete();
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
            'attivita' => $draft->date_start !== null,
            'smartbox' => filled($draft->price),
            default => filled($draft->rooms),
        };
    }
}
