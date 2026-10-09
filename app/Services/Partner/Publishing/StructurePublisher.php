<?php

namespace App\Services\Partner\Publishing;

use App\Enums\ProductType;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\DB;

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
            // Senza una provincia valida resta quella che c'era, magari corretta dal pannello.
            'region_id' => $this->regionIdFor($draft) ?? $current?->region_id,
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

        $this->syncRooms($structure, $draft);

        return $structure;
    }

    /**
     * Una stanza per riga della bozza, agganciata a `draft_key`: ripubblicare
     * aggiorna la stanza esistente e ne conserva l'id, da cui dipende
     * l'occupazione registrata sugli ordini. Le righe tolte dalla bozza
     * spariscono; gli ordini storici tengono `room_name` (room_id va a null).
     */
    private function syncRooms(Structure $structure, StructureDraft $draft): void
    {
        $rows = $draft->normalizedRooms();

        // Foto che le stanze pubblicate mostrano ora: quelle che la nuova
        // versione non usa più si potano dopo il commit, come la galleria.
        $before = Room::query()
            ->where('structure_id', $structure->id)
            ->pluck('photos')
            ->flatten()
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->unique();
        $hadRooms = Room::query()->where('structure_id', $structure->id)->exists();

        foreach ($rows as $position => $row) {
            $room = Room::updateOrCreate(
                ['structure_id' => $structure->id, 'draft_key' => $row['key']],
                [
                    'type' => $row['type'],
                    'name' => $this->translationsFrom($row['name']),
                    'description' => $this->translationsFrom($row['description']),
                    'price_cents' => $this->cents($row['price']),
                    'max_guests' => $row['max_guests'],
                    'max_animals' => $row['max_animals'],
                    'units' => $row['units'],
                    'photos' => $row['photos'] ?: null,
                    'position' => $position,
                ],
            );

            $this->syncAmenities($room, $row['amenities']);
        }

        // whereNotNull: le stanze senza draft_key (create a mano) non si toccano.
        Room::query()
            ->where('structure_id', $structure->id)
            ->whereNotNull('draft_key')
            ->whereNotIn('draft_key', array_column($rows, 'key'))
            ->get()
            ->each(function (Room $room): void {
                $room->amenities()->detach();
                $room->delete();
            });

        // Prima pubblicazione con le stanze di una struttura che ha già
        // venduto: i suoi ordini hanno room_id null e l'occupazione non li
        // vedrebbe. Con una stanza sola sono suoi; con più stanze non si sa
        // a quale attribuirli e restano null (l'accorpamento lo gestisce da sé).
        if (! $hadRooms && count($rows) === 1) {
            OrderItem::query()
                ->where('purchasable_type', $structure->getMorphClass())
                ->where('purchasable_id', $structure->id)
                ->whereNull('room_id')
                ->update(['room_id' => Room::query()->where('structure_id', $structure->id)->where('draft_key', $rows[0]['key'])->value('id')]);
        }

        $kept = [...($draft->photos ?? []), ...array_merge(...array_column($rows, 'photos') ?: [[]])];

        foreach ($before->reject(fn (string $path) => in_array($path, $kept, true)) as $path) {
            DB::afterCommit(fn () => self::deletePhotoIfUnreferenced($path));
        }
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
