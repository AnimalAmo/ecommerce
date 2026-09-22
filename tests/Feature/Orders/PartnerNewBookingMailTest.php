<?php

namespace Tests\Feature\Orders;

use App\Events\OnSiteOrderConfirmed;
use App\Mail\OrderConfirmationMail;
use App\Mail\PartnerNewBookingMail;
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
 * Il partner non riceveva mai nulla: niente mail Stripe per l'offline, e per
 * l'online solo l'addebito sul suo account. Una mail per partner distinto
 * delle righe, dal listener di OrderPaid (online) e da quello di
 * OnSiteOrderConfirmed (in struttura); le righe senza partner non avvisano nessuno.
 */
class PartnerNewBookingMailTest extends TestCase
{
    use PlacesOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        Mail::fake();

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Invii ────────────────────────────────────────────────────────────────

    public function test_online_order_notifies_the_partner_once_for_all_its_lines(): void
    {
        $partner = User::factory()->stripeConnected()->create(['email' => 'partner@example.com']);
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->for($order)->create(['partner_user_id' => $partner->id]);

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(PartnerNewBookingMail::class, 1);
        Mail::assertSent(PartnerNewBookingMail::class, fn (PartnerNewBookingMail $mail): bool => $mail->hasTo('partner@example.com')
            && $mail->order->is($order)
            && $mail->partner->is($partner));
    }

    public function test_on_site_booking_notifies_the_partner(): void
    {
        $partner = User::factory()->offlinePartner()->create(['email' => 'fido@example.com']);

        $order = $this->confirmOnSiteBooking($partner);

        Mail::assertSent(PartnerNewBookingMail::class, 1);
        Mail::assertSent(PartnerNewBookingMail::class, fn (PartnerNewBookingMail $mail): bool => $mail->hasTo('fido@example.com')
            && $mail->order->is($order));
    }

    public function test_each_distinct_partner_gets_its_own_mail(): void
    {
        $anna = User::factory()->create(['email' => 'anna@example.com']);
        $bruno = User::factory()->create(['email' => 'bruno@example.com']);
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['partner_user_id' => $anna->id]);
        OrderItem::factory()->for($order)->create(['partner_user_id' => $bruno->id]);
        OrderItem::factory()->for($order)->create(['partner_user_id' => $anna->id]);

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(PartnerNewBookingMail::class, 2);
        Mail::assertSent(PartnerNewBookingMail::class, fn (PartnerNewBookingMail $mail): bool => $mail->hasTo('anna@example.com'));
        Mail::assertSent(PartnerNewBookingMail::class, fn (PartnerNewBookingMail $mail): bool => $mail->hasTo('bruno@example.com'));
    }

    public function test_a_line_without_partner_sends_no_partner_mail(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['partner_user_id' => null]);

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertNotSent(PartnerNewBookingMail::class);
    }

    public function test_a_failed_buyer_confirmation_does_not_block_the_partner_mail(): void
    {
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

        $partner = User::factory()->offlinePartner()->create(['email' => 'fido@example.com']);
        $order = Order::factory()->onSite()->create();
        OrderItem::factory()->for($order)->create(['partner_user_id' => $partner->id]);

        OnSiteOrderConfirmed::dispatch($order->fresh());

        Mail::assertNotSent(OrderConfirmationMail::class);
        Mail::assertSent(PartnerNewBookingMail::class, fn (PartnerNewBookingMail $mail): bool => $mail->hasTo('fido@example.com'));
    }

    // ── Lingua ───────────────────────────────────────────────────────────────

    public function test_the_partner_mail_ignores_the_language_of_the_buyer(): void
    {
        // Coda sync: il listener gira dentro la richiesta del checkout, con la
        // lingua di chi compra. Il partner non ha una lingua salvata.
        $partner = User::factory()->offlinePartner()->create(['first_name' => 'Marco']);
        $order = Order::factory()->onSite()->create();
        $item = OrderItem::factory()->for($order)->create(['partner_user_id' => $partner->id]);

        app()->setLocale('en');
        OnSiteOrderConfirmed::dispatch($order->fresh());

        $mail = Mail::sent(PartnerNewBookingMail::class)->first();
        $mail->assertHasSubject('Nuova prenotazione '.$order->order_number);

        $html = $mail->render();

        $this->assertStringContainsString('Hai una nuova prenotazione!', $html);
        $this->assertStringContainsString('Da incassare tu', $html);
        $this->assertStringNotContainsString('You have a new booking!', $html);
        $this->assertStringContainsString('href="'.url('/partner/prenotazioni/'.$item->id).'"', $html);
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function test_online_mail_renders_lines_paid_online_and_the_booking_link(): void
    {
        $partner = User::factory()->create(['first_name' => 'Marco']);
        $order = Order::factory()->paid()->create(['total_cents' => 50000]);
        $item = OrderItem::factory()->for($order)->create([
            'partner_user_id' => $partner->id,
            'title' => 'Hotel Brescia',
            'price_cents' => 50000,
            'options' => [
                'animals' => ['cane' => 1],
                'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 1],
            ],
            'booked_from' => Carbon::create(2026, 8, 1),
            'booked_until' => Carbon::create(2026, 8, 6),
        ]);

        $mail = new PartnerNewBookingMail($order->fresh(), $partner);
        $mail->assertHasSubject('Nuova prenotazione '.$order->order_number);

        $html = $mail->render();

        $this->assertStringContainsString('Hai una nuova prenotazione!', $html);
        $this->assertStringContainsString('Ciao Marco,', $html);
        $this->assertStringContainsString('ordine '.$order->order_number.' del 15/07/2026', $html);
        $this->assertStringContainsString('Hotel Brescia', $html);
        $this->assertStringContainsString('01/08/2026 - 06/08/2026', $html);
        $this->assertStringContainsString('Persone', $html);
        $this->assertMatchesRegularExpression('/<td[^>]*>\s*3\s*<\/td>/', $html);
        $this->assertStringContainsString("500\u{A0}€", $html);
        $this->assertStringContainsString('Pagato online', $html);
        $this->assertStringNotContainsString('Da incassare tu', $html);
        $this->assertStringContainsString('href="'.route('partner.bookings.show', ['booking' => $item->id]).'"', $html);
    }

    public function test_on_site_mail_says_the_partner_collects_the_amount(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $order = Order::factory()->onSite()->create(['total_cents' => 50000]);
        OrderItem::factory()->for($order)->create(['partner_user_id' => $partner->id, 'price_cents' => 50000]);

        $html = (new PartnerNewBookingMail($order->fresh(), $partner))->render();

        $this->assertStringContainsString("Da incassare tu: 500\u{A0}€", $html);
        $this->assertStringNotContainsString('Pagato online', $html);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * Prenotazione vera: carrello → PlaceOrderAction in modalità OnSite (5 notti = 500 €),
     * senza link di pagamento. Riga e ordine passano dagli helper del trait
     * PlacesOrders (addStructureLine e placeOnSiteOrder, Task 7).
     */
    private function confirmOnSiteBooking(User $partner): Order
    {
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => $partner->id,
            'price_cents' => 10000,
        ]));

        return $this->placeOnSiteOrder();
    }
}
