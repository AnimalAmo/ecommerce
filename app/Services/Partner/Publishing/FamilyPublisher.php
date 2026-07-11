<?php

namespace App\Services\Partner\Publishing;

use App\Models\Amenity\Amenity;
use App\Models\Structure\StructureDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Base dei publisher di famiglia: ogni publish() fa updateOrCreate con chiave
 * `structure_draft_id` (unique sulle tabelle catalogo), così ri-pubblicare lo
 * stesso draft aggiorna la riga esistente invece di duplicarla. Qui vivono le
 * mappature comuni draft → catalogo (traduzioni, slug, foto, prezzi, amenities,
 * righe general_info) condivise dalle tre famiglie.
 */
abstract class FamilyPublisher
{
    /**
     * Slug del wizard → nome Amenity (AmenitySeeder). Gli slug senza equivalente
     * a catalogo (riscaldamento, tv, area_animali, ...) vengono ignorati; le
     * amenity del gruppo non selezionate finiscono nel pivot con included=false
     * (le righe ✗ del template).
     */
    protected const AMENITY_MAP = [
        'wifi' => 'Wifi',
        'aria_condizionata' => 'Aria condizionata negli spazi comuni',
        'sauna' => 'Spa', // approssimazione: il wizard non ha una voce "Spa" propria
        'spa' => 'Spa',
        'pranzo' => 'Pranzo',
        'pet_sitting' => 'Pet sitting',
        'veterinario' => 'Servizio veterinario',
        'omaggio' => 'Omaggio di benvenuto',
    ];

    abstract public function publish(StructureDraft $draft): Model;

    /**
     * Traduzioni compilate del campo draft, da assegnare alla colonna
     * translatable del catalogo. Mai vuoto: le colonne testo sono NOT NULL.
     */
    protected function translations(StructureDraft $draft, string $field): array
    {
        $values = array_filter($draft->getTranslations($field), fn ($value) => filled($value));

        return $values ?: ['it' => ''];
    }

    /** Slug unico e stabile: nome it + id draft (events/smartbox hanno unique index). */
    protected function slug(StructureDraft $draft, string $fallback): string
    {
        $base = Str::slug($draft->getTranslation('name', 'it')) ?: $fallback;

        return $base.'-'.$draft->id;
    }

    /**
     * Prima foto caricata nel wizard (path sul disco public: HasCatalogImages
     * la risolve in Storage URL). '' per soddisfare le colonne img NOT NULL:
     * blank ⇒ gli accessor tornano null e i blade nascondono/degradano.
     */
    protected function coverPhoto(StructureDraft $draft): string
    {
        return $draft->photos[0] ?? '';
    }

    /** cancellation_when del wizard ('30'|'15'|'7'|'1') → giorni interi. */
    protected function cancellationDays(StructureDraft $draft): ?int
    {
        return $draft->cancellation_when !== null ? (int) $draft->cancellation_when : null;
    }

    /**
     * Prezzo inserito dal partner → integer cents (il B2C fa aritmetica sui
     * cents, Format::money è type-hinted int). I valori già numeric (la regola
     * del wizard) passano diretti — il cleanup regex mangerebbe l'esponente di
     * '1e3'; per il resto normalizza la virgola italiana e ripulisce i valori
     * legacy tipo '215 €'.
     */
    protected function cents(?string $value): int
    {
        $value = trim((string) $value);

        if (! is_numeric($value)) {
            $value = str_replace(',', '.', preg_replace('/[^\d,.]/', '', $value));
        }

        return (int) round(((float) $value) * 100);
    }

    /** Riga general_info della cancellazione gratuita (icona calendar-return). */
    protected function cancellationRow(StructureDraft $draft): ?array
    {
        $days = $this->cancellationDays($draft);

        if ($days === null) {
            return null;
        }

        return [
            'icon' => 'calendar-return',
            'title' => 'Cancellazione gratuita',
            'lines' => [$days === 1 ? "Fino a 1 giorno prima dell'arrivo" : "Fino a {$days} giorni prima dell'arrivo"],
        ];
    }

    /**
     * Righe general_info dei pasti: colazione → icona coffee, pranzo/cena →
     * icona lunch, con gli orari di meal_times quando compilati.
     *
     * @param  list<string>  $meals  slug selezionati tra colazione|pranzo|cena
     * @param  array<string, array{from?: string, to?: string}>  $mealTimes
     */
    protected function mealRows(array $meals, array $mealTimes): array
    {
        $time = function (string $meal) use ($mealTimes): ?string {
            $slot = $mealTimes[$meal] ?? [];

            return filled($slot['from'] ?? null) && filled($slot['to'] ?? null)
                ? $slot['from'].'-'.$slot['to']
                : null;
        };

        $rows = [];

        if (in_array('colazione', $meals, true)) {
            $rows[] = [
                'icon' => 'coffee',
                'title' => 'Colazione inclusa',
                'lines' => array_values(array_filter([$time('colazione') ? 'Orario: '.$time('colazione') : null])),
            ];
        }

        $lunch = in_array('pranzo', $meals, true);
        $dinner = in_array('cena', $meals, true);

        if ($lunch || $dinner) {
            $rows[] = [
                'icon' => 'lunch',
                'title' => $lunch && $dinner ? 'Pranzo e cena inclusi' : ($lunch ? 'Pranzo incluso' : 'Cena inclusa'),
                'lines' => array_values(array_filter([
                    $lunch && $time('pranzo') ? ($dinner ? 'Orario pranzo: ' : 'Orario: ').$time('pranzo') : null,
                    $dinner && $time('cena') ? ($lunch ? 'Orario cena: ' : 'Orario: ').$time('cena') : null,
                ])),
            ];
        }

        return $rows;
    }

    /**
     * Sincronizza il pivot amenityables riproducendo la semantica del template:
     * per OGNI amenity dei due gruppi una riga, included=true se selezionata
     * nel wizard (✓ verde), false altrimenti (✗ rosa). Posizioni 1..n per
     * gruppo, come AmenitySeeder::pivot().
     *
     * @param  list<string>  $selectedSlugs  slug wizard selezionati (tutte le colonne json rilevanti)
     */
    protected function syncAmenities(Model $model, array $selectedSlugs): void
    {
        $names = array_values(array_intersect_key(self::AMENITY_MAP, array_flip($selectedSlugs)));

        $payload = [];

        foreach ([Amenity::GROUP_HOTEL, Amenity::GROUP_ANIMAL] as $group) {
            $position = 0;

            foreach (Amenity::query()->where('group', $group)->orderBy('id')->get() as $amenity) {
                $payload[$amenity->id] = [
                    'included' => in_array($amenity->name, $names, true),
                    'position' => ++$position,
                ];
            }
        }

        $model->amenities()->sync($payload);
    }
}
