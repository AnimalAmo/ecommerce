<?php

namespace Tests\Feature\Cart;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Cart\SessionCartStorage;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Aggiunta al carrello dai widget delle pagine detail (guest = riga in
 * sessione, nessun gate di login): options canonicalizzate, prezzo quotato
 * server-side, violazioni disponibilità = toast danger senza riga.
 */
class AddToCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Orologio ancorato alle 08:00 di oggi: gli eventi "Oggi alle 13:30" del
        // seed restano futuri qualunque sia l'ora reale del run (setTestNow
        // ratificato per lo step 3; i default dei widget usano la stessa data).
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_adds_structure_with_default_dates_guests_and_animals(): void
    {
        $hotel = Structure::where('slug', 'hotel-brescia')->orderBy('position')->firstOrFail();
        $checkIn = CarbonImmutable::today()->addDays(7);
        $checkOut = CarbonImmutable::today()->addDays(12);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-brescia'])
            ->call('addToCart')
            ->assertOk()
            // Nessun gate di login (a differenza dei preferiti): il guest compra in sessione.
            ->assertNotDispatched('modal-show')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true)
            // Il pop-up mostra i dati REALI scelti nel widget, non il mock.
            ->assertSee('Aggiunto al carrello')
            ->assertSee($checkIn->format('d/m/Y').' - '.$checkOut->format('d/m/Y'))
            ->assertSee('2 adulti')
            ->assertSee('1 cane');

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        $this->assertSame('structure', $entry['type']);
        $this->assertSame($hotel->id, $entry['id']);
        $this->assertFalse($entry['is_gift']);
        // 5 notti × 43 € (supplemento animali seed = 0): quotazione server-side.
        $this->assertSame(21500, $entry['price_cents']);
        // Options canonicalizzate (ksort ricorsivo) nel vocabolario structure.
        $this->assertSame([
            'animals' => ['cane' => 1],
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
        ], $entry['options']);

        // Guest = solo sessione: nessuna riga carts/cart_items a db.
        $this->assertGuest();
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_adds_service_with_day_and_times(): void
    {
        $service = Structure::where('slug', 'dog-sitting')->firstOrFail();
        // Il default oggi+7 è chiuso da seed (demo del giorno non disponibile): si sceglie oggi+8.
        $day = CarbonImmutable::today()->addDays(8);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting'])
            ->set('editCheckIn', $day->format('d/m/Y'))
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        // I servizi sono righe Structure: stesso alias morph 'structure'.
        $this->assertSame('structure', $entry['type']);
        $this->assertSame($service->id, $entry['id']);
        $this->assertFalse($entry['is_gift']);
        // 6 ore (10:00 → 16:00) × 12 €/h: il totale onesto, non quello dell'XD.
        $this->assertSame(7200, $entry['price_cents']);
        // Vocabolario service: niente guests, il servizio è a ore.
        $this->assertSame([
            'animals' => ['cane' => 1],
            'day' => $day->toDateString(),
            'time_from' => '10:00',
            'time_to' => '16:00',
        ], $entry['options']);
    }

    public function test_service_closed_day_is_rejected(): void
    {
        // Il default del widget (oggi+7) coincide con la chiusura demo del dog sitting.
        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting'])
            ->call('addToCart')
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_structure_closed_dates_are_rejected(): void
    {
        // Hotel Brescia è chiuso oggi+3..+5: l'intervallo oggi+2 → oggi+4 copre la notte chiusa di oggi+3.
        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-brescia'])
            ->set('editCheckIn', CarbonImmutable::today()->addDays(2)->format('d/m/Y'))
            ->set('editCheckOut', CarbonImmutable::today()->addDays(4)->format('d/m/Y'))
            ->call('addToCart')
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_guest_adds_event_with_single_participant_and_real_price(): void
    {
        $event = Event::where('slug', 'brunch-pet-friendly')->firstOrFail();

        Livewire::test(EventDetail::class, ['event' => 'brunch-pet-friendly'])
            ->call('addToCart')
            ->assertNotDispatched('modal-show')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true)
            // Prezzo VERO del seed nel pop-up (mai il fallback 2500 hardcoded del mock — qui coincide col dato reale).
            ->assertSee("25\u{A0}€");

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        $this->assertSame('event', $entry['type']);
        $this->assertSame($event->id, $entry['id']);
        $this->assertFalse($entry['is_gift']);
        $this->assertSame(2500, $entry['price_cents']);
        // Pagina senza contatore: sempre 1 partecipante per aggiunta (decisione ratificata).
        $this->assertSame(['participants' => 1], $entry['options']);
    }

    public function test_grid_quick_add_creates_event_line_with_toast(): void
    {
        // Aggiunta rapida dalla card della griglia /eventi: nessun pop-up XD, conferma via toast.
        $event = Event::where('slug', 'brunch-pet-friendly')->firstOrFail();

        Livewire::test(Events::class)
            ->call('addToCart', $event->id)
            ->assertOk()
            ->assertDispatched('toast-show');

        $entry = array_values(session()->get(SessionCartStorage::SESSION_KEY, []))[0];
        $this->assertSame('event', $entry['type']);
        $this->assertSame($event->id, $entry['id']);
        $this->assertSame(2500, $entry['price_cents']);
        $this->assertSame(['participants' => 1], $entry['options']);
    }

    public function test_grid_quick_add_uses_widget_defaults_for_activities(): void
    {
        // Attività dalla griglia: stessi default del widget di dettaglio (2 adulti, 1 cane).
        $activity = Event::where('slug', 'weekend-escursioni')->firstOrFail();

        Livewire::test(Events::class)
            ->call('addToCart', $activity->id)
            ->assertDispatched('toast-show');

        $entry = array_values(session()->get(SessionCartStorage::SESSION_KEY, []))[0];
        $this->assertSame($activity->id, $entry['id']);
        // 118 € a persona × 2 adulti.
        $this->assertSame(23600, $entry['price_cents']);
        $this->assertSame(['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0], $entry['options']['guests']);
        $this->assertSame(['cane' => 1], $entry['options']['animals']);
    }

    public function test_grid_quick_add_is_a_noop_for_free_events(): void
    {
        $free = Event::where('slug', 'festa-pet-friendly')->firstOrFail();

        Livewire::test(Events::class)
            ->call('addToCart', $free->id)
            ->assertNotDispatched('toast-show');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_free_event_is_not_added_to_cart(): void
    {
        // Evento gratuito = CTA Partecipa: addToCart è un no-op silenzioso (né riga né toast né pop-up).
        Livewire::test(EventDetail::class, ['event' => 'festa-pet-friendly'])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_guest_adds_activity_with_guests_and_animals(): void
    {
        $activity = Event::where('slug', 'weekend-escursioni')->firstOrFail();

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-escursioni'])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        // Le attività sono righe Event: stesso alias morph 'event'.
        $this->assertSame('event', $entry['type']);
        $this->assertSame($activity->id, $entry['id']);
        $this->assertFalse($entry['is_gift']);
        // 118 € a persona × 2 adulti (gli animali non sono prezzati).
        $this->assertSame(23600, $entry['price_cents']);
        // Vocabolario activity: niente date nelle options, derivano dalla riga evento.
        $this->assertSame([
            'animals' => ['cane' => 1],
            'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
        ], $entry['options']);
    }

    public function test_activity_over_capacity_is_rejected(): void
    {
        // weekend-escursioni ha capienza 20: 21 persone superano il massimo
        // (validazione read-only contro max_participants, prop manomessa dal client).
        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-escursioni'])
            ->set('editGuests', ['adulti' => 21, 'ragazzi' => 0, 'bambini' => 0])
            ->call('addToCart')
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_guest_adds_smartbox_purchase(): void
    {
        $box = SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail();

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-lombardia'])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        $this->assertSame('smartbox_package', $entry['type']);
        $this->assertSame($box->id, $entry['id']);
        $this->assertFalse($entry['is_gift']);
        // Prezzo flat del cofanetto: gli animali non sono prezzati.
        $this->assertSame(21500, $entry['price_cents']);
        $this->assertSame(['animals' => ['cane' => 1]], $entry['options']);
    }

    public function test_guest_adds_smartbox_gift_with_gift_skeleton(): void
    {
        $box = SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail();

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-lombardia'])
            ->call('setGift', true)
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $cart = session()->get(SessionCartStorage::SESSION_KEY, []);
        $this->assertCount(1, $cart);

        $entry = array_values($cart)[0];
        $this->assertSame('smartbox_package', $entry['type']);
        $this->assertSame($box->id, $entry['id']);
        $this->assertTrue($entry['is_gift']);
        $this->assertSame(21500, $entry['price_cents']);
        // Scheletro gift senza default fake: dedica/messaggio dal carrello, email dal checkout.
        $this->assertSame([
            'animals' => ['cane' => 1],
            'gift' => ['dedication' => null, 'message' => null, 'recipient_email' => null],
        ], $entry['options']);
    }
}
