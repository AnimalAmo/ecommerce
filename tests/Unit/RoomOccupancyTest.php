<?php

namespace Tests\Unit;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Services\Availability\RoomOccupancy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Occupazione per stanza: massimo di unità occupate in una notte, check-out escluso. */
class RoomOccupancyTest extends TestCase
{
    use RefreshDatabase;

    private RoomOccupancy $occupancy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->occupancy = new RoomOccupancy;
    }

    private function day(int $day): CarbonImmutable
    {
        return CarbonImmutable::create(2026, 12, $day);
    }

    private function book(Room $room, int $from, int $to, ?Order $order = null): OrderItem
    {
        return OrderItem::factory()->create([
            'order_id' => ($order ?? Order::factory()->paid()->create())->id,
            'room_id' => $room->id,
            'booked_from' => $this->day($from),
            'booked_until' => $this->day($to),
        ]);
    }

    public function test_free_room_is_available(): void
    {
        $room = Room::factory()->create();

        $this->assertSame(0, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12)));
        $this->assertTrue($this->occupancy->isAvailable($room, $this->day(10), $this->day(12)));
    }

    public function test_overlapping_paid_booking_fills_single_unit_room(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 9, 11);

        $this->assertSame(1, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12)));
        $this->assertFalse($this->occupancy->isAvailable($room, $this->day(10), $this->day(12)));
    }

    public function test_checkout_day_of_other_booking_is_free(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 10, 12);

        $this->assertTrue($this->occupancy->isAvailable($room, $this->day(12), $this->day(14)));
    }

    public function test_cancelled_and_pending_orders_do_not_count(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 10, 12, Order::factory()->create());
        $this->book($room, 10, 12, Order::factory()->create(['status' => 'cancelled']));

        $this->assertSame(0, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12)));
    }

    public function test_confirmed_onsite_order_counts(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 10, 12, Order::factory()->onSite()->create());

        $this->assertFalse($this->occupancy->isAvailable($room, $this->day(10), $this->day(12)));
    }

    public function test_two_units_allow_two_overlapping_bookings_not_three(): void
    {
        $room = Room::factory()->units(2)->create();
        $this->book($room, 10, 12);

        $this->assertTrue($this->occupancy->isAvailable($room, $this->day(10), $this->day(12)));

        $this->book($room, 10, 12);

        $this->assertSame(2, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12)));
        $this->assertFalse($this->occupancy->isAvailable($room, $this->day(10), $this->day(12)));
    }

    public function test_consecutive_bookings_occupy_one_unit(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 10, 12);
        $this->book($room, 12, 14);

        $this->assertSame(1, $this->occupancy->bookedUnits($room, $this->day(11), $this->day(13)));
    }

    public function test_except_order_id_ignores_that_order(): void
    {
        $room = Room::factory()->create();
        $item = $this->book($room, 10, 12);

        $this->assertSame(0, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12), $item->order_id));
    }

    public function test_other_rooms_do_not_count(): void
    {
        $room = Room::factory()->create();
        $this->book(Room::factory()->create(), 10, 12);

        $this->assertSame(0, $this->occupancy->bookedUnits($room, $this->day(10), $this->day(12)));
    }

    public function test_full_dates_lists_nights_only(): void
    {
        $room = Room::factory()->create();
        $this->book($room, 10, 12);

        $this->assertSame(['2026-12-10', '2026-12-11'], $this->occupancy->fullDates($room, 2026, 12));
    }
}
