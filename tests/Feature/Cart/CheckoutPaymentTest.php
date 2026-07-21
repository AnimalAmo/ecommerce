<?php

namespace Tests\Feature\Cart;

use App\Actions\Order\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Livewire\Commerce\Checkout;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\PaymentGateway\PaymentGateway;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Wiring pagamento del checkout (step 4): FakePaymentGateway nel container al
 * posto di Stripe (i test non toccano MAI la rete) — flusso completo
 * step 1→2→callback→ordine→step 3, guest, regalo, capture fallito, refund sul
 * sold-out post-capture, gateway disabilitato e degradazione senza chiavi.
 * Orologio fisso come PlaceOrderActionTest.
 */
class CheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        // Le mail post-incasso hanno il loro test (OrderPaidMailTest).
        Mail::fake();

        $this->seed(PaymentGatewaySeeder::class);
        app(PaymentGatewayService::class)->clearCache();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(StripeGateway::class, $this->gateway);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Flusso completo autenticato ──────────────────────────────────────────

    public function test_authenticated_checkout_places_the_order_and_reaches_step_three(): void
    {
        $buyer = $this->buyer();
        $this->actingAs($buyer);

        $structure = Structure::factory()->create(['price_cents' => 10000]);
        $this->addStructureLine($structure); // 5 notti = 500 €

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            // Sessione gateway aperta all'ingresso nello step 2.
            ->assertSet('clientSecret', 'cs_fake_secret')
            ->assertSet('paymentIntentId', 'pi_fake_1')
            ->assertSet('paymentUnavailable', false)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            ->assertSet('processing', false)
            ->assertSee('Grazie del tuo acquisto!')
            // Step 3 dallo snapshot: il carrello è già stato svuotato.
            ->assertSee($structure->name)
            // CTA "Vai ai tuoi acquisti": una struttura è un ordine, non una partecipazione.
            // (assert sulla variabile di vista: l'URL degli ordini è anche nel menu dell'header)
            ->assertViewHas('purchasesUrl', route('profilo.ordini'));

        // Capture verificato con l'importo server-side (mai dal client).
        $this->assertSame(
            [['payload' => ['payment_intent_id' => 'pi_fake_1'], 'expected_amount_cents' => 50000]],
            $this->gateway->captureCalls,
        );

        // Ordine con buyer/user/righe/totale giusti.
        $order = Order::sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertTrue($order->user->is($buyer));
        $this->assertSame('Giulia', $order->first_name);
        $this->assertSame('Rossi', $order->last_name);
        $this->assertSame('giulia@example.com', $order->email);
        $this->assertSame('340 5738920', $order->phone);
        $this->assertSame('Italia', $order->country);
        $this->assertSame(50000, $order->total_cents);
        $this->assertFalse($order->is_gift);

        $item = $order->items()->sole();
        $this->assertTrue($item->purchasable->is($structure));
        $this->assertSame(50000, $item->price_cents);

        $payment = $order->payment;
        $this->assertSame(PaymentStatus::Completed, $payment->status);
        $this->assertSame(PaymentMethod::Card, $payment->payment_method);
        $this->assertSame('pi_fake_1', $payment->transaction_id);
        $this->assertSame(50000, $payment->amount_cents);

        // Carrello del flusso svuotato dalla pipeline.
        $this->assertTrue($this->cart()->items()->isEmpty());
    }

    // ── CTA post-acquisto: sezione di profilo per tipo di acquisto ───────────

    public function test_an_event_only_order_sends_the_buyer_to_the_events_he_attends(): void
    {
        $this->actingAs($this->buyer());

        $event = Event::factory()->create([
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            // Solo eventi: la CTA porta a "Eventi a cui partecipo", non agli ordini.
            ->assertViewHas('purchasesUrl', route('profilo.eventi'))
            ->assertSee(route('profilo.eventi'));

        $this->assertSame(ProductType::Event, Order::sole()->items()->sole()->product_type);
    }

    public function test_a_mixed_order_stays_on_my_orders(): void
    {
        $this->actingAs($this->buyer());

        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));
        $event = Event::factory()->create([
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 1], false);

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            // Un ordine è atomico: misto = "I miei ordini", mai spezzato in due sezioni.
            ->assertViewHas('purchasesUrl', route('profilo.ordini'))
            ->assertDontSee(route('profilo.eventi'));
    }

    // ── Guest checkout ───────────────────────────────────────────────────────

    public function test_guest_checkout_creates_an_order_without_user(): void
    {
        $structure = Structure::factory()->create(['price_cents' => 10000]);
        $this->addStructureLine($structure); // carrello di sessione guest

        Livewire::test(Checkout::class)
            ->set('firstName', 'Mario')
            ->set('lastName', 'Verdi')
            ->set('email', 'mario@example.com')
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            // Ordine senza user_id + pagine profilo dietro auth: niente CTA acquisti,
            // resta la sola "Torna alla Home" (prima era un link a un vicolo cieco).
            ->assertViewHas('purchasesUrl', null)
            ->assertDontSee('Vai ai tuoi acquisti');

        $order = Order::sole();
        $this->assertNull($order->user_id);
        $this->assertSame('Mario', $order->first_name);
        $this->assertSame('Verdi', $order->last_name);
        $this->assertSame('mario@example.com', $order->email);
        // Cellulare non compilato: snapshot null, non stringa vuota.
        $this->assertNull($order->phone);
        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    // ── Flusso regalo ────────────────────────────────────────────────────────

    public function test_gift_checkout_orders_only_the_gift_lines_with_gift_snapshot(): void
    {
        $this->actingAs($this->buyer());

        $structure = Structure::factory()->create(['price_cents' => 10000]);
        $normalKey = $this->addStructureLine($structure);
        $box = SmartboxPackage::factory()->create(['price_cents' => 21500]);
        $this->addGiftSmartboxLine($box);

        Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->assertSet('gift', true)
            ->set('recipientEmail', 'marco@example.com')
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            // Email destinataria dallo snapshot (le righe regalo non sono più in carrello).
            ->assertSee('La Smartbox è stata mandata all’email: marco@example.com')
            ->assertSee('Dedicato a: Marco');

        // SOLO le righe regalo ordinate: totale = smartbox, riga con options.gift snapshottate.
        $order = Order::sole();
        $this->assertTrue($order->is_gift);
        $this->assertSame(21500, $order->total_cents);

        $item = $order->items()->sole();
        $this->assertTrue($item->is_gift);
        $this->assertSame([
            'dedication' => 'Marco',
            'message' => 'Tanti auguri!',
            'recipient_email' => 'marco@example.com',
        ], $item->options['gift']);

        // Rimosse SOLO le righe regalo: la riga normale resta in carrello.
        $this->assertTrue($this->cart()->items(true)->isEmpty());
        $remaining = $this->cart()->items(false);
        $this->assertCount(1, $remaining);
        $this->assertSame($normalKey, $remaining->first()->key);
    }

    // ── Capture fallito ──────────────────────────────────────────────────────

    public function test_failed_capture_keeps_the_user_at_step_two_without_an_order(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        $this->gateway->captureSucceeds = false;

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
        // Niente refund: nessun incasso da stornare.
        $this->assertSame([], $this->gateway->refundCalls);
        $this->assertCount(1, $this->cart()->items());
    }

    // ── Sold-out post-capture: storno immediato ──────────────────────────────

    public function test_post_capture_sold_out_refunds_the_charge_and_creates_no_order(): void
    {
        $this->actingAs($this->buyer());

        $event = Event::factory()->create([
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false); // 50 €

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2);

        // Acquirenti concorrenti DOPO il pre-check: restano 1 < 2 posti.
        $event->update(['booked_participants' => 9]);

        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        // Storno pieno col transaction id del capture (nessun OrderPayment esiste).
        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => 5000]],
            $this->gateway->refundCalls,
        );

        // Rollback totale: nessun ordine, capienza altrui intatta, carrello intatto.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
        $this->assertSame(9, $event->refresh()->booked_participants);
        $this->assertCount(1, $this->cart()->items());

        // Sessione nuova per riprovare (il PI stornato non è riusabile).
        $this->assertCount(2, $this->gateway->initCalls);
    }

    // ── Selezione wallet (Express Checkout Element) ──────────────────────────

    public function test_selecting_a_wallet_reuses_the_intent_and_orders_with_that_method(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            // Metodo rimosso/sconosciuto: selezione rifiutata, resta card.
            ->call('selectPayment', 'paypal')
            ->assertSet('paymentMethod', 'card')
            ->call('selectPayment', 'google_pay')
            ->assertSet('paymentMethod', 'google_pay')
            // La riga wallet monta l'Express Checkout Element.
            ->assertSeeHtml('stripeExpressCheckout')
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3);

        // Lo switch riusa il PI della sessione (update, mai un PI orfano).
        $this->assertCount(2, $this->gateway->initCalls);
        $this->assertSame('google_pay', $this->gateway->initCalls[1]['method']);
        $this->assertSame(['payment_intent_id' => 'pi_fake_1'], $this->gateway->initCalls[1]['context']);

        $order = Order::sole();
        $this->assertSame(PaymentMethod::GooglePay, $order->payment->payment_method);
    }

    // ── Init fallito: il callback passa comunque dalla verifica capture ──────

    public function test_callback_with_a_null_session_still_goes_through_capture(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        // Errore API transitorio all'init: step 2 raggiunto con sessione nulla.
        $this->gateway->initThrows = true;

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('paymentUnavailable', true)
            ->assertSet('paymentIntentId', null);

        // payloadMatchesSession con proprietà nulla lascia passare: la
        // riverifica server-side del capture resta il filtro (più il guard
        // idempotente a db su provider + gateway_session_id).
        $this->gateway->initThrows = false;
        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3);

        $this->assertSame(
            [['payload' => ['payment_intent_id' => 'pi_fake_1'], 'expected_amount_cents' => 50000]],
            $this->gateway->captureCalls,
        );
        $this->assertSame(1, Order::count());
    }

    // ── Idempotenza: lo stesso incasso non genera mai due ordini ─────────────

    public function test_replaying_the_callback_with_the_same_intent_is_idempotent(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3);

        $this->assertSame(1, Order::count());

        // Replay diretto dallo step 3: bloccato dal guard sullo step.
        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1']);
        $this->assertSame(1, Order::count());

        // Replay con step manomesso via devtools: il guard idempotente a db
        // (provider + gateway_session_id) intercetta — niente ordine, niente refund.
        $component->set('step', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertRedirect(route('profilo.ordini'));

        $this->assertSame(1, Order::count());
        $this->assertDatabaseCount('order_payments', 1);
        $this->assertSame([], $this->gateway->refundCalls);
    }

    public function test_callback_with_a_foreign_payment_intent_is_rejected_before_capture(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        // Payload devtools con un PI diverso dalla sessione corrente: nessuna capture.
        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('paymentIntentId', 'pi_fake_1')
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_evil_9'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        $this->assertSame([], $this->gateway->captureCalls);
        $this->assertDatabaseCount('orders', 0);
    }

    // ── Incassato ma non valido (importo cambiato): storno immediato ─────────

    public function test_amount_mismatch_capture_is_refunded_without_an_order(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        $this->gateway->captureAmountMismatch = true;

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        // Storno per l'importo REALMENTE incassato, non per il totale atteso.
        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => 12300]],
            $this->gateway->refundCalls,
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
        // Sessione nuova per riprovare (il PI stornato non è riusabile).
        $this->assertCount(2, $this->gateway->initCalls);
    }

    // ── Qualsiasi Throwable post-capture: storno immediato ───────────────────

    public function test_any_pipeline_error_after_capture_refunds_the_charge(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        $this->mock(PlaceOrderAction::class, function ($mock): void {
            $mock->shouldReceive('execute')->once()->andThrow(new RuntimeException('boom'));
        });

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        // Mai un incasso orfano: storno pieno col transaction id del capture.
        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => 50000]],
            $this->gateway->refundCalls,
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(1, $this->cart()->items());
    }

    // ── Prodotto cancellato post-capture: refund, non un 500 ─────────────────

    public function test_deleted_purchasable_after_capture_is_refunded_without_an_order(): void
    {
        $this->actingAs($this->buyer());
        $structure = Structure::factory()->create(['price_cents' => 10000]);
        $this->addStructureLine($structure);

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2);

        // Prodotto rimosso dal catalogo DOPO il pre-check dello step 2: il
        // carrello (che filtra i purchasable spariti) scende sotto l'importo
        // del PI già confermato → capture reale = incassato ma non valido.
        $structure->delete();
        $this->gateway->captureAmountMismatch = true;
        $this->gateway->mismatchCapturedAmountCents = 50000;

        $component->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2)
            ->assertSet('processing', false);

        $this->assertSame(
            [['transaction_id' => 'pi_fake_1', 'amount_cents' => 50000]],
            $this->gateway->refundCalls,
        );
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_payments', 0);
    }

    // ── Totale cambiato fra init e "Paga ora": re-init, niente conferma ──────

    public function test_cart_total_change_after_init_reinits_the_session_instead_of_paying(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        $component = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('sessionAmountCents', 50000);

        // Carrello cambiato in un'altra tab dopo l'init della sessione.
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 4000]));

        $component->call('processPayment')
            ->assertNotDispatched('process-payment')
            ->assertSet('processing', false)
            // Sessione ri-allineata al totale corrente (50000 + 5 notti × 40 €).
            ->assertSet('sessionAmountCents', 70000);

        $this->assertCount(2, $this->gateway->initCalls);
        $this->assertSame(70000, $this->gateway->initCalls[1]['amount_cents']);
        // PI nuovo, non update: il client_secret cambia e il wire:key rimonta
        // l'Element (un update lascerebbe "Paga ora" spento per sempre).
        $this->assertSame([], $this->gateway->initCalls[1]['context']);
        $this->assertDatabaseCount('orders', 0);
    }

    // ── Init JS fallito: box informativo, mai un "Paga ora" morto ────────────

    public function test_failed_js_init_marks_the_payment_unavailable(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('elementReady', false)
            // L'Element montato abilita "Paga ora"...
            ->call('markElementReady')
            ->assertSet('elementReady', true)
            // ...l'init fallito degrada al box cortese e sblocca processing.
            ->call('reportPaymentInitFailed')
            ->assertSet('paymentUnavailable', true)
            ->assertSet('elementReady', false)
            ->assertSet('processing', false);
    }

    // ── Gateway disabilitato ─────────────────────────────────────────────────

    public function test_methods_of_a_disabled_gateway_are_hidden_and_rejected(): void
    {
        PaymentGateway::query()->where('code', 'stripe')->update(['is_enabled' => false]);
        app(PaymentGatewayService::class)->clearCache();

        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            // Nessuna riga metodo: box informativo al posto dell'element.
            ->assertSet('paymentUnavailable', true)
            ->assertDontSee('Google Pay')
            ->assertSee(__('checkout.payment_unavailable'))
            // Tampering client-side: la capture è comunque rifiutata.
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 2);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], $this->gateway->captureCalls);
    }

    // ── Chiavi mancanti: degradazione senza crash ────────────────────────────

    public function test_missing_gateway_config_degrades_to_an_info_box(): void
    {
        // Risoluzione REALE del gateway (niente fake) con chiavi vuote.
        $this->app->forgetInstance(StripeGateway::class);
        config(['payment.stripe.secret' => '']);

        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            // Niente crash: step 2 con box informativo al posto dell'element.
            ->assertSet('step', 2)
            ->assertSet('paymentUnavailable', true)
            ->assertSet('clientSecret', null)
            ->assertSee(__('checkout.payment_unavailable'));

        $this->assertDatabaseCount('orders', 0);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    private function buyer(): User
    {
        return User::factory()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia@example.com',
            'phone' => '340 5738920',
        ]);
    }

    // ── Carta salvata a profilo ──────────────────────────────────────────────

    public function test_a_saved_card_opens_the_session_with_the_customer_and_the_payment_method(): void
    {
        $this->actingAs($this->buyerWithSavedCard());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            // Carta salvata preselezionata: niente Element da compilare.
            ->assertSet('useSavedCard', true)
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->assertSet('clientSecret', 'cs_fake_secret')
            ->assertSee('4242');

        $this->assertSame(
            ['customer_id' => 'cus_test', 'payment_method_id' => 'pm_test'],
            $this->gateway->initCalls[0]['context'],
        );
    }

    public function test_the_saved_card_pays_and_places_the_order(): void
    {
        $buyer = $this->buyerWithSavedCard();
        $this->actingAs($buyer);
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('markElementReady')
            ->call('processPayment')
            ->assertDispatched('process-payment', method: 'card')
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3);

        $order = Order::sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(PaymentMethod::Card, $order->payment->payment_method);
    }

    public function test_choosing_another_card_reopens_the_session_without_the_saved_one(): void
    {
        $this->actingAs($this->buyerWithSavedCard());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('selectSavedCard', false)
            ->assertSet('useSavedCard', false)
            ->assertSet('clientSecret', 'cs_fake_secret');

        // PI con payment method allegato non riusabile: sessione nuova, niente
        // payment_intent_id e nessun riferimento alla carta salvata.
        $this->assertSame([], $this->gateway->initCalls[1]['context']);
    }

    public function test_switching_to_a_wallet_drops_the_saved_card_session(): void
    {
        $this->actingAs($this->buyerWithSavedCard());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('selectPayment', PaymentMethod::GooglePay->value)
            ->assertSet('paymentMethod', 'google_pay');

        $this->assertSame([], $this->gateway->initCalls[1]['context']);
    }

    public function test_a_buyer_without_a_saved_card_keeps_the_element_flow(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->assertSet('useSavedCard', false)
            ->call('goToStep', 2)
            ->assertSet('step', 2);

        $this->assertSame([], $this->gateway->initCalls[0]['context']);
    }

    public function test_a_saved_card_broken_on_stripe_falls_back_to_a_new_card(): void
    {
        $this->actingAs($this->buyerWithSavedCard());
        $this->addStructureLine(Structure::factory()->create(['price_cents' => 10000]));

        // pm staccato dalla dashboard: il checkout non deve restare bloccato.
        $this->gateway->initThrowsWithSavedCard = true;

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('useSavedCard', false)
            ->assertSet('paymentUnavailable', false)
            ->assertSet('clientSecret', 'cs_fake_secret');
    }

    /** Acquirente con una carta già salvata a profilo (Stripe customer + payment method). */
    private function buyerWithSavedCard(): User
    {
        $buyer = $this->buyer();

        $buyer->forceFill([
            'stripe_customer_id' => 'cus_test',
            'stripe_payment_method_id' => 'pm_test',
            'card_brand' => 'visa',
            'card_last4' => '4242',
            'card_exp_month' => 12,
            'card_exp_year' => 2030,
            'card_holder' => 'Giulia Rossi',
        ])->save();

        return $buyer;
    }

    /** Riga struttura: 01/08 → 06/08 (5 notti), 2 adulti e 1 cane. */
    private function addStructureLine(Structure $structure): int|string
    {
        return $this->cart()->addItem('structure', $structure->id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga regalo: smartbox con metadati gift completi tranne l'email (arriva dallo step 1). */
    private function addGiftSmartboxLine(SmartboxPackage $box): int|string
    {
        return $this->cart()->addItem('smartbox_package', $box->id, [
            'animals' => ['cane' => 1],
            'gift' => [
                'dedication' => 'Marco',
                'message' => 'Tanti auguri!',
            ],
        ], true)->key;
    }
}
