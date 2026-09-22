<?php

namespace App\Services\Admin\People;

use App\Models\User;

/**
 * Esito di PartnerAccountService::create(): l'account, se era già un cliente
 * e cosa non è andato a buon fine dopo il commit. Serve a chi chiama per
 * scegliere il messaggio ("creato", "promosso", o l'avviso) e la variante
 * della mail di benvenuto.
 */
final readonly class CreatedPartner
{
    public function __construct(
        public User $user,
        public bool $promoted,
        /** false: set() è fallito, il profilo ha la modalità di default (online). */
        public bool $paymentModeSaved = true,
        /** false: la mail di benvenuto non è partita, va rimandata dalla scheda. */
        public bool $welcomeSent = true,
    ) {}
}
