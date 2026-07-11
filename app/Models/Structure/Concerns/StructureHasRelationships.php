<?php

namespace App\Models\Structure\Concerns;

use App\Models\Region\Region;
use App\Models\Structure\StructureClosure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait StructureHasRelationships
{
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** Giorni di chiusura (calendario disponibilità, step 3). */
    public function closures(): HasMany
    {
        return $this->hasMany(StructureClosure::class);
    }

    /** Partner proprietario (null per le righe seedate della piattaforma). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Draft di origine: la ri-pubblicazione aggiorna la stessa riga (unique su structure_draft_id). */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(StructureDraft::class, 'structure_draft_id');
    }
}
