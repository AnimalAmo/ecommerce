<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartValidationException;
use App\Livewire\Commerce\Cart;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Availability\AvailabilityService;
use App\Services\Cart\CartManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Stanza nel carrello: scelta obbligatoria se la struttura ha stanze, prezzo e capienza per stanza, occupazione solo per partner Online. */
class RoomCartTest extends TestCase
{
    use RefreshDatabase;

    private function bookingOptions(array $extra = []): array
    {
        return array_merge([
            'check_in' => now()->addWeek()->toDateString(),
            'check_out' => now()->addWeek()->addDays(2)->toDateString(),
            'guests' => ['adulti' => 2],
            'animals' => ['cane' => 1],
        ], $extra);
    }

    /** Struttura del partner; poi l'acquirente è un utente separato. */
    private function structureOf(User $partner, array $attributes = []): Structure
    {
        $structure = Structure::factory()->for($partner)->create(array_merge(['price_cents' => 10000, 'animal_supplement_cents' => 0], $attributes));
        $this->actingAs(User::factory()->create());

        return $structure;
    }

    private function add(Structure $structure, array $options): void
    {
        app(CartManager::class)->addItem('structure', $structure->id, $options, isGift: false);
    }

    private function fillRoom(Room $room, array $options): void
    {
        OrderItem::factory()->create([
            'order_id' => Order::factory()->paid()->create()->id,
            'room_id' => $room->id,
            'booked_from' => $options['check_in'],
            'booked_until' => $options['check_out'],
        ]);
    }

    public function test_structure_without_rooms_behaves_as_before(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());

        $item = app(CartManager::class)->addItem('structure', $structure->id, $this->bookingOptions(), isGift: false);

        $this->assertSame(20000, $item->priceCents);
    }

    public function test_room_id_required_when_structure_has_rooms(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        Room::factory()->for($structure)->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(CartValidationException::notPurchasable()->getMessage());

        $this->add($structure, $this->bookingOptions());
    }

    public function test_room_of_another_structure_is_rejected(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        Room::factory()->for($structure)->create();
        $foreign = Room::factory()->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(CartValidationException::notPurchasable()->getMessage());

        $this->add($structure, $this->bookingOptions(['room_id' => $foreign->id]));
    }

    public function test_nonexistent_room_is_rejected(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        Room::factory()->for($structure)->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage(CartValidationException::notPurchasable()->getMessage());

        $this->add($structure, $this->bookingOptions(['room_id' => 999999]));
    }

    public function test_price_uses_room_price(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner(), ['animal_supplement_cents' => 500]);
        $room = Room::factory()->for($structure)->create(['price_cents' => 15000]);

        // Id come stringa (Livewire): 2 notti x 150 + 1 animale x 5 x 2 notti.
        $item = app(CartManager::class)->addItem('structure', $structure->id, $this->bookingOptions(['room_id' => (string) $room->id]), isGift: false);

        $this->assertSame(30000 + 1000, $item->priceCents);
    }

    public function test_guests_over_room_capacity_rejected(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        $room = Room::factory()->for($structure)->create(['max_guests' => 2, 'max_animals' => 1]);

        try {
            $this->add($structure, $this->bookingOptions(['room_id' => $room->id, 'guests' => ['adulti' => 2, 'bambini' => 1]]));
            $this->fail('Ospiti oltre la capienza accettati.');
        } catch (CartValidationException $e) {
            $this->assertSame(CartValidationException::invalidParticipants()->getMessage(), $e->getMessage());
        }

        $this->expectException(CartValidationException::class);

        $this->add($structure, $this->bookingOptions(['room_id' => $room->id, 'animals' => ['cane' => 2]]));
    }

    public function test_online_partner_full_room_rejected(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        $room = Room::factory()->for($structure)->create();
        $options = $this->bookingOptions(['room_id' => $room->id]);
        $this->fillRoom($room, $options);

        try {
            $this->add($structure, $options);
            $this->fail('Stanza piena accettata.');
        } catch (CartValidationException $e) {
            $this->assertSame(CartValidationException::unavailableDates()->getMessage(), $e->getMessage());
        }
    }

    public function test_onsite_partner_full_room_still_accepted(): void
    {
        $structure = $this->structureOf($this->actingAsOfflinePartner());
        $room = Room::factory()->for($structure)->create(['price_cents' => 12000]);
        $options = $this->bookingOptions(['room_id' => $room->id]);
        $this->fillRoom($room, $options);

        $item = app(CartManager::class)->addItem('structure', $structure->id, $options, isGift: false);

        $this->assertSame(24000, $item->priceCents);
    }

    public function test_unavailable_dates_merges_closures_and_full_nights(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        $room = Room::factory()->for($structure)->create();
        $day = CarbonImmutable::today()->addMonth()->startOfMonth();
        $structure->closures()->create(['date' => $day->addDays(2)->toDateString()]);
        $this->fillRoom($room, ['check_in' => $day->addDays(5)->toDateString(), 'check_out' => $day->addDays(7)->toDateString()]);

        $service = app(AvailabilityService::class);

        $this->assertSame(
            [$day->addDays(2)->toDateString(), $day->addDays(5)->toDateString(), $day->addDays(6)->toDateString()],
            $service->unavailableDates($structure, $room, $day->year, $day->month),
        );
        // Senza stanza: solo le chiusure.
        $this->assertSame([$day->addDays(2)->toDateString()], $service->unavailableDates($structure, null, $day->year, $day->month));
    }

    public function test_unavailable_dates_for_onsite_partner_are_closures_only(): void
    {
        $structure = $this->structureOf($this->actingAsOfflinePartner());
        $room = Room::factory()->for($structure)->create();
        $day = CarbonImmutable::today()->addMonth()->startOfMonth();
        $this->fillRoom($room, ['check_in' => $day->toDateString(), 'check_out' => $day->addDays(2)->toDateString()]);

        $this->assertSame([], app(AvailabilityService::class)->unavailableDates($structure, $room, $day->year, $day->month));
    }

    public function test_cart_edit_popup_uses_the_line_room(): void
    {
        $structure = $this->structureOf($this->actingAsPayablePartner());
        $room = Room::factory()->for($structure)->create(['max_guests' => 2, 'max_animals' => 1]);
        $options = $this->bookingOptions(['room_id' => $room->id]);
        $this->add($structure, $options);
        // Un'altra prenotazione riempie la stanza tre notti dopo il check-out della riga.
        $full = CarbonImmutable::parse($options['check_out'])->addDays(3);
        $this->fillRoom($room, ['check_in' => $full->toDateString(), 'check_out' => $full->addDay()->toDateString()]);

        $key = app(CartManager::class)->items()->sole()->key;
        $component = Livewire::test(Cart::class)
            ->call('openEdit', $key)
            ->call('incrementGuest', 'adulti')
            ->call('incrementAnimal', 'cane')
            ->assertSet('editGuests.adulti', 2)
            ->assertSet('editAnimals.cane', 1);

        // Calendario sul mese della notte piena (openEdit lo apre su quello del check-in).
        $component->set('calendarMonth', $full->month)->set('calendarYear', $full->year);
        $cells = collect($component->call('toggleField', 'date')->viewData('calendar'))->flatten(1);

        $this->assertTrue($cells->firstWhere('date', $full->toDateString())['disabled']);
    }
}
