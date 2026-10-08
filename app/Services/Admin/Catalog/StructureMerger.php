<?php

namespace App\Services\Admin\Catalog;

use App\Enums\ProductType;
use App\Models\OrderItem\OrderItem;
use App\Models\Review\Review;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Services\Admin\Reviews\ReviewModeration;
use App\Services\Partner\Publishing\DraftPublisher;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Accorpa in una struttura sola le strutture che un partner ha creato «una per
 * camera» (caso reale: «Il Casale Sotto le Stelle - Monia / - Dona / - Stella»).
 *
 * La fonte di verità è la bozza del target: ogni sorgente diventa una riga in
 * `rooms`, poi il target si ripubblica dal publisher normale. Toccando solo la
 * tabella `rooms`, la prossima ripubblicazione del partner cancellerebbe le
 * stanze aggiunte (syncRooms toglie quelle che la bozza non ha).
 *
 * Le sorgenti non si cancellano: si sospendono come dal pannello e puntano al
 * target (`merged_into_*`), così le vecchie URL rispondono 301 e lo storico
 * ordini resta leggibile (`purchasable` non cambia).
 */
class StructureMerger
{
    /** Separatore fra nome della struttura e nome della camera: «Il Casale - Monia». */
    private const SEPARATOR = ' - ';

    public function __construct(
        private readonly DraftPublisher $publisher,
        private readonly CatalogAdmin $catalog,
        private readonly ReviewModeration $reviews,
    ) {}

    /**
     * Motivi per cui l'accorpamento non si può fare; vuoto se si può.
     *
     * @param  Collection<int, Structure>  $sources
     * @return list<string>
     */
    public function errors(Structure $target, Collection $sources): array
    {
        $errors = [];

        if ($sources->isEmpty()) {
            $errors[] = 'Nessuna struttura sorgente.';
        }

        if ($sources->contains(fn (Structure $source): bool => $source->is($target))) {
            $errors[] = "La struttura target #{$target->id} è anche fra le sorgenti.";
        }

        if ($sources->pluck('id')->duplicates()->isNotEmpty()) {
            $errors[] = 'Una struttura sorgente è ripetuta.';
        }

        foreach ([$target, ...$sources->all()] as $structure) {
            $label = "#{$structure->id} «{$structure->getTranslation('name', 'it')}»";

            if ($structure->draft === null) {
                $errors[] = "La struttura {$label} non ha una bozza.";
            } elseif ($structure->draft->family() !== 'struttura') {
                $errors[] = "La bozza della struttura {$label} non è della famiglia struttura.";
            } elseif ($structure->draft->merged_into_draft_id !== null) {
                $errors[] = "La bozza della struttura {$label} è già stata accorpata.";
            }

            if ($structure->type !== ProductType::Structure) {
                $errors[] = "La struttura {$label} non è una struttura ricettiva (tipo {$structure->type->value}).";
            }

            if ($structure->merged_into_structure_id !== null) {
                $errors[] = "La struttura {$label} è già stata accorpata nella #{$structure->merged_into_structure_id}.";
            }

            if ((int) $structure->user_id !== (int) $target->user_id) {
                $errors[] = "La struttura {$label} è di un altro partner (utente {$structure->user_id}, target utente {$target->user_id}).";
            }
        }

        // Una sorgente diventa una sola stanza: se ne ha già più d'una, o vi
        // sono state accorpate altre strutture, le sue stanze si perderebbero.
        foreach ($sources as $source) {
            $label = "#{$source->id} «{$source->getTranslation('name', 'it')}»";

            if ($source->rooms()->count() > 1) {
                $errors[] = "La struttura sorgente {$label} ha più stanze: accorparla le ridurrebbe a una.";
            }

            if (Structure::withHidden()->where('merged_into_structure_id', $source->id)->exists()) {
                $errors[] = "Nella struttura sorgente {$label} sono già state accorpate altre strutture: va usata come target.";
            }
        }

        return $errors;
    }

    /**
     * Anteprima senza scritture (per --dry-run): le righe stanza che si
     * aggiungerebbero, con quanto verrebbe spostato su ciascuna. Per la
     * stanza del target vedi targetRoomName() e targetBookings().
     *
     * @param  Collection<int, Structure>  $sources
     * @return list<array{source_id: int, row: array<string, mixed>, reviews: int, order_items: int}>
     */
    public function plan(Structure $target, Collection $sources): array
    {
        return $sources->values()->map(fn (Structure $source): array => [
            'source_id' => $source->id,
            'row' => $this->rowFor($source),
            'reviews' => Review::query()->whereMorphedTo('reviewable', $source)->count(),
            'order_items' => $this->orderItems($source)->count(),
        ])->all();
    }

    /**
     * Nome che riceverebbe l'unica stanza del target se è senza nome (le
     * righe legacy non ne hanno): «Monia» per «Il Casale - Monia». Senza
     * questo la stanza del target si chiamerebbe «Doppia» accanto a «Dona» e
     * «Stella». Null se non c'è niente da rinominare.
     *
     * @return array<string, string>|null
     */
    public function targetRoomName(Structure $target): ?array
    {
        // replicate(): normalizedRooms() su un model salvato scriverebbe le key (no in dry-run).
        $rows = $target->draft->replicate()->normalizedRooms();

        if (count($rows) !== 1 || filled($rows[0]['name']['it'] ?? null)) {
            return null;
        }

        $name = $this->roomName($target);

        return $name['it'] === $target->getTranslation('name', 'it') ? null : $name;
    }

    /**
     * Le prenotazioni del target stesso. Una struttura mai ripubblicata dopo
     * l'arrivo delle stanze non ha righe `rooms`, e i suoi ordini hanno
     * room_id null: dopo l'accorpamento la sua riga diventa una stanza (es.
     * «Monia») che l'occupazione vedrebbe vuota, e si potrebbe vendere due
     * volte. Se la bozza ha una riga sola, gli ordini sono suoi e si
     * collegano; con più righe non si sa a quale stanza attribuirli e restano
     * null (`ambiguous`: il comando lo segnala).
     *
     * @return array{order_items: int, ambiguous: int}
     */
    public function targetBookings(Structure $target): array
    {
        $unlinked = $this->orderItems($target)->whereNull('room_id')->count();

        if ($unlinked === 0 || Room::query()->where('structure_id', $target->id)->exists()) {
            return ['order_items' => 0, 'ambiguous' => 0];
        }

        $single = count($target->draft->replicate()->normalizedRooms()) === 1;

        return ['order_items' => $single ? $unlinked : 0, 'ambiguous' => $single ? 0 : $unlinked];
    }

    /**
     * @param  Collection<int, Structure>  $sources
     *
     * @throws InvalidArgumentException richiesta non valida (vedi errors()), niente scritto
     */
    public function merge(Structure $target, Collection $sources): Structure
    {
        if ($errors = $this->errors($target, $sources)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        return DB::transaction(function () use ($target, $sources): Structure {
            $draft = StructureDraft::query()->whereKey($target->structure_draft_id)->lockForUpdate()->firstOrFail();

            // Stessa regola del dry-run, letta prima che le key vengano scritte.
            $targetRoomName = $this->targetRoomName($target);
            $linkTargetBookings = $this->targetBookings($target)['order_items'] > 0;
            $rows = $draft->normalizedRooms();
            $targetKey = $rows[0]['key'] ?? null;

            if ($targetRoomName !== null) {
                $rows[0]['name'] = $targetRoomName;
            }

            $keys = [];

            foreach ($sources as $source) {
                $row = $this->rowFor($source);
                $keys[$source->id] = $row['key'];
                $rows[] = $row;
            }

            $draft->forceFill(['rooms' => $rows])->save();

            foreach ($sources as $source) {
                $this->moveReviews($source, $target);

                $this->catalog->suspend($source);
                $source->forceFill(['merged_into_structure_id' => $target->id])->save();
                $source->draft->forceFill(['merged_into_draft_id' => $draft->id])->save();
            }

            // Il publisher normale, come alla chiusura del wizard: struttura,
            // stanze per draft_key, amenity, prezzo «a partire da», foto.
            $published = $this->publisher->publish($draft);

            if (! $published instanceof Structure || ! $published->is($target)) {
                throw new RuntimeException("La bozza #{$draft->id} del target non si è ripubblicata.");
            }

            // Solo dopo il publish le stanze hanno un id: si ritrovano per draft_key.
            $roomIds = Room::query()
                ->where('structure_id', $target->id)
                ->whereIn('draft_key', $keys)
                ->pluck('id', 'draft_key');

            foreach ($sources as $source) {
                // Solo room_id: `purchasable` resta la sorgente, lo storico non cambia.
                $this->orderItems($source)->update(['room_id' => $roomIds[$keys[$source->id]]]);
            }

            // Gli ordini del target sulla stanza nata dalla sua riga (vedi targetBookings()).
            // Il publisher non lo fa: qui le stanze passano da 0 a più di una.
            if ($linkTargetBookings) {
                $this->orderItems($target)->whereNull('room_id')->update([
                    'room_id' => Room::query()->where('structure_id', $target->id)->where('draft_key', $targetKey)->value('id'),
                ]);
            }

            foreach ([$target, ...$sources->all()] as $structure) {
                $this->reviews->refreshRating($structure->getMorphClass(), $structure->id);
            }

            return $published->refresh();
        });
    }

    /**
     * Riga `rooms` della bozza target (formato normalizedRooms) per una sorgente.
     *
     * @return array<string, mixed>
     */
    private function rowFor(Structure $source): array
    {
        $rows = $source->draft->replicate()->normalizedRooms();

        $amenities = [
            ...array_intersect($source->draft->services ?? [], ServiceOptionLabels::slugs('services')),
            ...array_merge(...array_column($rows, 'amenities') ?: [[]]),
        ];

        return [
            'key' => (string) Str::uuid(),
            'type' => $rows[0]['type'] ?? 'doppia',
            'name' => $this->roomName($source),
            'description' => array_filter($source->getTranslations('description'), fn ($text): bool => filled($text)),
            'price' => number_format($source->price_from_cents / 100, 2, '.', ''),
            'max_guests' => max(array_column($rows, 'max_guests') ?: [2]),
            'max_animals' => max(array_column($rows, 'max_animals') ?: [2]),
            'units' => array_sum(array_column($rows, 'units')) ?: 1,
            'photos' => array_values(array_filter(
                $source->gallery ?? $source->draft->photos ?? [],
                fn ($path): bool => is_string($path) && str_contains($path, '/'),
            )),
            'amenities' => array_values(array_unique($amenities)),
        ];
    }

    /**
     * Per lingua, il testo dopo l'ultimo « - » del nome; il nome intero se
     * non c'è il separatore.
     *
     * @return array<string, string>
     */
    private function roomName(Structure $structure): array
    {
        return collect($structure->getTranslations('name'))
            ->filter(fn ($name): bool => filled($name))
            ->map(function (string $name): string {
                $position = mb_strrpos($name, self::SEPARATOR);
                $suffix = $position === false ? '' : trim(mb_substr($name, $position + mb_strlen(self::SEPARATOR)));

                return $suffix !== '' ? $suffix : trim($name);
            })
            ->all();
    }

    /** Recensioni in coda a quelle del target, nel loro ordine. */
    private function moveReviews(Structure $source, Structure $target): void
    {
        $offset = (int) Review::query()->whereMorphedTo('reviewable', $target)->max('position');

        Review::query()
            ->whereMorphedTo('reviewable', $source)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(fn (Review $review, int $index) => $review->forceFill([
                'reviewable_id' => $target->id,
                'position' => $offset + $index + 1,
            ])->save());
    }

    /** @return Builder<OrderItem> */
    private function orderItems(Structure $source): Builder
    {
        return OrderItem::query()
            ->where('purchasable_type', $source->getMorphClass())
            ->where('purchasable_id', $source->id);
    }
}
