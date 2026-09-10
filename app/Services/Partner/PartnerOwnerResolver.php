<?php

namespace App\Services\Partner;

use Illuminate\Database\Eloquent\Model;

/**
 * Proprietario di un prodotto acquistabile. Le tre tabelle di catalogo
 * (structures, events, smartbox_packages) hanno tutte user_id nullable con
 * nullOnDelete: le righe demo ce l'hanno nullo, ed è il caso che va
 * intercettato prima del checkout, non durante.
 */
class PartnerOwnerResolver
{
    public function ownerIdFor(Model $purchasable): ?int
    {
        $ownerId = $purchasable->getAttribute('user_id');

        return $ownerId === null ? null : (int) $ownerId;
    }
}
