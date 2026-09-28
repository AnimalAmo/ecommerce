<?php

namespace App\Models\CartItem\Concerns;

use App\Models\Cart\Cart;
use App\Models\Scopes\CatalogVisibleScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait CartItemHasRelationships
{
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Prodotto acquistabile: structure/event/smartbox_package (morph map).
     *
     * Senza lo scope di visibilità, come `OrderItem::purchasable()`, ma per
     * una ragione diversa. Difetto C9 (audit 28/09/2026): il `morphTo()` nudo
     * ereditava CatalogVisibleScope, quindi una riga ritirata o sospesa tornava
     * null e DatabaseCartStorage la saltava senza dire niente, lasciandola a
     * database per sempre. Qui il prodotto torna anche se è uscito dal
     * catalogo, così lo storage sa CHE COSA togliere e PERCHÉ (ritirato,
     * sospeso) e lo dice al cliente. Chi legge questa relazione deve quindi
     * guardare `isVisibleInCatalog()` prima di vendere: lo fa lo storage.
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes([CatalogVisibleScope::class]);
    }
}
