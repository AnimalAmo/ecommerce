<?php

namespace App\Services\Partner\Publishing;

use App\Enums\ProductType;
use App\Models\Region\Province;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;

/**
 * Famiglia struttura (hotel|bb|agriturismo) → tabella structures.
 */
class StructurePublisher extends FamilyPublisher
{
    public function publish(StructureDraft $draft): Structure
    {
        $current = Structure::query()->firstWhere('structure_draft_id', $draft->id);
        $priceCents = $this->minRoomPriceCents($draft);

        $structure = Structure::query()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'user_id' => $draft->user_id,
            // Sotto-tipologia hotel|bb|agriturismo per ora appiattita (v2: colonna sub_type).
            'type' => ProductType::Structure,
            'name' => $this->translations($draft, 'name'),
            'slug' => $this->slug($draft, 'struttura'),
            'location' => $draft->locationLabel(),
            // Regione derivata dalla provincia scelta nel wizard (provinces.region_id, mappa ISTAT).
            'region_id' => Province::query()->where('short_name', $draft->province)->value('region_id'),
            // "A partire da": la stanza più economica, per notte.
            'price_cents' => $priceCents,
            'price_from_cents' => $priceCents,
            // 0 esplicito come i seeder: il supplemento animali non ha input wizard (v2).
            'animal_supplement_cents' => 0,
            'img' => $this->coverPhoto($draft),
            'hero_img' => $this->coverPhoto($draft),
            // Nessuna fonte partner per la mappa (v2: embed dall'indirizzo); blank ⇒ sezione nascosta.
            'map_img' => '',
            'description' => $this->translations($draft, 'description'),
            'general_info' => $this->generalInfo($draft),
            // Nessuna fonte wizard per le card "Cosa troverai" (v2).
            'features' => null,
            'position' => $current->position ?? ((int) Structure::query()->max('position') + 1),
            'cancellation_policy_days' => $this->cancellationDays($draft),
        ]);

        $this->syncAmenities($structure, [
            ...($draft->services ?? []),
            ...($draft->additional_services ?? []),
            ...($draft->animal_services ?? []),
        ]);

        return $structure;
    }

    /** rooms[].price (stringhe numeric per notte) → min in cents; 0 senza stanze. */
    private function minRoomPriceCents(StructureDraft $draft): int
    {
        $prices = collect($draft->rooms ?? [])
            ->pluck('price')
            ->filter(fn ($price) => filled($price))
            ->map(fn ($price) => $this->cents((string) $price));

        return (int) ($prices->min() ?? 0);
    }

    /**
     * Sintesi [{icon,title,lines[]}] per il partial general-info: cancellazione,
     * pasti inclusi (dagli additional_services + meal_times) e check-in/out.
     * Stringhe it-only (limite noto, v2).
     */
    private function generalInfo(StructureDraft $draft): array
    {
        $rows = array_filter([$this->cancellationRow($draft)]);

        $meals = array_values(array_intersect($draft->additional_services ?? [], ['colazione', 'pranzo', 'cena']));
        $rows = [...$rows, ...$this->mealRows($meals, $draft->meal_times ?? [])];

        if (filled($draft->checkin_from) || filled($draft->checkout_from)) {
            $rows[] = [
                'icon' => 'home',
                'title' => 'Check-in e check-out',
                'lines' => array_values(array_filter([
                    filled($draft->checkin_from) ? 'Check-in: '.$draft->checkin_from.(filled($draft->checkin_to) ? '-'.$draft->checkin_to : '') : null,
                    filled($draft->checkout_from) ? 'Check-out: '.$draft->checkout_from.(filled($draft->checkout_to) ? '-'.$draft->checkout_to : '') : null,
                ])),
            ];
        }

        return array_values($rows);
    }
}
