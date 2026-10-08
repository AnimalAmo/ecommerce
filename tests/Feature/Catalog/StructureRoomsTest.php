<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Region\Region;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\SessionCartStorage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Scheda struttura con stanze: sezione «Scegli la camera», booking card sul
 * prezzo e sulla capienza della stanza scelta, calendario con le notti piene
 * (solo partner Online) e room_id nelle options del carrello.
 */
class StructureRoomsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    private function structure(?User $owner = null, int $price = 9000, string $slug = 'hotel-camere'): Structure
    {
        return Structure::factory()->create([
            'slug' => $slug,
            'price_cents' => $price,
            'animal_supplement_cents' => 0,
            'user_id' => ($owner ?? User::factory()->stripeConnected()->create())->id,
        ]);
    }

    private function room(Structure $structure, int $position, array $attributes = []): Room
    {
        return Room::factory()->for($structure)->create([
            'position' => $position,
            'price_cents' => 10000,
            'max_guests' => 4,
            'max_animals' => 2,
            ...$attributes,
        ]);
    }

    private function page(string $slug = 'hotel-camere'): Testable
    {
        return Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => $slug]);
    }

    /** Ordine pagato che occupa la stanza nelle notti [from, to). */
    private function book(Room $room, CarbonImmutable $from, CarbonImmutable $to): void
    {
        OrderItem::factory()->create([
            'order_id' => Order::factory()->paid()->create()->id,
            'room_id' => $room->id,
            'booked_from' => $from,
            'booked_until' => $to,
        ]);
    }

    public function test_room_picker_shown_with_two_rooms_hidden_with_one(): void
    {
        $structure = $this->structure();
        $this->room($structure, 1, ['name' => ['it' => 'Camera Lago']]);

        $this->page()
            ->assertOk()
            ->assertDontSee(__('catalog.rooms.title'));

        $suite = $this->room($structure, 2, [
            'name' => ['it' => 'Suite Bosco'], 'max_guests' => 3, 'max_animals' => 1,
            'photos' => ['room-photos/a.jpg', 'room-photos/b.jpg'],
        ]);

        $this->page()
            // «Vedi tutte le foto» della stanza: un modale suo, non quello della scheda.
            ->assertSeeHtml('data-modal="room-gallery-'.$suite->id.'"')
            ->assertSeeHtml('room-photos/b.jpg')
            ->assertOk()
            ->assertSee(__('catalog.rooms.title'))
            ->assertSee('Camera Lago')
            ->assertSee('Suite Bosco')
            ->assertSee(__('catalog.rooms.max_guests', ['count' => 3]))
            ->assertSee(__('catalog.rooms.max_animals', ['count' => 1]))
            ->assertSee(__('catalog.rooms.selected'))
            ->assertSee(__('catalog.rooms.select'));
    }

    public function test_select_room_changes_price_and_options(): void
    {
        $structure = $this->structure();
        $first = $this->room($structure, 1, ['price_cents' => 10000, 'name' => ['it' => 'Camera Lago']]);
        $second = $this->room($structure, 2, ['price_cents' => 15000, 'name' => ['it' => 'Suite Bosco']]);

        // Default: prima per posizione; 5 notti al prezzo della stanza, non della struttura (90 €).
        $this->page()
            ->assertSet('roomId', $first->id)
            ->assertViewHas('nightsCents', 50000)
            ->assertViewHas('totalCents', 50000)
            ->assertSee("100\u{A0}€ a notte")
            ->call('selectRoom', $second->id)
            ->assertSet('roomId', $second->id)
            ->assertViewHas('nightsCents', 75000)
            ->assertViewHas('totalCents', 75000)
            ->assertSee("150\u{A0}€ a notte")
            ->assertSee(__('catalog.rooms.booking_room', ['name' => 'Suite Bosco']));
    }

    public function test_select_room_of_another_structure_is_ignored(): void
    {
        $structure = $this->structure();
        $first = $this->room($structure, 1);
        $this->room($structure, 2);
        $foreign = Room::factory()->create();

        $this->page()
            ->call('selectRoom', $foreign->id)
            ->assertSet('roomId', $first->id);
    }

    public function test_guest_stepper_capped_by_room_capacity(): void
    {
        $structure = $this->structure();
        $this->room($structure, 1, ['max_guests' => 4, 'max_animals' => 2]);
        $small = $this->room($structure, 2, ['max_guests' => 2, 'max_animals' => 1]);

        // Default 2 adulti + 1 animale: un ragazzo in più arriva a 3 (sotto i 4 della prima stanza).
        $component = $this->page()
            ->call('incrementGuest', 'ragazzi')
            ->assertSet('editGuests.ragazzi', 1);

        // La stanza da 2 riporta gli ospiti entro la capienza e blocca il "+".
        $component->call('selectRoom', $small->id)
            ->assertSet('editGuests', ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0])
            ->assertViewHas('guestsAtMax', true)
            ->assertViewHas('animalsAtMax', true)
            ->call('incrementGuest', 'bambini')
            ->assertSet('editGuests.bambini', 0)
            ->call('incrementAnimal', 'cane')
            ->assertSet('editAnimals', ['cane' => 1]);
    }

    public function test_foreign_room_id_in_url_falls_back_to_default(): void
    {
        $structure = $this->structure();
        $first = $this->room($structure, 1);
        $second = $this->room($structure, 2);
        $foreign = Room::factory()->create();

        Livewire::withQueryParams(['camera' => $foreign->id])
            ->test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-camere'])
            ->assertSet('roomId', $first->id);

        // Una stanza valida nell'URL invece si rispetta.
        Livewire::withQueryParams(['camera' => $second->id])
            ->test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-camere'])
            ->assertSet('roomId', $second->id);
    }

    public function test_default_room_skips_a_full_room_for_online_partner(): void
    {
        $structure = $this->structure();
        $first = $this->room($structure, 1);
        $second = $this->room($structure, 2);

        // Le date di default sono oggi+7 → oggi+12: la prima stanza è piena.
        $this->book($first, CarbonImmutable::today()->addDays(8), CarbonImmutable::today()->addDays(9));

        $this->page()->assertSet('roomId', $second->id);

        $checkIn = CarbonImmutable::today()->addDays(7);
        $this->assertSame($second->id, $structure->defaultRoomFor($checkIn, $checkIn->addDays(5))?->id);
        $this->assertSame($first->id, $structure->defaultRoomFor($checkIn->addDays(10), $checkIn->addDays(12))?->id);
    }

    public function test_default_room_ignores_occupancy_for_onsite_partner(): void
    {
        $structure = $this->structure(User::factory()->offlinePartner()->create());
        $first = $this->room($structure, 1);
        $this->room($structure, 2);
        $checkIn = CarbonImmutable::today()->addDays(7);
        $this->book($first, $checkIn, $checkIn->addDays(5));

        $this->assertSame($first->id, $structure->defaultRoomFor($checkIn, $checkIn->addDays(5))?->id);
        $this->assertNull(Structure::factory()->create()->defaultRoomFor($checkIn, $checkIn->addDay()));
    }

    public function test_calendar_disables_full_nights_for_online_partner_only(): void
    {
        $night = CarbonImmutable::today()->addDays(20);

        foreach ([true, false] as $online) {
            $slug = $online ? 'hotel-online' : 'hotel-in-struttura';
            $structure = $this->structure($online ? null : User::factory()->offlinePartner()->create(), slug: $slug);
            $room = $this->room($structure, 1);
            $other = $this->room($structure, 2);
            $this->book($room, $night, $night->addDay());

            $component = $this->page($slug)
                ->call('selectRoom', $room->id)
                ->call('toggleField', 'date')
                ->set('calendarMonth', (int) $night->format('n'))
                ->set('calendarYear', (int) $night->format('Y'));

            $this->assertSame($online, $this->isDisabled($component->viewData('calendar'), $night), $online ? 'Online' : 'OnSite');
            // La notte del check-out della prenotazione resta libera.
            $this->assertFalse($this->isDisabled($component->viewData('calendar'), $night->addDay()));

            if ($online) {
                // Il click sulla notte piena non fa nulla.
                $component->call('selectDay', $night->toDateString())
                    ->assertSet('editCheckIn', CarbonImmutable::today()->addDays(7)->format('d/m/Y'));

                // Cambiando stanza la notte torna selezionabile.
                $component->call('selectRoom', $other->id);
                $this->assertFalse($this->isDisabled($component->viewData('calendar'), $night));
            }
        }
    }

    public function test_structure_without_rooms_page_unchanged(): void
    {
        $this->structure(price: 9000);

        $this->page()
            ->assertOk()
            ->assertSet('roomId', null)
            ->assertDontSee(__('catalog.rooms.title'))
            ->assertSee("90\u{A0}€ a notte")
            ->assertViewHas('totalCents', 45000)
            ->call('addToCart')
            ->assertSet('cartPopupOpen', true);

        $line = array_values(session()->get(SessionCartStorage::SESSION_KEY, []))[0];
        $this->assertArrayNotHasKey('room_id', $line['options']);
    }

    public function test_add_to_cart_sends_selected_room(): void
    {
        $structure = $this->structure();
        $this->room($structure, 1);
        $second = $this->room($structure, 2, ['price_cents' => 15000]);

        $this->page()
            ->call('selectRoom', $second->id)
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $cart = array_values(session()->get(SessionCartStorage::SESSION_KEY, []));
        $this->assertCount(1, $cart);
        $this->assertSame($second->id, (int) $cart[0]['options']['room_id']);
    }

    public function test_cart_and_checkout_cards_show_the_room_name(): void
    {
        $structure = $this->structure();
        $this->room($structure, 1);
        $this->room($structure, 2, ['name' => ['it' => 'Suite Bosco']]);
        $second = $structure->rooms()->where('position', 2)->first();

        $this->page()->call('selectRoom', $second->id)->call('addToCart');

        $this->get(route('carrello'))
            ->assertOk()
            ->assertSee(__('orders.room', ['name' => 'Suite Bosco']));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee(__('orders.room', ['name' => 'Suite Bosco']));

        // Stanza cancellata dal partner: la card resta, senza la riga stanza (niente 500).
        $second->delete();

        $this->get(route('carrello'))
            ->assertOk()
            ->assertDontSee(__('orders.room', ['name' => 'Suite Bosco']));
    }

    /** true se la cella del giorno è disabilitata nella griglia. */
    private function isDisabled(array $calendar, CarbonImmutable $day): bool
    {
        foreach ($calendar as $week) {
            foreach ($week as $cell) {
                if ($cell['date'] === $day->toDateString()) {
                    return $cell['disabled'];
                }
            }
        }

        $this->fail('Giorno '.$day->toDateString().' non presente nella griglia.');
    }
}
