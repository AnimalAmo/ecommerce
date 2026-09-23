<?php

namespace Tests\Feature\Orders;

use App\Events\OnSiteOrderConfirmed;
use App\Mail\OrderConfirmationMail;
use App\Mail\SmartboxGiftMail;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayment\OrderPayment;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use RuntimeException;
use Tests\Feature\Orders\Concerns\PlacesOrders;
use Tests\TestCase;

/**
 * Prenotazione da pagare in struttura: senza OrderPayment non c'è OrderPaid,
 * quindi la conferma al cliente passa da OnSiteOrderConfirmed →
 * SendOnSiteOrderMails. Il render controlla la copia offline (importo da
 * pagare, partner, link al sito) e che la mail online resti quella di prima.
 */
class OnSiteOrderMailTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    /**
     * Venditore offline con ragione sociale e indirizzo, per la copia della mail.
     * Non si chiama $offlineSeller: il trait PlacesOrders (Task 7) dichiara già
     * `private ?User $offlineSeller = null` e PHP rifiuta la stessa proprietà
     * ridichiarata con tipo o default diversi (fatal error, la classe non si carica).
     */
    private User $fido;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        Mail::fake();

        $this->fido = User::factory()->offlinePartner()->create();
        $this->fido->partnerProfile->update([
            'business_name' => 'Agriturismo Fido',
            'address' => 'Via Roma 1',
            'zip' => '25121',
            'city' => 'Brescia',
            'province' => 'BS',
        ]);

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Evento → listener ────────────────────────────────────────────────────

    public function test_confirming_an_on_site_booking_sends_the_confirmation_to_the_buyer(): void
    {
        $order = $this->confirmOnSiteBooking();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('giulia.rossi@gmail.com')
            && $mail->order->is($order));
        Mail::assertNotSent(SmartboxGiftMail::class);
    }

    public function test_a_failed_confirmation_does_not_propagate_from_the_listener(): void
    {
        // SMTP giù sulla conferma: con la coda sync il listener gira dentro la
        // richiesta del checkout, e chi ha appena prenotato non deve vedere errori.
        Mail::swap(new class(Mail::getFacadeRoot()->manager) extends MailFake
        {
            public function send($view, array $data = [], $callback = null)
            {
                if ($view instanceof OrderConfirmationMail) {
                    throw new RuntimeException('SMTP connection refused');
                }

                parent::send($view, $data, $callback);
            }
        });

        OnSiteOrderConfirmed::dispatch($this->onSiteOrder());

        Mail::assertNotSent(OrderConfirmationMail::class);
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function test_on_site_confirmation_has_the_booking_subject(): void
    {
        $order = $this->onSiteOrder();

        (new OrderConfirmationMail($order))->assertHasSubject('Prenotazione confermata '.$order->order_number);
    }

    public function test_on_site_confirmation_renders_amount_due_partner_and_website_link(): void
    {
        $order = $this->onSiteOrder(['partner_payment_url' => 'https://agriturismo-fido.it/prenota']);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Prenotazione confermata!', $html);
        $this->assertStringContainsString('Ciao Giulia,', $html);
        $this->assertStringContainsString('la tua prenotazione '.$order->order_number.' è confermata', $html);
        $this->assertStringContainsString("Da pagare direttamente al partner Agriturismo Fido, in struttura o sul suo sito: 500\u{A0}€", $html);
        $this->assertStringContainsString('Indirizzo: Agriturismo Fido, Via Roma 1, 25121 Brescia (BS)', $html);
        $this->assertStringContainsString('href="https://agriturismo-fido.it/prenota"', $html);
        $this->assertStringContainsString('Paga sul sito del partner', $html);
        $this->assertStringNotContainsString('Grazie del tuo acquisto!', $html);
        $this->assertStringNotContainsString('Metodo di pagamento', $html);
    }

    public function test_on_site_confirmation_without_payment_url_has_no_website_button(): void
    {
        $order = $this->onSiteOrder(['partner_payment_url' => null]);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Da pagare direttamente al partner Agriturismo Fido', $html);
        $this->assertStringNotContainsString('Paga sul sito del partner', $html);
    }

    public function test_on_site_confirmation_never_prints_a_non_http_payment_url(): void
    {
        // Copia salvata sull'ordine scritta senza passare dal service (seeder,
        // tinker): SafeUrl::http la scarta e il bottone non si stampa.
        $order = $this->onSiteOrder(['partner_payment_url' => 'javascript:alert(1)']);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Da pagare direttamente al partner Agriturismo Fido', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('Paga sul sito del partner', $html);
    }

    public function test_on_site_confirmation_without_the_partner_profile_still_reads_well(): void
    {
        $order = $this->onSiteOrder();
        $this->fido->partnerProfile->delete();

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString("Da pagare direttamente al partner, in struttura o sul suo sito: 500\u{A0}€", $html);
        $this->assertStringNotContainsString('partner ,', $html);
        $this->assertStringNotContainsString('Indirizzo:', $html);
    }

    public function test_on_site_confirmation_without_a_partner_name_keeps_the_address_readable(): void
    {
        $this->fido->partnerProfile->update(['business_name' => null]);
        $this->fido->update(['first_name' => '', 'last_name' => '']);

        $html = (new OrderConfirmationMail($this->onSiteOrder()))->render();

        $this->assertStringContainsString("Da pagare direttamente al partner, in struttura o sul suo sito: 500\u{A0}€", $html);
        $this->assertStringContainsString('Indirizzo: Via Roma 1, 25121 Brescia (BS)', $html);
        $this->assertStringNotContainsString('Indirizzo: ,', $html);
    }

    public function test_online_confirmation_keeps_the_purchase_copy(): void
    {
        $order = Order::factory()->paid()->create(['first_name' => 'Giulia']);
        OrderItem::factory()->for($order)->create(['partner_user_id' => $this->fido->id]);
        OrderPayment::factory()->for($order)->completed()->create();

        $mail = new OrderConfirmationMail($order->fresh());
        $mail->assertHasSubject('Conferma ordine '.$order->order_number);

        $html = $mail->render();

        $this->assertStringContainsString('Grazie del tuo acquisto!', $html);
        $this->assertStringContainsString('il tuo ordine '.$order->order_number.' è confermato', $html);
        $this->assertStringContainsString('Carta di credito o di debito', $html);
        $this->assertStringNotContainsString('Da pagare direttamente', $html);
        $this->assertStringNotContainsString('Indirizzo:', $html);
        $this->assertStringNotContainsString('Paga sul sito del partner', $html);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * Prenotazione vera: carrello → PlaceOrderAction in modalità OnSite (5 notti = 500 €).
     * Riga e ordine passano dagli helper del trait PlacesOrders (addStructureLine e
     * placeOnSiteOrder, Task 7): acquirente Giulia Rossi, giulia.rossi@gmail.com.
     */
    private function confirmOnSiteBooking(): Order
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $this->fido->id,
            'price_cents' => 10000,
        ]));

        return $this->placeOnSiteOrder(partnerPaymentUrl: 'https://agriturismo-fido.it/prenota');
    }

    /** Ordine offline costruito a mano, solo per il render: una riga da 500 € del venditore offline. */
    private function onSiteOrder(array $attributes = []): Order
    {
        $order = Order::factory()->onSite()->create(array_merge([
            'first_name' => 'Giulia',
            'total_cents' => 50000,
            'partner_payment_url' => 'https://agriturismo-fido.it/prenota',
        ], $attributes));

        OrderItem::factory()->for($order)->create([
            'partner_user_id' => $this->fido->id,
            'title' => 'Agriturismo Fido camera doppia',
            'price_cents' => 50000,
        ]);

        return $order->fresh();
    }
}
