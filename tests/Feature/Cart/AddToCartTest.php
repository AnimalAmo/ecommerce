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
use App\Models\User;
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

    // ── Buco di copertura 2: la capienza dal metodo che il client chiama ─────

    /**
     * Prima era provato solo cosa DISEGNA la scheda (le CTA spariscono a posti
     * esauriti). Qui si passa da `addToCart()`, che una chiamata wire manomessa
     * raggiunge comunque: la regola vera è di AvailabilityService, l'unico posto
     * dove i posti si contano.
     *
     * Avvertenza motori: `lockForUpdate` di ReserveAvailabilityPipe è un no-op
     * su SQLite, quindi da questa suite si prova il RIFIUTO, non l'ordinamento
     * dei lock sotto concorrenza — quello si vede solo su MySQL.
     */
    public function test_un_evento_pieno_rifiuta_laggiunta_anche_dalla_chiamata_diretta(): void
    {
        $event = Event::factory()->create([
            'slug' => 'evento-pieno',
            'price_cents' => 2500,
            'max_participants' => 10,
            'booked_participants' => 10,
        ]);

        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('addToCart')
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));

        // Stessa regola dalla card della griglia, che è l'altro ingresso pubblico.
        Livewire::test(Events::class)
            ->call('addToCart', $event->id)
            ->assertDispatched('toast-show');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /**
     * Capienza 1 e due aggiunte. Il primo cliente entra in carrello (nulla è
     * ancora consumato: il posto si prende in ReserveAvailabilityPipe, alla
     * conferma dell'ordine); consumato quel posto, il secondo non entra più.
     */
    public function test_capienza_uno_il_secondo_cliente_non_entra_in_carrello(): void
    {
        $event = Event::factory()->create([
            'slug' => 'unico-posto',
            'price_cents' => 2500,
            'max_participants' => 1,
            'booked_participants' => 0,
        ]);

        // Primo cliente: il posto è libero, la riga entra.
        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('addToCart')
            ->assertNotDispatched('toast-show')
            ->assertSet('cartPopupOpen', true);

        $this->assertCount(1, session()->get(SessionCartStorage::SESSION_KEY, []));

        // Il suo ordine va a buon fine: il posto è consumato.
        $event->update(['booked_participants' => 1]);

        // Secondo cliente, sessione nuova e pulita.
        session()->forget(SessionCartStorage::SESSION_KEY);

        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('addToCart')
            ->assertDispatched('toast-show')
            ->assertSet('cartPopupOpen', false);

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    // ── Difetto C7: le cinque schede non hanno la guardia sul pagamento ──────

    /** Titolare che incassa direttamente: i suoi prodotti non sono acquistabili qui. */
    private function offlineOwner(): User
    {
        return User::factory()->offlinePartner()->create();
    }

    /**
     * La guardia `PartnerPaymentModeService` vive in due soli posti
     * (AddsEventToCart per le griglie, FavoriteService per i preferiti); nessuno
     * dei cinque `addToCart()` delle schede di dettaglio la interroga. La CTA
     * non c'è, ma il metodo arriva dal payload del client: la riga entra, e da
     * quel momento `guardSinglePartner` lega il carrello a quel partner e
     * rifiuta ogni prodotto acquistabile di un altro venditore.
     */
    public function test_la_scheda_di_un_evento_di_chi_incassa_in_struttura_non_riempie_il_carrello(): void
    {
        $event = Event::factory()->create([
            'slug' => 'evento-offline',
            'user_id' => $this->offlineOwner()->id,
            'price_cents' => 2500,
        ]);

        Livewire::test(EventDetail::class, ['event' => $event->slug])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', false)
            // Tester 28/09/2026: il rifiuto deve venire dalla guardia sul pagamento
            // diretto, non da un altro motivo (date, capienza) che lascerebbe il
            // carrello vuoto lo stesso e farebbe passare il test a vuoto.
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable')
                && ($params['dataset']['variant'] ?? null) === 'danger')
            ->assertNotDispatched('cart-updated');

        $this->assertSame(
            [],
            session()->get(SessionCartStorage::SESSION_KEY, []),
            'Un prodotto di chi incassa in struttura non è acquistabile: la guardia va anche nella scheda, '
            .'non solo nelle griglie e nei preferiti.',
        );
    }

    public function test_la_scheda_di_unattivita_di_chi_incassa_in_struttura_non_riempie_il_carrello(): void
    {
        $activity = Event::factory()->activity(3)->create([
            'slug' => 'attivita-offline',
            'user_id' => $this->offlineOwner()->id,
            'price_cents' => 11800,
        ]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity->slug])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', false)
            // Tester 28/09/2026: il rifiuto deve venire dalla guardia sul pagamento
            // diretto, non da un altro motivo (date, capienza) che lascerebbe il
            // carrello vuoto lo stesso e farebbe passare il test a vuoto.
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable')
                && ($params['dataset']['variant'] ?? null) === 'danger')
            ->assertNotDispatched('cart-updated');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_la_scheda_di_una_smartbox_di_chi_incassa_in_struttura_non_riempie_il_carrello(): void
    {
        $box = SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail();
        $box->forceFill(['user_id' => $this->offlineOwner()->id])->save();

        Livewire::test(SmartboxDetail::class, ['box' => $box->slug])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', false)
            // Tester 28/09/2026: il rifiuto deve venire dalla guardia sul pagamento
            // diretto, non da un altro motivo (date, capienza) che lascerebbe il
            // carrello vuoto lo stesso e farebbe passare il test a vuoto.
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable')
                && ($params['dataset']['variant'] ?? null) === 'danger')
            ->assertNotDispatched('cart-updated');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_la_scheda_di_una_struttura_di_chi_incassa_in_struttura_non_riempie_il_carrello(): void
    {
        $hotel = Structure::where('slug', 'hotel-brescia')->orderBy('position')->firstOrFail();
        $hotel->forceFill(['user_id' => $this->offlineOwner()->id])->save();

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-brescia'])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', false)
            // Tester 28/09/2026: il rifiuto deve venire dalla guardia sul pagamento
            // diretto, non da un altro motivo (date, capienza) che lascerebbe il
            // carrello vuoto lo stesso e farebbe passare il test a vuoto.
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable')
                && ($params['dataset']['variant'] ?? null) === 'danger')
            ->assertNotDispatched('cart-updated');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    public function test_la_scheda_di_un_servizio_di_chi_incassa_in_struttura_non_riempie_il_carrello(): void
    {
        $service = Structure::where('slug', 'dog-sitting')->firstOrFail();
        $service->forceFill(['user_id' => $this->offlineOwner()->id])->save();

        // Il default oggi+7 è chiuso da seed: si sceglie un giorno aperto.
        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting'])
            ->set('editCheckIn', CarbonImmutable::today()->addDays(8)->format('d/m/Y'))
            ->call('addToCart')
            ->assertSet('cartPopupOpen', false)
            // Tester 28/09/2026: il rifiuto deve venire dalla guardia sul pagamento
            // diretto, non da un altro motivo (date, capienza) che lascerebbe il
            // carrello vuoto lo stesso e farebbe passare il test a vuoto.
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable')
                && ($params['dataset']['variant'] ?? null) === 'danger')
            ->assertNotDispatched('cart-updated');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /**
     * Il danno vero di C7: la riga fantasma lega il carrello al partner
     * sbagliato, e ogni prodotto legittimo di un altro venditore viene poi
     * rifiutato con «un ordine, un venditore» — senza che il cliente possa
     * capire che deve cancellare una riga che non ha voluto.
     */
    public function test_la_riga_fantasma_non_deve_bloccare_il_carrello_su_quel_partner(): void
    {
        $offlineEvent = Event::factory()->create([
            'slug' => 'evento-offline-blocco',
            'user_id' => $this->offlineOwner()->id,
            'price_cents' => 2500,
        ]);

        Livewire::test(EventDetail::class, ['event' => $offlineEvent->slug])->call('addToCart');

        // Prodotto legittimo di un venditore online: deve poter entrare.
        $legit = Event::factory()->create([
            'slug' => 'evento-online-legittimo',
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'price_cents' => 3000,
        ]);

        Livewire::test(EventDetail::class, ['event' => $legit->slug])
            ->call('addToCart')
            ->assertSet('cartPopupOpen', true);

        $entries = collect(session()->get(SessionCartStorage::SESSION_KEY, []))->pluck('id');
        $this->assertTrue($entries->contains($legit->id), 'Il prodotto acquistabile deve entrare in carrello.');
        $this->assertFalse($entries->contains($offlineEvent->id));
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
