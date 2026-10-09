<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Il pannello non può mandare il link per una nuova password a questo
 * iscritto. Come PartnerAccountException il messaggio è già la frase per
 * l'amministratore, in italiano forzato.
 */
class AdminPasswordLinkException extends RuntimeException
{
    public static function superadmin(): self
    {
        return new self(__('admin-people.users.errors.superadmin', [], 'it'));
    }

    public static function inactive(): self
    {
        return new self(__('admin-people.users.password_link.errors.inactive', [], 'it'));
    }

    public static function throttled(): self
    {
        return new self(__('admin-people.users.password_link.errors.throttled', [], 'it'));
    }
}
