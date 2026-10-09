<?php

namespace App\Models\OrderItem\Concerns;

use App\Models\Order\Order;
use App\Models\Scopes\CatalogVisibleScope;
use App\Models\Structure\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait OrderItemHasRelationships
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Stanza prenotata (null per le altre famiglie o se il partner l'ha cancellata). */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** Venditore congelato sulla riga (snapshot: sopravvive al prodotto cancellato). */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    /**
     * Morph nullable verso il catalogo (alias morph map: structure/event/smartbox_package).
     *
     * Senza lo scope di visibilità: un ordine già fatto resta legato alla sua
     * scheda anche se nel frattempo è stata sospesa (prenotazioni del partner,
     * calendario dei payout, recensioni).
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes([CatalogVisibleScope::class]);
    }
}
