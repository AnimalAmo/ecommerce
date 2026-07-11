<?php

namespace Tests\Feature\Partner;

use App\Enums\ProductType;
use App\Livewire\Partner\Bookings\PartnerBookings;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerBookingsTest extends TestCase
{
    use RefreshDatabase;

    /** Riga ordine su una struttura del partner (buyer Giulia Rossi). */
    private function structureBooking(User $partner, array $itemState = [], array $orderState = []): OrderItem
    {
        $structure = Structure::factory()->create(['user_id' => $partner->id]);

        $order = Order::factory()->paid()->create($orderState + [
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia.rossi@gmail.com',
        ]);

        return OrderItem::factory()->create($itemState + [
            'order_id' => $order->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'title' => 'Hotel Bau Resort',
            'price_cents' => 21500,
            'booked_from' => '2026-02-20',
            'booked_until' => '2026-02-25',
        ]);
    }

    public function test_guests_are_redirected_away(): void
    {
        $this->get(route('partner.bookings'))->assertRedirect();
    }

    public function test_the_structures_tab_lists_real_order_rows(): void
    {
        $partner = $this->actingAsActivePartner();
        $item = $this->structureBooking($partner);

        $this->get(route('partner.bookings'))
            ->assertOk()
            ->assertSee($item->order->order_number)
            ->assertSee('Giulia')
            ->assertSee('Rossi')
            ->assertSee('giulia.rossi@gmail.com')
            ->assertSee('Hotel Bau Resort')
            ->assertSee('20/02/2026 - 25/02/2026')
            ->assertSee("215\u{A0}€")
            ->assertSee(route('partner.bookings.show', $item->id));
    }

    public function test_bookings_of_other_partners_products_are_hidden(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->structureBooking($partner, ['title' => 'Hotel Mio']);

        $other = User::factory()->create();
        $foreign = Structure::factory()->create(['user_id' => $other->id]);
        OrderItem::factory()->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $foreign->id,
            'title' => 'Hotel Altrui',
        ]);

        Livewire::test(PartnerBookings::class)
            ->assertSee('Hotel Mio')
            ->assertDontSee('Hotel Altrui');
    }

    public function test_every_family_panel_shows_its_own_bookings(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->structureBooking($partner);

        $event = Event::factory()->create(['user_id' => $partner->id]);
        OrderItem::factory()->forEvent()->create([
            'purchasable_id' => $event->id,
            'title' => 'Sfilata a 4 zampe',
            'booked_from' => '2026-03-01 15:30',
            'booked_until' => '2026-03-01 17:30',
        ]);

        $activity = Event::factory()->activity()->create(['user_id' => $partner->id]);
        OrderItem::factory()->forEvent()->create([
            'purchasable_id' => $activity->id,
            'title' => 'Trekking con Fido',
            'product_type' => ProductType::Activity,
            'options' => ['guests' => ['adulti' => 2, 'bambini' => 1, 'ragazzi' => 0]],
            'booked_from' => '2026-04-01',
            'booked_until' => '2026-04-03',
        ]);

        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);
        OrderItem::factory()->forSmartbox()->create([
            'purchasable_id' => $box->id,
            'title' => 'Cofanetto Zen',
        ]);

        Livewire::test(PartnerBookings::class)
            ->assertSee('Hotel Bau Resort')
            ->assertSee('Sfilata a 4 zampe')
            ->assertSee('15:30')
            ->assertSee('Trekking con Fido')
            ->assertSee('Cofanetto Zen');
    }

    public function test_search_filters_the_rows(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->structureBooking($partner);
        $this->structureBooking($partner, ['title' => 'Agriturismo Le Querce'], [
            'first_name' => 'Marco',
            'last_name' => 'Bianchi',
            'email' => 'marco.bianchi@example.com',
        ]);

        Livewire::test(PartnerBookings::class)
            ->set('search', 'marco')
            ->assertSee('Agriturismo Le Querce')
            ->assertDontSee('Hotel Bau Resort')
            ->set('search', 'nessun match possibile')
            ->assertSee(__('partner.bookings.empty'));
    }

    public function test_the_date_filter_keeps_only_bookings_covering_that_day(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->structureBooking($partner);

        Livewire::test(PartnerBookings::class)
            ->set('date', '2026-02-22')
            ->assertSee('Hotel Bau Resort')
            ->set('date', '2027-01-01')
            ->assertDontSee('Hotel Bau Resort')
            ->assertSee(__('partner.bookings.empty'));
    }

    public function test_an_unknown_tab_falls_back_to_the_first_one(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerBookings::class)
            ->set('tab', 'hacker')
            ->assertSet('tab', 'strutture');
    }

    public function test_family_columns_follow_the_mockup(): void
    {
        $component = new PartnerBookings;

        // Eventi: Ora e niente Prezzo; smartbox: Validità e niente Data/N. Persone.
        $this->assertArrayHasKey('time', $component->columnsFor('eventi'));
        $this->assertArrayNotHasKey('price', $component->columnsFor('eventi'));
        $this->assertSame('col_validity', $component->columnsFor('smartbox')['date']);
        $this->assertArrayNotHasKey('people', $component->columnsFor('smartbox'));
        $this->assertSame('col_structure', $component->columnsFor('strutture')['title']);
        $this->assertSame('col_activity', $component->columnsFor('attivita')['title']);
    }
}
