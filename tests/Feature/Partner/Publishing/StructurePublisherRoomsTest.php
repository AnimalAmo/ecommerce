<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use App\Services\Partner\Publishing\FamilyPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bozza → tabella rooms: una stanza per riga di `rooms`, agganciata a draft_key.
 */
class StructurePublisherRoomsTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_A = '11111111-1111-4111-8111-111111111111';

    private const KEY_B = '22222222-2222-4222-8222-222222222222';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    /** @return array<string, mixed> */
    private function roomRow(string $key, array $overrides = []): array
    {
        return array_merge([
            'key' => $key,
            'type' => 'doppia',
            'name' => ['it' => 'Camera Lago', 'en' => 'Lake room'],
            'description' => ['it' => 'Vista lago.', 'en' => 'Lake view.'],
            'price' => '80.50',
            'max_guests' => 3,
            'max_animals' => 2,
            'units' => 4,
            'photos' => [],
            'amenities' => ['wifi', 'tv'],
        ], $overrides);
    }

    private function hotelDraft(array $rooms, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort'],
            'description' => ['it' => 'Hotel pet friendly.'],
            'province' => 'BS',
            'city' => 'Brescia',
            'rooms' => $rooms,
            'photos' => ['structure-photos/cover.jpg'],
        ], $attributes));
    }

    public function test_publish_creates_one_room_per_draft_row(): void
    {
        $draft = $this->hotelDraft([
            $this->roomRow(self::KEY_A),
            $this->roomRow(self::KEY_B, ['type' => 'suite', 'name' => [], 'description' => [], 'price' => '120', 'units' => 1, 'photos' => ['structure-photos/suite.jpg'], 'amenities' => []]),
        ]);

        $structure = app(DraftPublisher::class)->publish($draft);

        $rooms = $structure->rooms;
        $this->assertCount(2, $rooms);

        $a = $rooms[0];
        $this->assertSame(self::KEY_A, $a->draft_key);
        $this->assertSame('doppia', $a->type);
        $this->assertSame('Camera Lago', $a->getTranslation('name', 'it'));
        $this->assertSame('Lake room', $a->getTranslation('name', 'en'));
        $this->assertSame(8050, $a->price_cents);
        $this->assertSame(3, $a->max_guests);
        $this->assertSame(2, $a->max_animals);
        $this->assertSame(4, $a->units);
        $this->assertSame(0, $a->position);
        $included = $a->amenities()->wherePivot('included', true)->pluck('name')->sort()->values()->all();
        $this->assertSame(['TV', 'Wifi'], $included);

        $b = $rooms[1];
        $this->assertSame(12000, $b->price_cents);
        $this->assertSame(1, $b->position);
        $this->assertSame(['structure-photos/suite.jpg'], $b->photos);
        $this->assertSame('', $b->getTranslation('name', 'it'));

        // "A partire da": la stanza più economica.
        $this->assertSame(8050, $structure->price_from_cents);
    }

    public function test_republish_keeps_room_ids_stable(): void
    {
        $draft = $this->hotelDraft([$this->roomRow(self::KEY_A), $this->roomRow(self::KEY_B)]);
        $structure = app(DraftPublisher::class)->publish($draft);
        $ids = $structure->rooms()->pluck('id', 'draft_key')->all();

        $draft->refresh();
        $rows = $draft->rooms;
        $rows[0]['price'] = '99';
        $rows[0]['units'] = 7;
        $draft->update(['rooms' => $rows]);

        app(DraftPublisher::class)->publish($draft->refresh());

        $this->assertSame($ids, $structure->rooms()->pluck('id', 'draft_key')->all());
        $this->assertSame(9900, Room::where('draft_key', self::KEY_A)->value('price_cents'));
        $this->assertSame(7, Room::where('draft_key', self::KEY_A)->value('units'));
    }

    public function test_room_removed_from_draft_is_deleted_and_order_snapshot_survives(): void
    {
        $draft = $this->hotelDraft([$this->roomRow(self::KEY_A), $this->roomRow(self::KEY_B)]);
        $structure = app(DraftPublisher::class)->publish($draft);
        $removed = Room::where('draft_key', self::KEY_B)->firstOrFail();

        $item = OrderItem::factory()->create([
            'purchasable_id' => $structure->id,
            'room_id' => $removed->id,
            'options' => ['room_name' => 'Camera Lago', 'check_in' => '2027-01-10', 'check_out' => '2027-01-12'],
        ]);

        $draft->update(['rooms' => [$this->roomRow(self::KEY_A)]]);
        app(DraftPublisher::class)->publish($draft->refresh());

        $this->assertDatabaseMissing('rooms', ['id' => $removed->id]);
        $this->assertSame(1, $structure->rooms()->count());

        $item->refresh();
        $this->assertNull($item->room_id);
        $this->assertSame('Camera Lago', $item->options['room_name']);
    }

    public function test_legacy_rows_are_normalized_with_persisted_keys(): void
    {
        $draft = $this->hotelDraft([
            ['type' => 'doppia', 'count' => 3, 'price' => '80'],
            ['type' => 'intera_struttura', 'count' => 1, 'beds' => 6, 'price' => '200'],
        ]);

        $first = $draft->normalizedRooms();

        $this->assertSame(3, $first[0]['units']);
        $this->assertSame(2, $first[0]['max_guests']);
        $this->assertSame(2, $first[0]['max_animals']);
        $this->assertSame([], $first[0]['name']);
        $this->assertSame(6, $first[1]['max_guests']);
        $this->assertNotSame($first[0]['key'], $first[1]['key']);

        // Chiavi salvate sulla bozza: stabili tra due chiamate e dopo un reload.
        $this->assertSame($first, $draft->normalizedRooms());
        $this->assertSame($first, StructureDraft::find($draft->id)->normalizedRooms());

        $structure = app(DraftPublisher::class)->publish($draft->refresh());
        $this->assertSame($first[0]['key'], $structure->rooms[0]->draft_key);
        $this->assertSame('', $structure->rooms[0]->getTranslation('name', 'it'));
    }

    public function test_room_photos_are_not_pruned_while_referenced(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/cover.jpg', 'c');
        Storage::disk('public')->put('structure-photos/room.jpg', 'r');

        $draft = $this->hotelDraft(
            [$this->roomRow(self::KEY_A)],
            ['photos' => ['structure-photos/cover.jpg', 'structure-photos/room.jpg']],
        );
        app(DraftPublisher::class)->publish($draft);

        // La foto esce dalla galleria ma resta nella stanza.
        $draft->refresh()->update([
            'photos' => ['structure-photos/cover.jpg'],
            'rooms' => [$this->roomRow(self::KEY_A, ['photos' => ['structure-photos/room.jpg']])],
        ]);
        // In transazione come DraftCompleter: la potatura parte al commit.
        DB::transaction(fn () => app(DraftPublisher::class)->publish($draft->refresh()));

        Storage::disk('public')->assertExists('structure-photos/room.jpg');
        $this->assertSame(['structure-photos/room.jpg'], Room::where('draft_key', self::KEY_A)->first()->photos);
    }

    /** Una foto tolta dalla stanza resta finché la stanza pubblicata la mostra, poi si pota. */
    public function test_room_photo_removed_from_draft_is_pruned_after_republish(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure-photos/old.jpg', 'o');
        Storage::disk('public')->put('structure-photos/kept.jpg', 'k');

        $draft = $this->hotelDraft([
            $this->roomRow(self::KEY_A, ['photos' => ['structure-photos/old.jpg', 'structure-photos/kept.jpg']]),
        ]);
        app(DraftPublisher::class)->publish($draft);

        $draft->refresh()->update(['rooms' => [$this->roomRow(self::KEY_A, ['photos' => ['structure-photos/kept.jpg']])]]);

        // La stanza pubblicata la punta ancora: la X non la cancella.
        $this->assertFalse(FamilyPublisher::deletePhotoIfUnreferenced('structure-photos/old.jpg'));

        DB::transaction(fn () => app(DraftPublisher::class)->publish($draft->refresh()));

        Storage::disk('public')->assertMissing('structure-photos/old.jpg');
        Storage::disk('public')->assertExists('structure-photos/kept.jpg');
    }

    public function test_merged_draft_is_not_listed_nor_published(): void
    {
        $target = $this->hotelDraft([$this->roomRow(self::KEY_A)]);
        $merged = $this->hotelDraft([$this->roomRow(self::KEY_B)], ['user_id' => $target->user_id]);
        $merged->forceFill(['merged_into_draft_id' => $target->id])->save();

        $this->assertNull(app(DraftPublisher::class)->publish($merged->refresh()));
        $this->assertNull(Structure::withHidden()->firstWhere('structure_draft_id', $merged->id));

        $listed = StructureDraft::listableFor($target->user_id)->pluck('id')->all();
        $this->assertContains($target->id, $listed);
        $this->assertNotContains($merged->id, $listed);
    }

    /**
     * Struttura pubblicata prima delle stanze: nessuna riga `rooms` e un
     * ordine con room_id null.
     *
     * @return array{0: StructureDraft, 1: OrderItem}
     */
    private function legacyWithBooking(array $rows): array
    {
        $draft = $this->hotelDraft($rows);
        $structure = app(DraftPublisher::class)->publish($draft);
        Room::query()->where('structure_id', $structure->id)->delete();

        $item = OrderItem::factory()->create(['purchasable_type' => 'structure', 'purchasable_id' => $structure->id, 'room_id' => null]);

        return [$draft->refresh(), $item];
    }

    public function test_first_publish_with_one_room_links_existing_bookings(): void
    {
        [$draft, $item] = $this->legacyWithBooking([$this->roomRow(self::KEY_A)]);

        $structure = app(DraftPublisher::class)->publish($draft);

        $this->assertSame($structure->rooms()->sole()->id, $item->fresh()->room_id);
    }

    public function test_first_publish_with_two_rooms_leaves_bookings_unlinked(): void
    {
        [$draft, $item] = $this->legacyWithBooking([$this->roomRow(self::KEY_A), $this->roomRow(self::KEY_B)]);

        app(DraftPublisher::class)->publish($draft);

        $this->assertNull($item->fresh()->room_id);
    }

    public function test_structure_that_already_had_rooms_is_never_backfilled(): void
    {
        $draft = $this->hotelDraft([$this->roomRow(self::KEY_A)]);
        $structure = app(DraftPublisher::class)->publish($draft);
        $item = OrderItem::factory()->create(['purchasable_type' => 'structure', 'purchasable_id' => $structure->id, 'room_id' => null]);

        app(DraftPublisher::class)->publish($draft->refresh());

        $this->assertNull($item->fresh()->room_id);
    }
}
