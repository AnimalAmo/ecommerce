<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Review\Review;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\Catalog\StructureMerger;
use App\Services\Availability\RoomOccupancy;
use App\Services\Partner\Publishing\DraftPublisher;
use App\Services\Partner\Publishing\FamilyPublisher;
use Carbon\CarbonImmutable;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `catalog:merge-structures`: le strutture create «una per camera» (caso reale
 * «Il Casale Sotto le Stelle - Monia / - Dona / - Stella») diventano stanze
 * di una struttura sola, e le vecchie URL rispondono 301.
 */
class MergeStructuresTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('public');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
        $this->partner = User::factory()->stripeConnected()->create();
    }

    /**
     * Struttura pubblicata dal publisher vero, con una riga stanza legacy
     * (senza key né nome, come le bozze di prima delle stanze).
     */
    private function published(string $name, string $price, array $photos, ?User $owner = null, array $row = []): Structure
    {
        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'jpg');
        }

        $draft = StructureDraft::create([
            'user_id' => ($owner ?? $this->partner)->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'agriturismo',
            'name' => ['it' => $name, 'en' => $name],
            'description' => ['it' => "Descrizione {$name}", 'en' => "Description {$name}"],
            'province' => 'BS',
            'city' => 'Brescia',
            'services' => ['wifi', 'piscina'],
            // Servizi animali della scheda: con l'accorpamento devono finire nella stanza.
            'animal_services' => ['pet_sitting'],
            'rooms' => [['type' => 'doppia', 'price' => $price, 'count' => 1, ...$row]],
            'photos' => $photos,
        ]);

        return app(DraftPublisher::class)->publish($draft);
    }

    /** @return array{0: Structure, 1: Structure, 2: Structure} */
    private function casale(): array
    {
        return [
            $this->published('Il Casale Sotto le Stelle - Monia', '90', ['structure-photos/monia.jpg']),
            $this->published('Il Casale Sotto le Stelle - Dona', '80', ['structure-photos/dona-1.jpg', 'structure-photos/dona-2.jpg'], row: ['count' => 2, 'max_guests' => 3, 'max_animals' => 1]),
            $this->published('Il Casale Sotto le Stelle - Stella', '120', ['structure-photos/stella.jpg']),
        ];
    }

    private function snapshot(): array
    {
        return collect(['structures', 'structure_drafts', 'rooms', 'reviews', 'order_items', 'amenityables'])
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all()])
            ->all();
    }

    private function bookOn(Structure $structure): OrderItem
    {
        return OrderItem::factory()->create([
            'order_id' => Order::factory()->paid()->create()->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
        ]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        [$monia, $dona, $stella] = $this->casale();
        Review::factory()->create(['reviewable_id' => $dona->id]);
        $this->bookOn($stella);

        // Bozze di produzione pubblicate prima delle stanze: righe senza key.
        // L'anteprima non deve scriverle (normalizedRooms() lo farebbe).
        foreach ([$monia, $dona, $stella] as $structure) {
            $draft = $structure->draft;
            $draft->forceFill(['rooms' => array_map(fn (array $row) => array_diff_key($row, ['key' => true]), $draft->rooms)])->saveQuietly();
        }

        $before = $this->snapshot();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id, $stella->id], '--dry-run' => true])
            ->expectsOutputToContain('Dona')
            ->expectsOutputToContain('Stella')
            ->assertSuccessful();

        $this->assertEquals($before, $this->snapshot());
    }

    public function test_merge_turns_sources_into_rooms(): void
    {
        [$monia, $dona, $stella] = $this->casale();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id, $stella->id]])
            ->assertSuccessful();

        $rooms = $monia->fresh()->rooms;
        $this->assertSame(['Monia', 'Dona', 'Stella'], $rooms->map(fn (Room $room) => $room->getTranslation('name', 'it'))->all());

        $doneRoom = $rooms[1];
        $this->assertSame(8000, $doneRoom->price_cents);
        $this->assertSame(2, $doneRoom->units);
        $this->assertSame(3, $doneRoom->max_guests);
        $this->assertSame(1, $doneRoom->max_animals);
        $this->assertSame(['structure-photos/dona-1.jpg', 'structure-photos/dona-2.jpg'], $doneRoom->photos);
        $this->assertSame('Descrizione Il Casale Sotto le Stelle - Dona', $doneRoom->getTranslation('description', 'it'));
        $this->assertSame(['Pet sitting', 'Piscina', 'Wifi'], $doneRoom->amenities()->wherePivot('included', true)->pluck('name')->sort()->values()->all());

        // La bozza è la fonte di verità: una ripubblicazione non toglie le stanze, né cambia i loro id.
        $draft = $monia->draft->fresh();
        $this->assertCount(3, $draft->rooms);
        $ids = $rooms->pluck('id')->all();
        app(DraftPublisher::class)->publish($draft);
        $this->assertSame($ids, $monia->fresh()->rooms->pluck('id')->all());

        // La foto della sorgente nascosta resta su disco: ora è della stanza.
        Storage::disk('public')->assertExists('structure-photos/dona-1.jpg');
        Storage::disk('public')->assertExists('structure-photos/stella.jpg');

        // Anche quando la sorgente non la punta più, la foto resta: è della stanza.
        Structure::withHidden()->whereKey($dona->id)->update(['gallery' => null, 'img' => '', 'hero_img' => '']);
        $dona->draft->forceFill(['photos' => []])->save();
        $this->assertFalse(FamilyPublisher::deletePhotoIfUnreferenced('structure-photos/dona-1.jpg'));
        Storage::disk('public')->assertExists('structure-photos/dona-1.jpg');
        $this->assertSame(8000, $monia->fresh()->price_from_cents);
    }

    public function test_merge_moves_reviews_and_order_items(): void
    {
        [$monia, $dona, $stella] = $this->casale();
        Review::factory()->create(['reviewable_id' => $monia->id, 'rating' => 5.0, 'position' => 1]);
        $donaReview = Review::factory()->create(['reviewable_id' => $dona->id, 'rating' => 3.0, 'position' => 1]);
        $donaItem = $this->bookOn($dona);
        $stellaItem = $this->bookOn($stella);

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id, $stella->id]])
            ->assertSuccessful();

        $donaReview->refresh();
        $this->assertSame($monia->id, (int) $donaReview->reviewable_id);
        $this->assertSame(2, (int) $donaReview->position);
        $this->assertSame(4.0, $monia->fresh()->rating);

        $rooms = $monia->fresh()->rooms->keyBy(fn (Room $room) => $room->getTranslation('name', 'it'));
        $this->assertSame($rooms['Dona']->id, $donaItem->fresh()->room_id);
        $this->assertSame($rooms['Stella']->id, $stellaItem->fresh()->room_id);

        // Lo storico ordini non cambia: la riga punta ancora alla sorgente.
        $this->assertSame($dona->id, (int) $donaItem->fresh()->purchasable_id);
    }

    public function test_merged_sources_hidden_and_drafts_not_listed(): void
    {
        [$monia, $dona, $stella] = $this->casale();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id, $stella->id]])
            ->assertSuccessful();

        $this->assertNotNull(Structure::query()->find($monia->id));

        foreach ([$dona, $stella] as $source) {
            $this->assertNull(Structure::query()->find($source->id));
            $hidden = Structure::withHidden()->find($source->id);
            $this->assertTrue($hidden->isSuspended());
            $this->assertSame($monia->id, (int) $hidden->merged_into_structure_id);
            $this->assertSame($monia->structure_draft_id, (int) $hidden->draft->merged_into_draft_id);
        }

        $this->assertSame([$monia->structure_draft_id], StructureDraft::query()->listableFor($this->partner->id)->pluck('id')->all());
    }

    public function test_old_url_redirects_301_to_target(): void
    {
        [$monia, $dona] = $this->casale();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id]])
            ->assertSuccessful();

        $this->get(route('holiday.structure', ['region' => 'lombardia', 'structure' => $dona->slug]))
            ->assertStatus(301)
            ->assertRedirect(route('holiday.structure', ['region' => 'lombardia', 'structure' => $monia->slug]));

        // Una struttura nascosta e non accorpata resta un 404.
        $stella = Structure::withHidden()->where('name->it', 'Il Casale Sotto le Stelle - Stella')->first();
        $stella->forceFill(['suspended_at' => now()])->save();
        $this->get(route('holiday.structure', ['region' => 'lombardia', 'structure' => $stella->slug]))->assertNotFound();
    }

    public function test_refuses_sources_of_another_partner(): void
    {
        [$monia, $dona] = $this->casale();
        $foreign = $this->published('Altro Agriturismo - Rosa', '70', [], User::factory()->stripeConnected()->create());

        $before = $this->snapshot();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id, $foreign->id]])
            ->expectsOutputToContain((string) $foreign->id)
            ->assertFailed();

        $this->assertEquals($before, $this->snapshot());
    }

    public function test_refuses_target_among_sources_missing_and_already_merged(): void
    {
        [$monia, $dona, $stella] = $this->casale();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$monia->id]])->assertFailed();
        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [999999]])->assertFailed();

        $service = $this->published('Dog sitter Luca', '30', []);
        Structure::withHidden()->whereKey($service->id)->update(['type' => 'service']);
        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$service->id]])->assertFailed();

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id]])->assertSuccessful();
        $before = $this->snapshot();
        $this->artisan('catalog:merge-structures', ['target' => $stella->id, 'sources' => [$dona->id]])->assertFailed();
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_refuses_a_source_with_several_rooms_or_that_absorbed_others(): void
    {
        [$monia, $dona, $stella] = $this->casale();
        $merger = app(StructureMerger::class);

        // Sorgente con più righe stanza: accorparla le schiaccerebbe in una.
        Room::factory()->for($dona)->create();
        $this->assertGreaterThan(1, $dona->rooms()->count());
        $errors = $merger->errors($monia, collect([$dona]));
        $this->assertNotEmpty(array_filter($errors, fn (string $error) => str_contains($error, "#{$dona->id}")));

        // Sorgente in cui sono già state accorpate altre strutture.
        $absorbed = $this->published('Il Casale Sotto le Stelle - Luna', '70', []);
        Structure::withHidden()->whereKey($absorbed->id)->update(['merged_into_structure_id' => $stella->id]);
        $this->assertSame(1, $stella->rooms()->count());
        $errors = $merger->errors($monia, collect([$stella->fresh()]));
        $this->assertNotEmpty(array_filter($errors, fn (string $error) => str_contains($error, "#{$stella->id}")));

        $before = $this->snapshot();
        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$stella->id]])->assertFailed();
        $this->assertEquals($before, $this->snapshot());
    }

    /** Il target com'è in produzione: mai ripubblicato dopo le stanze, nessuna riga `rooms`. */
    private function asLegacy(Structure $structure, ?array $rows = null): void
    {
        Room::query()->where('structure_id', $structure->id)->delete();
        $draft = $structure->draft;
        $draft->forceFill(['rooms' => $rows ?? array_map(fn (array $row) => array_diff_key($row, ['key' => true]), $draft->rooms)])->saveQuietly();
    }

    public function test_target_own_bookings_are_linked_to_its_room(): void
    {
        [$monia, $dona] = $this->casale();
        $this->asLegacy($monia);
        $from = CarbonImmutable::today()->addDays(20);
        $item = OrderItem::factory()->create([
            'order_id' => Order::factory()->paid()->create()->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $monia->id,
            'room_id' => null,
            'booked_from' => $from,
            'booked_until' => $from->addDays(3),
        ]);

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id], '--dry-run' => true])
            ->expectsTable(
                ['Sorgente', 'Stanza', 'Tipo', 'Prezzo/notte', 'Unità', 'Ospiti', 'Animali', 'Foto', 'Recensioni', 'Righe ordine'],
                [
                    ['#'.$monia->id.' (target)', 'it: Monia', '', '', '', '', '', '', '', 1],
                    ['#'.$dona->id, 'it: Dona / en: Dona', 'doppia', '80.00', 2, 3, 1, 2, 0, 0],
                ],
            )
            ->assertSuccessful();
        $this->assertNull($item->fresh()->room_id);

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id]])->assertSuccessful();

        $room = $monia->fresh()->rooms->first(fn (Room $room) => $room->getTranslation('name', 'it') === 'Monia');
        $this->assertSame($room->id, $item->fresh()->room_id);
        // Una camera sola (units 1) già venduta in quelle notti: piena.
        $this->assertFalse(app(RoomOccupancy::class)->isAvailable($room, $from, $from->addDays(3)));
    }

    public function test_target_bookings_stay_unlinked_and_warned_when_target_has_several_rows(): void
    {
        [$monia, $dona] = $this->casale();
        $this->asLegacy($monia, [['type' => 'doppia', 'price' => '90'], ['type' => 'suite', 'price' => '150']]);
        $item = $this->bookOn($monia);

        $this->artisan('catalog:merge-structures', ['target' => $monia->id, 'sources' => [$dona->id]])
            ->expectsOutputToContain('restano senza stanza')
            ->assertSuccessful();

        $this->assertNull($item->fresh()->room_id);
    }
}
