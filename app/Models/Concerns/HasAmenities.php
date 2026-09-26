<?php

namespace App\Models\Concerns;

use App\Models\Amenity\Amenity;
use App\Services\Partner\Publishing\FamilyPublisher;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasAmenities
{
    public function amenities(): MorphToMany
    {
        return $this->morphToMany(Amenity::class, 'amenityable')
            ->withPivot(['included', 'position'])
            ->orderByPivot('position');
    }

    /**
     * Righe di un gruppo ('hotel'|'animal') già in ordine riga, SOLO quelle
     * effettivamente offerte (richiesta della cliente, 29/09/2026: le voci non
     * disponibili appesantivano la scheda e davano una percezione negativa).
     *
     * Nessuna informazione voluta va persa: il pivot `included = false` non lo
     * sceglie nessuno — né il partner né l'admin hanno un campo per dichiarare
     * un servizio assente — lo genera {@see FamilyPublisher::syncAmenities()},
     * che scrive una riga per ogni amenity del catalogo.
     *
     * La chiave `included` resta nella shape, sempre true: i blade la leggono
     * ancora e il filtro si annulla togliendo una riga sola.
     *
     * @return array<int, array{label: string, included: bool}>
     */
    public function amenityRows(string $group): array
    {
        return $this->amenities
            ->where('group', $group)
            ->filter(fn (Amenity $amenity): bool => (bool) $amenity->pivot->included)
            ->map(fn (Amenity $amenity): array => [
                'label' => $amenity->name,
                'included' => true,
            ])
            ->values()
            ->all();
    }
}
