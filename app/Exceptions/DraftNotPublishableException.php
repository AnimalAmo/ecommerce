<?php

namespace App\Exceptions;

use App\Models\Structure\StructureDraft;
use RuntimeException;

/**
 * La bozza non ha i dati minimi per il catalogo (DraftPublisher::isPublishable:
 * nome italiano, più stanze, data d'inizio o prezzo secondo la famiglia).
 * Prima DraftPublisher tornava null in silenzio e il wizard segnava lo stesso
 * la bozza `completed`: compariva in "I miei servizi" senza esistere sul B2C.
 */
class DraftNotPublishableException extends RuntimeException
{
    /** Bozza rifiutata, per i log di chi pubblica fuori dal wizard. */
    public ?int $draftId = null;

    public static function forDraft(StructureDraft $draft): self
    {
        $exception = new self(__('partner.errors.draft_not_publishable'));
        $exception->draftId = $draft->id;

        return $exception;
    }
}
