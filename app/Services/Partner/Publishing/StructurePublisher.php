<?php

namespace App\Services\Partner\Publishing;

use App\Enums\ProductType;
use App\Models\Region\Province;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\Log;

/**
 * Famiglia struttura (hotel|bb|agriturismo) → tabella structures.
 */
class StructurePublisher extends FamilyPublisher
{
    public function publish(StructureDraft $draft): Structure
    {
        $current = Structure::withHidden()->firstWhere('structure_draft_id', $draft->id);
        $priceCents = $this->minRoomPriceCents($draft);

        $structure = Structure::withHidden()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'user_id' => $draft->user_id,
            // Sotto-tipologia hotel|bb|agriturismo per ora appiattita (v2: colonna sub_type).
            'type' => ProductType::Structure,
            'name' => $this->translations($draft, 'name'),
            'slug' => $this->slug($draft, 'struttura'),
            'location' => $draft->locationLabel(),
            // Regione derivata dalla provincia scelta nel wizard (provinces.region_id, mappa ISTAT).
            'region_id' => $this->regionIdFor($draft),
            // "A partire da": la stanza più economica, per notte.
            'price_cents' => $priceCents,
            'price_from_cents' => $priceCents,
            // 0 esplicito come i seeder: il supplemento animali non ha input wizard (v2).
            'animal_supplement_cents' => 0,
            'img' => $this->coverPhoto($draft),
            'hero_img' => $this->coverPhoto($draft),
            'gallery' => $this->gallery($draft),
            // Nessuna fonte partner per la mappa (v2: embed dall'indirizzo); blank ⇒ sezione nascosta.
            'map_img' => '',
            'description' => $this->translations($draft, 'description'),
            'general_info' => $this->generalInfo($draft),
            // Nessuna fonte wizard per le card "Cosa troverai" (v2).
            'features' => null,
            'position' => $current->position ?? ((int) Structure::withHidden()->max('position') + 1),
            'cancellation_policy_days' => $this->cancellationDays($draft),
            ...$this->moderationAttributes($current),
        ]);

        $this->syncAmenities($structure, [
            ...($draft->services ?? []),
            ...($draft->additional_services ?? []),
            ...($draft->animal_services ?? []),
        ]);

        return $structure;
    }

    /**
     * Regione della scheda, dedotta dalla sigla di provincia della bozza
     * (`provinces.region_id`, mappa ISTAT).
     *
     * Il null non blocca la pubblicazione — la scheda resta valida e
     * prenotabile — ma la fa sparire da ogni elenco regionale, e finora
     * succedeva in silenzio: nessuno collegava «il mio hotel non si trova» a
     * una colonna vuota. Da qui il warning.
     *
     * Scatta in due casi diversi, sigla fuori elenco e provincia vuota (bozza
     * vecchia, step "Luogo" mai completato), quindi il messaggio non accusa
     * nessuno di aver sbagliato a scrivere: dice la conseguenza e lascia la
     * sigla — o la stringa vuota — nel contesto.
     */
    private function regionIdFor(StructureDraft $draft): ?int
    {
        $province = (string) ($draft->province ?? '');

        $regionId = $province === ''
            ? null
            : Province::query()->where('short_name', $province)->value('region_id');

        if ($regionId === null) {
            Log::warning('Regione non ricavata dalla provincia: la scheda non comparirà su nessuna pagina regione', [
                'structure_draft_id' => $draft->id,
                'partner_user_id' => $draft->user_id,
                'province' => $province,
            ]);

            return null;
        }

        return (int) $regionId;
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
