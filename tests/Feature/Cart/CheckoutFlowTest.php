<?php

namespace Tests\Feature\Cart;

use App\Livewire\Cart;
use App\Livewire\Checkout;
use App\Models\CartItem\CartItem;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $giulia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();
    }

    public function test_cart_edits_are_reflected_in_the_checkout_summary(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addHotelLine(); // 5 notti = 215 €

        $checkIn = CarbonImmutable::today()->addDays(20)->format('d/m/Y');

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Riepilogo dell’ordine')
            ->assertSee('Hotel Brescia')
            // assertSeeText: i marker BLOCK di Livewire spezzano il range nel markup.
            ->assertSeeText($checkIn.' - '.CarbonImmutable::today()->addDays(25)->format('d/m/Y'))
            ->assertSee("215\u{A0}€");

        // Modifica dal pop-up del carrello: 5 notti → 2 notti (86 €).
        Livewire::test(Cart::class)
            ->call('openEdit', $key)
            ->set('editCheckOut', CarbonImmutable::today()->addDays(22)->format('d/m/Y'))
            ->call('confirmEdit');

        // Handoff reale: il riepilogo checkout riflette date e prezzo nuovi.
        $this->get('/checkout')
            ->assertOk()
            ->assertSeeText($checkIn.' - '.CarbonImmutable::today()->addDays(22)->format('d/m/Y'))
            ->assertSee("86\u{A0}€")
            ->assertDontSee("215\u{A0}€");
    }

    public function test_empty_cart_redirects_back_to_the_cart_per_flow(): void
    {
        // Guest senza carrello di sessione.
        $this->get('/checkout')->assertRedirect(route('carrello'));

        // Utente autenticato senza righe: si torna al carrello del flusso corrente.
        $this->actingAs(User::factory()->create());
        $this->get('/checkout')->assertRedirect(route('carrello'));
        $this->get('/checkout?regalo=1')->assertRedirect(route('carrello', ['regalo' => 1]));
    }

    public function test_gift_checkout_redirects_when_only_normal_lines_exist(): void
    {
        $this->actingAs($this->giulia);
        $this->addHotelLine();

        // Il set filtrato regalo è vuoto: guard attiva; il flusso normale passa.
        $this->get('/checkout?regalo=1')->assertRedirect(route('carrello', ['regalo' => 1]));
        $this->get('/checkout')->assertOk();
    }

    public function test_step_one_hydrates_from_the_authenticated_user(): void
    {
        $this->actingAs($this->giulia);
        $this->addHotelLine();

        Livewire::test(Checkout::class)
            ->assertSet('step', 1)
            ->assertSet('firstName', 'Giulia')
            ->assertSet('lastName', 'Rossi')
            ->assertSet('email', 'giulia.rossi@gmail.com')
            ->assertSet('phone', '340 5738920')
            // Paese statico: nessuna colonna a db (fatturazione = step 4).
            ->assertSet('country', 'Italia');
    }

    public function test_step_one_stays_empty_for_guests(): void
    {
        // Carrello guest in sessione (lo storage sessione è condiviso col componente).
        $this->addHotelLine();

        Livewire::test(Checkout::class)
            ->assertNoRedirect()
            ->assertSet('firstName', '')
            ->assertSet('lastName', '')
            ->assertSet('email', '')
            ->assertSet('phone', '');
    }

    public function test_recipient_email_is_required_and_valid_in_gift_mode(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addGiftSmartboxLine();

        Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->assertSet('gift', true)
            // Senza metadati regalo la card riepilogo non mostra righe dedica/messaggio.
            ->assertDontSee('Dedicato a:')
            ->call('goToStep', 2)
            ->assertHasErrors(['recipientEmail' => 'required'])
            ->assertSet('step', 1)
            ->assertSee("Inserisci l'email del destinatario.")
            ->set('recipientEmail', 'non-una-email')
            ->call('goToStep', 2)
            ->assertHasErrors(['recipientEmail' => 'email'])
            ->assertSet('step', 1);

        // Niente persistenza finché la validazione fallisce.
        $this->assertArrayNotHasKey('gift', CartItem::query()->findOrFail($key)->options);
    }

    public function test_recipient_email_is_not_required_in_the_normal_flow(): void
    {
        $this->actingAs($this->giulia);
        $this->addHotelLine();

        Livewire::test(Checkout::class)
            // Campo assente dallo step 1 del flusso normale.
            ->assertDontSee('Email del destinatario')
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 2);
    }

    public function test_recipient_email_is_persisted_to_the_gift_line_and_shown_at_step_three(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addGiftSmartboxLine(['dedication' => 'Marco', 'message' => 'Tanti auguri!']);

        $component = Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->set('recipientEmail', 'marco@example.com')
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        // Email destinatario nelle options.gift della riga; dedica e messaggio intatti.
        $options = CartItem::query()->findOrFail($key)->options;
        $this->assertSame('marco@example.com', $options['gift']['recipient_email']);
        $this->assertSame('Marco', $options['gift']['dedication']);
        $this->assertSame('Tanti auguri!', $options['gift']['message']);

        // Step 3 "Fatto!": email dalle options (niente const hardcoded) + righe dedica valorizzate.
        $component->call('goToStep', 3)
            ->assertSet('step', 3)
            ->assertSee('Grazie del tuo acquisto!')
            ->assertSee('La Smartbox è stata mandata all’email: marco@example.com')
            ->assertSee('Dedicato a: Marco')
            ->assertSee('Messaggio: Tanti auguri!');
    }

    public function test_shared_guest_and_animal_labels_render_at_checkout(): void
    {
        $this->actingAs($this->giulia);

        // Struttura con ospiti misti e specie multiple; attività con soli adulti.
        $this->cart()->addItem('structure', $this->hotel()->id, [
            'check_in' => CarbonImmutable::today()->addDays(20)->toDateString(),
            'check_out' => CarbonImmutable::today()->addDays(22)->toDateString(),
            'guests' => ['adulti' => 2, 'ragazzi' => 1, 'bambini' => 0],
            'animals' => ['cane' => 1, 'gatto' => 1],
        ], false);
        $this->addActivityLine(['cane' => 2]);

        // Etichette dagli helper condivisi (Format::guests / Format::animals), UNA implementazione.
        $this->get('/checkout')
            ->assertOk()
            ->assertSee('3 ospiti')
            ->assertSee('1 cane, 1 gatto')
            ->assertSee('2 adulti')
            ->assertSee('2 cani');

        // I vecchi duplicati guestsLabel/dogsLabel non esistono più nei componenti.
        $this->assertFalse(method_exists(Checkout::class, 'guestsLabel'));
        $this->assertFalse(method_exists(Checkout::class, 'dogsLabel'));
        $this->assertFalse(method_exists(Cart::class, 'guestsLabel'));
        $this->assertFalse(method_exists(Cart::class, 'dogsLabel'));
    }

    /** Facciata carrello (storage scelto dallo stato auth corrente). */
    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    /** Hotel Brescia, prima card per position (chiusure demo a oggi+3..+5). */
    private function hotel(): Structure
    {
        return Structure::where('slug', 'hotel-brescia')->orderBy('position')->firstOrFail();
    }

    /** Riga struttura: Hotel Brescia oggi+20 → oggi+25 (5 notti = 215 €), 2 adulti e 1 cane. */
    private function addHotelLine(): int|string
    {
        return $this->cart()->addItem('structure', $this->hotel()->id, [
            'check_in' => CarbonImmutable::today()->addDays(20)->toDateString(),
            'check_out' => CarbonImmutable::today()->addDays(25)->toDateString(),
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga attività: Weekend di escursioni (starts_at null), 2 adulti = 236 €. */
    private function addActivityLine(array $animals = ['cane' => 1]): int|string
    {
        return $this->cart()->addItem('event', Event::where('slug', 'weekend-escursioni')->firstOrFail()->id, [
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => $animals,
        ], false)->key;
    }

    /** Riga regalo: smartbox Weekend di relax in Lombardia (215 €), metadati gift opzionali. */
    private function addGiftSmartboxLine(array $gift = []): int|string
    {
        return $this->cart()->addItem('smartbox_package', SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail()->id, [
            'animals' => ['cane' => 1],
            ...($gift !== [] ? ['gift' => $gift] : []),
        ], true)->key;
    }
}
