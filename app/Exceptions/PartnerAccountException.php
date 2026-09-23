<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

/**
 * Il pannello non può creare (o rimandare il link a) questo partner. Il
 * messaggio è già la frase da mostrare all'amministratore, in italiano
 * forzato: il pannello è solo italiano, e un'eccezione nata in una richiesta
 * Livewire senza il middleware di lingua (test, code) uscirebbe in inglese.
 */
class PartnerAccountException extends RuntimeException
{
    /** L'account che ha già l'email, per il link alla sua scheda. */
    public ?User $user = null;

    public static function superadmin(): self
    {
        return new self(__('admin-people.partner_create.errors.superadmin', [], 'it'));
    }

    public static function alreadyPartner(User $user): self
    {
        $exception = new self(__('admin-people.partner_create.errors.already_partner', [], 'it'));
        $exception->user = $user;

        return $exception;
    }

    public static function inactive(): self
    {
        return new self(__('admin-people.partner_create.errors.inactive', [], 'it'));
    }

    public static function notPartner(): self
    {
        return new self(__('admin-people.partner_create.errors.not_partner', [], 'it'));
    }

    public static function throttled(): self
    {
        return new self(__('admin-people.partner_create.errors.throttled', [], 'it'));
    }
}
