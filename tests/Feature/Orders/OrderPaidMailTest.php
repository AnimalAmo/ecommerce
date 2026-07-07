<?php

namespace Tests\Feature\Orders;

use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Mail\SmartboxGiftMail;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayment\OrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use RuntimeException;
use Tests\TestCase;

/**
 * Catena OrderPaymentObserver → OrderPaid → SendOrderPaidMails: conferma al
 * buyer, una mail regalo per destinatario, idempotenza sulla transizione a
 * Completed, più il render dei due markdown (copy italiano, Format::money).
 */
class OrderPaidMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Mail::fake();
    }

    // ── Observer → evento → listener ─────────────────────────────────────────

    public function test_completed_payment_sends_the_confirmation_to_the_buyer(): void
    {
        $order = Order::factory()->create(['email' => 'buyer@example.com']);
        OrderItem::factory()->for($order)->create();

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('buyer@example.com')
            && $mail->order->is($order));
        Mail::assertNotSent(SmartboxGiftMail::class);
    }

    public function test_each_gift_line_with_recipient_gets_its_own_gift_mail(): void
    {
        $order = Order::factory()->gift()->create();
        $anna = OrderItem::factory()->for($order)->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Anna', 'message' => 'Auguri!', 'recipient_email' => 'anna@example.com']],
        ]);
        $bruno = OrderItem::factory()->for($order)->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Bruno', 'message' => 'Buon viaggio!', 'recipient_email' => 'bruno@example.com']],
        ]);
        // Riga smartbox NON regalo nello stesso ordine: nessuna mail per lei.
        OrderItem::factory()->for($order)->forSmartbox()->create();

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertSent(SmartboxGiftMail::class, 2);
        Mail::assertSent(SmartboxGiftMail::class, fn (SmartboxGiftMail $mail): bool => $mail->hasTo('anna@example.com') && $mail->item->is($anna));
        Mail::assertSent(SmartboxGiftMail::class, fn (SmartboxGiftMail $mail): bool => $mail->hasTo('bruno@example.com') && $mail->item->is($bruno));
    }

    public function test_a_failed_send_does_not_block_the_other_mails_and_does_not_propagate(): void
    {
        // Fake che esplode sul PRIMO destinatario (la conferma al buyer):
        // simula SMTP giù / casella irraggiungibile. Con la coda sync il
        // listener gira in-process post-commit: l'eccezione non deve risalire
        // (l'utente ha già pagato) né trascinare gli invii successivi.
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

        $order = Order::factory()->gift()->create(['email' => 'buyer@example.com']);
        OrderItem::factory()->for($order)->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Anna', 'message' => 'Auguri!', 'recipient_email' => 'anna@example.com']],
        ]);
        OrderItem::factory()->for($order)->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Bruno', 'message' => 'Buon viaggio!', 'recipient_email' => 'bruno@example.com']],
        ]);

        // Nessuna eccezione deve propagarsi da handle() fin qui.
        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertNotSent(OrderConfirmationMail::class);
        Mail::assertSent(SmartboxGiftMail::class, 2);
        Mail::assertSent(SmartboxGiftMail::class, fn (SmartboxGiftMail $mail): bool => $mail->hasTo('anna@example.com'));
        Mail::assertSent(SmartboxGiftMail::class, fn (SmartboxGiftMail $mail): bool => $mail->hasTo('bruno@example.com'));
    }

    public function test_gift_line_without_recipient_email_sends_no_gift_mail(): void
    {
        $order = Order::factory()->gift()->create();
        OrderItem::factory()->for($order)->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Anna']],
        ]);

        OrderPayment::factory()->for($order)->completed()->create();

        Mail::assertSent(OrderConfirmationMail::class, 1);
        Mail::assertNotSent(SmartboxGiftMail::class);
    }

    public function test_order_paid_fires_once_per_transition_to_completed(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create();

        // Nasce Pending: nessuna mail.
        $payment = OrderPayment::factory()->for($order)->create();
        Mail::assertNothingSent();

        // Transizione a Completed (riconciliazione webhook): una mail.
        $payment->update(['status' => PaymentStatus::Completed, 'paid_at' => Carbon::now()]);
        Mail::assertSent(OrderConfirmationMail::class, 1);

        // Risalvataggi senza transizione: nessuna seconda mail.
        $payment->update(['provider_response' => ['status' => 'succeeded']]);
        $payment->touch();
        $payment->update(['status' => PaymentStatus::Completed]);
        Mail::assertSent(OrderConfirmationMail::class, 1);
    }

    // ── Render dei markdown ──────────────────────────────────────────────────

    public function test_confirmation_mail_renders_order_lines_and_totals(): void
    {
        $order = Order::factory()->paid()->create(['first_name' => 'Giulia', 'total_cents' => 47600]);
        OrderItem::factory()->for($order)->create([
            'title' => 'Hotel Brescia',
            'price_cents' => 21500,
            'booked_from' => Carbon::create(2026, 8, 1),
            'booked_until' => Carbon::create(2026, 8, 6),
        ]);
        OrderPayment::factory()->for($order)->completed()->create();

        $html = (new OrderConfirmationMail($order->fresh()))->render();

        $this->assertStringContainsString('Grazie del tuo acquisto!', $html);
        $this->assertStringContainsString('Ciao Giulia,', $html);
        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString('Hotel Brescia', $html);
        $this->assertStringContainsString('01/08/2026 - 06/08/2026', $html);
        // Format::money: spazio unificatore fra cifra e simbolo.
        $this->assertStringContainsString("215\u{A0}€", $html);
        $this->assertStringContainsString("476\u{A0}€", $html);
        $this->assertStringContainsString('Carta di credito o di debito', $html);
    }

    public function test_gift_mail_renders_dedication_message_and_validity(): void
    {
        $order = Order::factory()->gift()->create(['first_name' => 'Giulia', 'last_name' => 'Rossi']);
        $item = OrderItem::factory()->for($order)->gift()->create([
            'title' => 'Weekend di relax in Lombardia',
            'options' => ['animals' => ['cane' => 1], 'gift' => ['dedication' => 'Marco', 'message' => 'Tanti auguri!', 'recipient_email' => 'marco@example.com']],
            'booked_until' => Carbon::create(2027, 7, 15, 12, 0, 0),
        ]);

        $html = (new SmartboxGiftMail($item))->render();

        $this->assertStringContainsString('Hai ricevuto un regalo!', $html);
        $this->assertStringContainsString('Giulia Rossi', $html);
        $this->assertStringContainsString('Weekend di relax in Lombardia', $html);
        $this->assertStringContainsString('Dedicato a: Marco', $html);
        $this->assertStringContainsString('Messaggio: Tanti auguri!', $html);
        $this->assertStringContainsString('valida fino al 15/07/2027', $html);
    }
}
