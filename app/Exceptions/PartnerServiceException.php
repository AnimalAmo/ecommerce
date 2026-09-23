<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

/**
 * Il partner a cui l'admin sta intestando una scheda non è ricevibile: non ha
 * il ruolo `partner`, è disattivato, è anonimizzato, oppure non ha un profilo
 * aziendale (e allora non si sa nemmeno come verrebbe pagato).
 *
 * Il caso non nasce dall'interfaccia — il select offre solo chi è idoneo — ma
 * dal tempo che passa fra l'apertura della pagina e il salvataggio, e da un
 * `?partner=` scritto a mano nell'indirizzo. Il messaggio è in italiano e
 * forzato: il pannello non cambia lingua, e il locale in quel momento può
 * essere quello del sito pubblico.
 */
class PartnerServiceException extends RuntimeException
{
    /** Partner rifiutato, per i log di chi crea schede fuori dal wizard. */
    public ?int $partnerId = null;

    public static function notEligible(User $partner): self
    {
        $name = $partner->partnerProfile?->business_name ?: $partner->name;

        $exception = new self(__('admin-catalog.create.not_eligible', ['name' => $name], 'it'));
        $exception->partnerId = $partner->id;

        return $exception;
    }
}
