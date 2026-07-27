<?php

namespace Tests\Feature\Partner;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayment\OrderPayment;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerBookingDetailTest extends TestCase
{
    use RefreshDatabase;

    /** Riga ordine su una struttura del partner, con pagamento carta completato. */
    private function structureBooking(User $partner): OrderItem
    {
        $structure = Structure::factory()->create(['user_id' => $partner->id]);

        $order = Order::factory()->paid()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia.rossi@gmail.com',
            'phone' => '3487384989',
        ]);
        OrderPayment::factory()->completed()->create(['order_id' => $order->id]);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'title' => 'Hotel Bau Resort',
            'price_cents' => 13500,
            'booked_from' => '2026-02-20',
            'booked_until' => '2026-02-25',
        ]);
    }

    public function test_guests_are_redirected_away(): void
    {
        $this->get(route('partner.bookings.show', 1))->assertRedirect();
    }

    public function test_structure_detail_shows_customer_and_booking_info(): void
    {
        $partner = $this->actingAsActivePartner();
        $item = $this->structureBooking($partner);

        $this->get(route('partner.bookings.show', $item->id))
            ->assertOk()
            ->assertSee(__('partner.bookings.detail_customer'))
            ->assertSee('Giulia')
            ->assertSee('+39 348 738 4989')
            ->assertSee($item->order->order_number)
            ->assertSee(__('payment.methods.card'))
            ->assertSee('Hotel Bau Resort')
            ->assertSee('20/02/2026 - 25/02/2026')
            ->assertSee("135\u{A0}€")
            // 5 notti tra check-in e check-out.
            ->assertSee(trans_choice('partner.bookings.duration_nights', 5, ['count' => 5]));
    }

    public function test_event_detail_shows_single_date_time_and_location(): void
    {
        $partner = $this->actingAsActivePartner();
        $event = Event::factory()->create(['user_id' => $partner->id]);

        $item = OrderItem::factory()->forEvent()->create([
            'purchasable_id' => $event->id,
            'title' => 'Sfilata a 4 zampe',
            'location' => 'Parco Sempione, Milano',
            'booked_from' => '2026-03-01 15:30',
            'booked_until' => '2026-03-01 17:30',
        ]);

        $this->get(route('partner.bookings.show', $item->id))
            ->assertOk()
            ->assertSee('01/03/2026')
            ->assertSee('15:30')
            ->assertSee('Parco Sempione, Milano')
            ->assertSee(trans_choice('partner.bookings.duration_hours', 2, ['count' => 2]));
    }

    public function test_smartbox_detail_shows_validity_instead_of_dates(): void
    {
        $partner = $this->actingAsActivePartner();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);

        $item = OrderItem::factory()->forSmartbox()->create([
            'purchasable_id' => $box->id,
            'title' => 'Cofanetto Zen',
            'booked_from' => '2026-02-20',
            'booked_until' => '2027-02-20',
        ]);

        $this->get(route('partner.bookings.show', $item->id))
            ->assertOk()
            ->assertSee(__('partner.bookings.detail_validity'))
            ->assertSee('20/02/2026 - 20/02/2027')
            ->assertDontSee(__('partner.bookings.detail_booking_date'));
    }

    public function test_activity_detail_shows_range_and_days(): void
    {
        $partner = $this->actingAsActivePartner();
        $activity = Event::factory()->activity()->create(['user_id' => $partner->id]);

        $item = OrderItem::factory()->forEvent()->create([
            'purchasable_id' => $activity->id,
            'title' => 'Trekking con Fido',
            'product_type' => ProductType::Activity,
            'booked_from' => '2026-04-01 09:00',
            'booked_until' => '2026-04-03 18:00',
        ]);

        $this->get(route('partner.bookings.show', $item->id))
            ->assertOk()
            ->assertSee('01/04/2026 - 03/04/2026')
            ->assertSee(trans_choice('partner.bookings.duration_days', 3, ['count' => 3]));
    }

    public function test_bookings_of_other_partners_products_are_forbidden(): void
    {
        $this->actingAsActivePartner();

        $other = User::factory()->create();
        $foreign = Structure::factory()->create(['user_id' => $other->id]);
        $item = OrderItem::factory()->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $foreign->id,
        ]);

        $this->get(route('partner.bookings.show', $item->id))->assertForbidden();
    }
}
