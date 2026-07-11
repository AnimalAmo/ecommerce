<?php

namespace App\Services\Partner\Publishing;

use App\Enums\ProductType;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;

/**
 * Famiglia smartbox (soggiorno|benessere|avventura) → tabella smartbox_packages.
 * Le strutture di riscatto (smartbox_structures) NON vengono pubblicate: oggi
 * il wizard offre slug demo hardcoded (audit finding 5, v2: pivot reale).
 */
class SmartboxPublisher extends FamilyPublisher
{
    public function publish(StructureDraft $draft): SmartboxPackage
    {
        $current = SmartboxPackage::query()->firstWhere('structure_draft_id', $draft->id);
        $priceCents = $this->cents($draft->price);

        $package = SmartboxPackage::query()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'user_id' => $draft->user_id,
            'type' => match ($draft->type) {
                'benessere' => ProductType::Wellness,
                'avventura' => ProductType::Adventure,
                default => ProductType::Stay,
            },
            'title' => $this->translations($draft, 'name'),
            'slug' => $this->slug($draft, 'smartbox'),
            // Nessun input wizard per l'audience (v2); blank ⇒ riga nascosta nei blade.
            'audience' => '',
            'price_cents' => $priceCents,
            'price_from_cents' => $priceCents,
            // Regola piattaforma: ogni cofanetto vale 12 mesi.
            'validity_months' => 12,
            'img' => $this->coverPhoto($draft),
            'hero_img' => $this->coverPhoto($draft),
            'description' => $this->translations($draft, 'description'),
            'extended_description' => $this->translations($draft, 'detailed_description'),
            'general_info' => $this->generalInfo($draft),
            // Colonna NOT NULL senza fonte wizard: vuoto ⇒ sezione "Cosa troverai" nascosta.
            'features' => [],
            'position' => $current->position ?? ((int) SmartboxPackage::query()->max('position') + 1),
            'cancellation_policy_days' => $this->cancellationDays($draft),
        ]);

        $this->syncAmenities($package, [
            ...($draft->included_services ?? []),
            ...($draft->additional_services ?? []),
            ...($draft->meals ?? []),
            ...($draft->animal_services ?? []),
        ]);

        return $package;
    }

    /** Sintesi general_info: cancellazione, durata soggiorno, pasti (meals + meal_times). */
    private function generalInfo(StructureDraft $draft): array
    {
        $rows = array_filter([$this->cancellationRow($draft)]);

        if ($draft->duration_days) {
            $rows[] = [
                'icon' => 'home',
                'title' => 'Durata del soggiorno',
                'lines' => ['Soggiorno di '.$draft->duration_days.' '.($draft->duration_days === 1 ? 'giorno' : 'giorni')],
            ];
        }

        $meals = array_values(array_intersect($draft->meals ?? [], ['colazione', 'pranzo', 'cena']));

        return array_values([...$rows, ...$this->mealRows($meals, $draft->meal_times ?? [])]);
    }
}
