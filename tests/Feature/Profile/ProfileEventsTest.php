<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfileEvents;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\User;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Eventi a cui partecipo" legge le righe ordine di tipo evento (snapshot, come
 * "I miei ordini"): finché la lista era una const PHP questi test asserivano un
 * evento inventato, uguale per ogni utente registrato.
 */
class ProfileEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Oggi fisso come in ProfileOrdersTest: le date relative a now() restano
        // coerenti tra arrange e assert anche a cavallo di mezzanotte.
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Evento acquistato dall'utente, con finestra prenotata esplicita. */
    private function bookedEvent(User $user, Carbon $startsAt, array $attributes = []): OrderItem
    {
        $order = Order::factory()->paid()->for($user)->create();

        return OrderItem::factory()->forEvent()->for($order)->create([
            'booked_from' => $startsAt,
            'booked_until' => $startsAt->copy()->addHours(2),
            ...$attributes,
        ]);
    }

    public function test_upcoming_tab_lists_the_events_the_user_booked(): void
    {
        $user = User::factory()->create();
        $startsAt = now()->addDays(10)->setTime(18, 30);

        $event = $this->bookedEvent($user, $startsAt, [
            'title' => 'Passeggiata al parco con i cani',
            'location' => 'Torino, Italia',
            'price_cents' => 1500,
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertSet('tab', 'programma')
            ->assertSee($event->title)
            ->assertSee('Torino, Italia')
            // Stessa riga orario della griglia eventi, dallo snapshot booked_from.
            ->assertSee(Format::eventTime($startsAt))
            ->assertSee(Format::money(1500))
            ->set('tab', 'passati')
            ->assertDontSee($event->title);
    }

    public function test_past_tab_lists_only_the_events_already_over(): void
    {
        $user = User::factory()->create();

        $done = $this->bookedEvent($user, now()->subDays(10)->setTime(18, 30), ['title' => 'Evento concluso']);
        $next = $this->bookedEvent($user, now()->addDays(10)->setTime(18, 30), ['title' => 'Evento in arrivo']);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertSee($next->title)
            ->assertDontSee($done->title)
            ->set('tab', 'passati')
            ->assertSee($done->title)
            ->assertDontSee($next->title);
    }

    public function test_events_of_other_users_are_never_listed(): void
    {
        $other = User::factory()->create();
        $event = $this->bookedEvent($other, now()->addDays(10)->setTime(18, 30), ['title' => 'Evento di un altro']);

        Livewire::actingAs(User::factory()->create())
            ->test(ProfileEvents::class)
            ->assertDontSee($event->title)
            ->assertSee(__('profile.events_empty'));
    }

    public function test_gifted_events_stay_out_of_the_list(): void
    {
        $user = User::factory()->create();

        // Chi regala non partecipa: la riga resta in "I miei ordini"
        // (stessa regola di OrderQueryService::profileRouteFor()).
        $gift = $this->bookedEvent($user, now()->addDays(10)->setTime(18, 30), [
            'title' => 'Evento regalato a Marco',
            'is_gift' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertDontSee($gift->title);
    }

    public function test_non_event_purchases_stay_out_of_the_list(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();

        // Riga struttura: appartiene a "I miei ordini", non agli eventi.
        $stay = OrderItem::factory()->for($order)->create(['title' => 'Hotel Bau Bau']);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertDontSee($stay->title);
    }

    public function test_free_event_shows_the_free_label_instead_of_zero(): void
    {
        $user = User::factory()->create();
        $event = $this->bookedEvent($user, now()->addDays(3)->setTime(11, 0), [
            'title' => 'Aperitivo pet friendly',
            'price_cents' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertSee($event->title)
            ->assertSee(__('format.free'))
            ->assertDontSee(Format::money(0));
    }

    public function test_event_without_photo_window_or_location_still_renders_in_programma(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();

        // Snapshot minimo (foto, luogo e finestra sono nullable in order_items):
        // la card deve reggere, e senza booked_until la riga resta "in programma".
        $event = OrderItem::factory()->forEvent()->for($order)->create([
            'title' => 'Evento senza data',
            'photo_url' => null,
            'location' => null,
            'booked_from' => null,
            'booked_until' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ProfileEvents::class)
            ->assertSee($event->title)
            ->set('tab', 'passati')
            ->assertDontSee($event->title);
    }

    public function test_arbitrary_tab_falls_back_to_programma(): void
    {
        $user = User::factory()->create();
        $event = $this->bookedEvent($user, now()->addDays(10)->setTime(18, 30), ['title' => 'Evento in arrivo']);

        // Le tab sono bindate con wire:model: un valore fuori whitelist non deve
        // arrivare alla query come bucket fantasma, ma tornare a "In programma".
        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'xxx'])
            ->test(ProfileEvents::class)
            ->assertSet('tab', 'programma')
            ->assertSee($event->title);
    }
}
