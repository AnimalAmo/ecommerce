<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfileOrders;
use App\Livewire\Profile\ProfileOrderSummary;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\User;
use App\Services\Orders\OrderQueryService;
use App\Support\Format;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Oggi fisso: mercoledì 15/07/2026 a mezzogiorno — le date relative a
        // now() restano coerenti tra arrange e assert anche a cavallo di mezzanotte.
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        // Reset dell'orologio finto prima dello smontaggio dell'app
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Ordine pagato con una riga structure e finestra prenotata esplicita. */
    private function orderWithWindow(User $user, Carbon $from, Carbon $until): Order
    {
        $order = Order::factory()->paid()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'booked_from' => $from,
            'booked_until' => $until,
        ]);

        return $order;
    }

    public function test_order_with_future_booked_until_is_in_programma_bucket(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->addDays(5), now()->addDays(10));

        Livewire::actingAs($user)
            ->test(ProfileOrders::class)
            ->assertSee($order->order_number)
            ->set('tab', 'passati')
            ->assertDontSee($order->order_number);
    }

    public function test_order_with_past_booked_until_is_in_passati_bucket(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->subDays(10), now()->subDays(5));

        Livewire::actingAs($user)
            ->test(ProfileOrders::class)
            ->assertDontSee($order->order_number)
            ->set('tab', 'passati')
            ->assertSee($order->order_number);
    }

    public function test_mixed_order_stays_in_programma_until_last_booked_until_passes(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'booked_from' => now()->subDays(9),
            'booked_until' => now()->subDays(5),
        ]);
        OrderItem::factory()->for($order)->create([
            'booked_from' => now()->addDays(5),
            'booked_until' => now()->addDays(9),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileOrders::class)
            ->assertSee($order->order_number)
            ->set('tab', 'passati')
            ->assertDontSee($order->order_number);
    }

    public function test_smartbox_order_buckets_by_validity_window(): void
    {
        $user = User::factory()->create();

        // Factory forSmartbox: finestra = oggi → oggi + 12 mesi (validità).
        $valid = Order::factory()->paid()->for($user)->create();
        OrderItem::factory()->forSmartbox()->for($valid)->create();

        $expired = Order::factory()->paid()->for($user)->create();
        OrderItem::factory()->forSmartbox()->for($expired)->create([
            'booked_from' => now()->subMonths(13),
            'booked_until' => now()->subMonth(),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileOrders::class)
            ->assertSee($valid->order_number)
            ->assertDontSee($expired->order_number)
            ->set('tab', 'passati')
            ->assertSee($expired->order_number)
            ->assertDontSee($valid->order_number);
    }

    public function test_arbitrary_tab_query_param_falls_back_to_programma(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->addDays(5), now()->addDays(10));

        // ?tab=xxx fuori whitelist: nessun bucket "fantasma", si torna a In programma.
        Livewire::actingAs($user)
            ->withQueryParams(['tab' => 'xxx'])
            ->test(ProfileOrders::class)
            ->assertSet('tab', 'programma')
            ->assertSee($order->order_number);
    }

    public function test_summary_of_another_users_order_is_404(): void
    {
        $owner = User::factory()->create();
        $order = $this->orderWithWindow($owner, now()->addDays(5), now()->addDays(10));

        $this->actingAs(User::factory()->create())
            ->get(route('profilo.ordini.riepilogo', $order->order_number))
            ->assertNotFound();
    }

    public function test_summary_renders_snapshot_even_after_purchasable_is_deleted(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();

        $from = now()->addDays(5);
        $until = now()->addDays(10);

        $item = OrderItem::factory()->for($order)->create([
            'title' => 'Hotel Fantasma',
            'price_cents' => 21500,
            'booked_from' => $from,
            'booked_until' => $until,
        ]);

        // Prodotto rimosso dal catalogo: la card vive di solo snapshot.
        $item->purchasable->delete();

        $this->actingAs($user)
            ->get(route('profilo.ordini.riepilogo', $order->order_number))
            ->assertOk()
            ->assertSee('Hotel Fantasma')
            ->assertSee(Format::money(21500))
            ->assertSee(Format::dateRange($from, $until));
    }

    public function test_review_flow_opens_the_form_and_ends_on_the_success_alert(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->subDays(10), now()->subDays(5));
        $item = $order->items->first();

        Livewire::actingAs($user)
            ->test(ProfileOrderSummary::class, ['order' => $order->order_number])
            ->call('openReview', $item->id)
            ->assertSet('reviewItemId', $item->id)
            ->set('reviewTitle', 'Super weekend rilassante!')
            ->call('confirmReview')
            // Il form si chiude e lascia il posto allo sweet alert verde sulla stessa riga.
            ->assertSet('reviewItemId', null)
            ->assertSet('reviewDoneItemId', $item->id)
            ->assertSee(__('profile.review_shared'))
            ->call('dismissReviewDone')
            ->assertSet('reviewDoneItemId', null)
            ->assertDontSee(__('profile.review_shared'))
            // Riga recensita: la pillola cambia etichetta e il form si riapre compilato.
            ->assertSee(__('profile.view_review'))
            ->call('openReview', $item->id)
            ->assertSet('reviewTitle', 'Super weekend rilassante!');
    }

    public function test_desktop_review_control_opens_the_modal(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->subDays(10), now()->subDays(5));

        Livewire::actingAs($user)
            ->test(ProfileOrderSummary::class, ['order' => $order->order_number])
            ->call('openReview', $order->items->first()->id)
            ->assertDispatched('modal-show');
    }

    public function test_mobile_review_control_does_not_open_the_desktop_modal(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->subDays(10), now()->subDays(5));

        // Il pop-up desktop vive in un wrapper max-lg:hidden: da mobile showModal() aprirebbe
        // un <dialog> invisibile che rende inerte tutta la pagina (indietro/condividi morti).
        Livewire::actingAs($user)
            ->test(ProfileOrderSummary::class, ['order' => $order->order_number])
            ->call('openReview', $order->items->first()->id, false)
            ->assertSet('reviewItemId', $order->items->first()->id)
            ->assertNotDispatched('modal-show');
    }

    public function test_upcoming_order_summary_still_offers_the_review_button(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->addDays(5), now()->addDays(10));

        // Il controllo recensione non dipende dal bucket: l'artboard app del riepilogo lo
        // tiene su ogni card ed è raggiunto proprio da "i miei ordini - in programma".
        Livewire::actingAs($user)
            ->test(ProfileOrderSummary::class, ['order' => $order->order_number])
            ->assertSee(__('profile.write_review'));
    }

    public function test_review_cannot_be_opened_on_an_item_of_another_order(): void
    {
        $user = User::factory()->create();
        $order = $this->orderWithWindow($user, now()->subDays(10), now()->subDays(5));
        $other = $this->orderWithWindow($user, now()->subDays(20), now()->subDays(15));

        Livewire::actingAs($user)
            ->test(ProfileOrderSummary::class, ['order' => $order->order_number])
            ->call('openReview', $other->items->first()->id)
            ->assertSet('reviewItemId', null);
    }

    public function test_gift_order_summary_shows_dedication_and_message(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->gift()->for($user)->create();

        OrderItem::factory()->gift()->for($order)->create([
            'options' => [
                'animals' => ['cane' => 1],
                'gift' => [
                    'dedication' => 'Per Anna',
                    'message' => 'Buon compleanno!',
                    'recipient_email' => 'anna@example.com',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('profilo.ordini.riepilogo', $order->order_number))
            ->assertOk()
            ->assertSee('Dedicato a: Per Anna')
            ->assertSee('Messaggio: Buon compleanno!')
            ->assertDontSee('anna@example.com');
    }

    public function test_demo_order_seeder_is_idempotent_and_matches_the_mock(): void
    {
        $this->seed(DatabaseSeeder::class);

        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();

        $orders = Order::where('user_id', $giulia->id)->count();
        $items = OrderItem::whereIn('order_id', Order::where('user_id', $giulia->id)->select('id'))->count();

        // Secondo run: cancella e ricrea i soli demo → stessi conteggi.
        $this->seed(DemoOrderSeeder::class);

        $this->assertSame($orders, Order::where('user_id', $giulia->id)->count());
        $this->assertSame($items, OrderItem::whereIn('order_id', Order::where('user_id', $giulia->id)->select('id'))->count());

        // Mock XD: ordine in programma con 3 articoli e totale 476,00 €.
        $upcoming = Order::where('user_id', $giulia->id)->where('total_cents', 47600)->firstOrFail();
        $this->assertSame(3, $upcoming->items()->count());
        $this->assertFalse(app(OrderQueryService::class)->isPast($upcoming->load('items')));

        // Ordine regalo con dedica per il riepilogo gift.
        $gift = Order::where('user_id', $giulia->id)->where('is_gift', true)->firstOrFail();
        $this->assertSame('Per Marco', $gift->items()->sole()->options['gift']['dedication']);

        // Almeno 2 ordini passati per il tab Passati e il bottone recensione.
        $this->assertGreaterThanOrEqual(2, count(app(OrderQueryService::class)->listFor($giulia, true)));
    }
}
