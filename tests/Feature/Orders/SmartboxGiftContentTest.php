<?php

namespace Tests\Feature\Orders;

use App\Mail\SmartboxGiftMail;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dedica e messaggio della smartbox regalo sono testo scelto dall'acquirente e
 * spedito da mg.animalamo.it a un indirizzo che non ha chiesto niente: l'unico
 * posto del progetto in cui un estraneo detta il contenuto di una nostra mail.
 * L'HTML grezzo lo neutralizza già Blade; la sintassi markdown no.
 */
class SmartboxGiftContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function giftItem(string $message, string $dedication = 'Anna'): OrderItem
    {
        return OrderItem::factory()->for(Order::factory()->gift()->create())->gift()->create([
            'options' => ['animals' => ['cane' => 1], 'gift' => [
                'dedication' => $dedication,
                'message' => $message,
                'recipient_email' => 'anna@example.com',
            ]],
        ]);
    }

    public function test_a_markdown_link_in_the_message_does_not_become_a_link(): void
    {
        $item = $this->giftItem('Auguri! [Ritira il tuo premio](https://sito-di-terzi.example/x)');

        $html = (new SmartboxGiftMail($item))->render();

        // L'URL resta visibile come testo — è pur sempre il messaggio di chi
        // compra, e nasconderlo sarebbe peggio. Quello che non deve esistere è
        // l'ancora: niente da cliccare verso un dominio che non è nostro.
        $this->assertStringNotContainsString('href="https://sito-di-terzi.example', $html);
        $this->assertStringContainsString('Ritira il tuo premio', $html);
    }

    /** Stessa sintassi, stessa cura: un'immagine remota è un tracking pixel scelto da altri. */
    public function test_a_markdown_image_in_the_dedication_does_not_become_an_image(): void
    {
        $item = $this->giftItem('Auguri!', '![x](https://sito-di-terzi.example/pixel.gif)');

        $html = (new SmartboxGiftMail($item))->render();

        $this->assertStringNotContainsString('src="https://sito-di-terzi.example', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    /** Il testo normale deve restare leggibile: la protezione non può mangiarsi le parentesi. */
    public function test_ordinary_brackets_survive_readable(): void
    {
        $item = $this->giftItem('Auguri (finalmente) dal team [reparto viaggi]');

        $html = (new SmartboxGiftMail($item))->render();

        $this->assertStringContainsString('Auguri (finalmente) dal team [reparto viaggi]', $html);
    }
}
