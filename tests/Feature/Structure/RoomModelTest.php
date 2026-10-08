<?php

namespace Tests\Feature\Structure;

use App\Models\Amenity\Amenity;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_lists_rooms_by_position(): void
    {
        $structure = Structure::factory()->create();
        $second = Room::factory()->for($structure)->create(['position' => 2]);
        $first = Room::factory()->for($structure)->create(['position' => 1]);
        // Stanza di un'altra struttura: non deve comparire.
        Room::factory()->create();

        $this->assertSame([$first->id, $second->id], $structure->rooms->pluck('id')->all());
    }

    public function test_room_display_name_falls_back_to_type_label(): void
    {
        $structure = Structure::factory()->create();

        $named = Room::factory()->for($structure)->create(['name' => 'Camera Vista Lago', 'type' => 'doppia']);
        $unnamed = Room::factory()->for($structure)->create(['name' => '', 'type' => 'doppia']);

        $this->assertSame('Camera Vista Lago', $named->displayName());
        $this->assertSame(__('partner.hotel_rooms.type_double'), $unnamed->displayName());
    }

    public function test_room_amenities_sync(): void
    {
        $room = Room::factory()->create();
        $amenities = Amenity::factory()->count(2)->create();

        // `included` è NOT NULL senza default sul pivot: lo valorizza chi sincronizza.
        $room->amenities()->sync($amenities->mapWithKeys(
            fn (Amenity $amenity, int $i): array => [$amenity->id => ['included' => true, 'position' => $i]],
        )->all());

        $this->assertEqualsCanonicalizing(
            $amenities->pluck('id')->all(),
            $room->fresh()->amenities->pluck('id')->all(),
        );
    }

    public function test_deleting_room_nulls_order_item_room_id(): void
    {
        $room = Room::factory()->create();
        $item = OrderItem::factory()->create(['room_id' => $room->id]);

        $this->assertTrue($item->room->is($room));

        $room->delete();

        $this->assertNull($item->fresh()->room_id);
    }
}
