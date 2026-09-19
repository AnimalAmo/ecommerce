<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CatalogVisibleScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stati amministrativi di una scheda a catalogo (strutture, eventi, smartbox):
 * sospensione dal pannello e approvazione preventiva delle pubblicazioni.
 *
 * Il trait registra CatalogVisibleScope: da qui in poi `Structure::query()`
 * vede solo ciò che il sito può mostrare. Per il resto c'è `withHidden()`.
 *
 * @method static Builder withHidden()
 */
trait HasCatalogModeration
{
    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_CHANGES_REQUESTED = 'changes_requested';

    public static function bootHasCatalogModeration(): void
    {
        static::addGlobalScope(new CatalogVisibleScope);
    }

    public function initializeHasCatalogModeration(): void
    {
        // Le scrivono i publisher (updateOrCreate) quando la moderazione è accesa.
        $this->mergeFillable(['approval_status', 'approval_requested_at']);

        $this->mergeCasts([
            'suspended_at' => 'datetime',
            'approval_requested_at' => 'datetime',
            'approved_at' => 'datetime',
        ]);
    }

    /** Tutte le righe, anche sospese o in attesa: pannello, area partner, publisher. */
    public function scopeWithHidden(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CatalogVisibleScope::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /** Visibile e comprabile sul sito: il negativo esatto di CatalogVisibleScope. */
    public function isVisibleInCatalog(): bool
    {
        return ! $this->isSuspended() && $this->isApproved();
    }
}
