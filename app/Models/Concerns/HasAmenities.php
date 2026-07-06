<?php

namespace App\Models\Concerns;

use App\Models\Amenity\Amenity;
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
     * Righe {label, included} di un gruppo ('hotel'|'animal'), già in ordine riga —
     * la shape che i blade del template si aspettano.
     *
     * @return array<int, array{label: string, included: bool}>
     */
    public function amenityRows(string $group): array
    {
        return $this->amenities
            ->where('group', $group)
            ->map(fn (Amenity $amenity): array => [
                'label' => $amenity->name,
                'included' => (bool) $amenity->pivot->included,
            ])
            ->values()
            ->all();
    }
}
