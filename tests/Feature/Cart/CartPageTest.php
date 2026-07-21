<?php

namespace Tests\Feature\Cart;

use App\Livewire\Commerce\Cart;
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

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    private User $giulia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();
    }

    public function test_cart_page_renders_real_lines_with_nbsp_money(): void
    {
        $this->actingAs($this->giulia);
        $this->addHotelLine();    // 5 notti × 43 € = 215 €
        $this->addActivityLine(); // 118 € × 2 persone = 236 €

        $checkIn = CarbonImmutable::today()->addDays(20)->format('d/m/Y');
        $checkOut = CarbonImmutable::today()->addDays(25)->format('d/m/Y');

        $this->get('/carrello')
            ->assertOk()
            ->assertSeeText('Carrello (2 articoli)')
            // Riga struttura: titolo, località, date dalle options, etichette condivise.
            ->assertSee('Hotel Brescia')
            ->assertSee('Dario Boario Terme (BS), Italia')
            // assertSeeText: i marker BLOCK di Livewire spezzano il range nel markup.
            ->assertSeeText($checkIn.' - '.$checkOut)
            ->assertSee('2 adulti')
            ->assertSee('1 cane')
            ->assertSee("215\u{A0}€")
            // Riga attività (starts_at null: nessuna riga date).
            ->assertSee('Weekend di escursioni')
            ->assertSee("236\u{A0}€")
            // Totale reale del flusso (215 + 236), sempre via Format::money (NBSP).
            ->assertSee('Totale (2 articoli)')
            ->assertSee("451\u{A0}€");
    }

    public function test_edit_modal_reprices_the_line_on_confirm(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addHotelLine(); // 5 notti = 215 €

        $newCheckOut = CarbonImmutable::today()->addDays(22);

        Livewire::test(Cart::class)
            ->call('openEdit', $key)
            ->assertSet('editingKey', $key)
            ->assertSet('editingFamily', 'structure')
            // Copie di lavoro idratate dalle options della riga.
            ->assertSet('editCheckIn', CarbonImmutable::today()->addDays(20)->format('d/m/Y'))
            ->set('editCheckOut', $newCheckOut->format('d/m/Y'))
            ->call('confirmEdit')
            // Conferma ok: pop-up chiuso e card riprezzata (2 notti × 43 € = 86 €).
            ->assertSet('editingKey', null)
            ->assertSee("86\u{A0}€")
            ->assertDontSee("215\u{A0}€");

        // Riprezzo e options persistiti sulla riga (mai prezzi dal client).
        $item = CartItem::query()->findOrFail($key);
        $this->assertSame(8600, $item->price_cents);
        $this->assertSame($newCheckOut->toDateString(), $item->options['check_out']);
    }

    public function test_edit_modal_rejects_closed_dates_with_a_toast_and_stays_open(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addHotelLine(); // Hotel Brescia: chiusure seed a oggi+3..+5.

        Livewire::test(Cart::class)
            ->call('openEdit', $key)
            ->set('editCheckIn', CarbonImmutable::today()->addDays(2)->format('d/m/Y'))
            ->set('editCheckOut', CarbonImmutable::today()->addDays(6)->format('d/m/Y'))
            ->call('confirmEdit')
            // Violazione disponibilità: toast danger e pop-up ancora aperto.
            ->assertDispatched('toast-show')
            ->assertSet('editingKey', $key);

        // La riga resta invariata: niente riprezzo né merge delle options.
        $item = CartItem::query()->findOrFail($key);
        $this->assertSame(21500, $item->price_cents);
        $this->assertSame(CarbonImmutable::today()->addDays(20)->toDateString(), $item->options['check_in']);
    }

    public function test_remove_item_updates_total_and_count(): void
    {
        $this->actingAs($this->giulia);
        $hotelKey = $this->addHotelLine(); // 215 €
        $this->addActivityLine();          // 236 €

        Livewire::test(Cart::class)
            ->assertSeeText('Carrello (2 articoli)')
            ->assertSee("451\u{A0}€")
            ->call('removeItem', $hotelKey)
            // Conteggio (con singolare) e totale si riallineano al volo.
            ->assertSeeText('Carrello (1 articolo)')
            ->assertDontSee('Hotel Brescia')
            ->assertSee("236\u{A0}€")
            ->assertDontSee("215\u{A0}€");

        $this->assertDatabaseMissing('cart_items', ['id' => $hotelKey]);
    }

    public function test_empty_state_shows_the_three_most_favorited_products(): void
    {
        // Classifica pilotata sui conteggi preferiti (i seed di Giulia valgono 1 a testa):
        // Hotel Brescia 3 cuori, Vacanza in montagna 3 (tie-break: id crescente), smartbox 2.
        $activity = Event::where('slug', 'vacanza-montagna')->firstOrFail();
        $box = SmartboxPackage::where('slug', 'relax-lombardia-2')->firstOrFail();

        $this->addFavorites('structure', $this->hotel()->id, 2);
        $this->addFavorites('event', $activity->id, 2);
        $this->addFavorites('smartbox_package', $box->id, 1);

        // Guest a carrello vuoto: stato vuoto + card "più amate" reali, in ordine di classifica.
        $this->get('/carrello')
            ->assertOk()
            ->assertSee('Non hai ancora prenotato attività')
            ->assertSee('Le attività più amate su Animal-amo')
            ->assertSeeInOrder(['Hotel Brescia', 'Vacanza di relax in montagna', 'Weekend di relax in Lombardia'])
            // Righe meta per famiglia: rating, durata attività, validità smartbox.
            ->assertSee('4,5')
            ->assertSee('DURATA DI 5 GIORNI')
            ->assertSee('VALIDO PER 1 ANNO')
            // Prezzi "A partire da": price_from (0 nel mock) per struttura/smartbox, pieno per l'attività.
            ->assertSee("A partire da 0,00\u{A0}€")
            ->assertSee("A partire da 250\u{A0}€")
            // Il quarto classificato (1 cuore) resta fuori dalle 3 card.
            ->assertDontSee('Pomeriggio di addestramento')
            // Le stesse card servono il carosello mobile (artboard app "Carrello vuoto").
            ->assertSee('wire:key="most-loved-', false);
    }

    public function test_gift_mode_shows_only_gift_lines_and_vice_versa(): void
    {
        $this->actingAs($this->giulia);
        $this->addHotelLine(20, 22);  // 2 notti = 86 €, flusso normale
        $this->addGiftSmartboxLine(); // 215 €, flusso regalo

        // /carrello senza flag: SOLO la riga normale, totale sul set filtrato.
        $this->get('/carrello')
            ->assertOk()
            ->assertSeeText('Carrello (1 articolo)')
            ->assertSee('Hotel Brescia')
            ->assertSee("86\u{A0}€")
            ->assertDontSee('Weekend di relax in Lombardia')
            ->assertDontSee("215\u{A0}€");

        // ?regalo=1: SOLO la riga regalo, con la pill validità della smartbox.
        $this->get('/carrello?regalo=1')
            ->assertOk()
            ->assertSeeText('Carrello (1 articolo)')
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee('Smartbox valida per 1 anno')
            ->assertSee("215\u{A0}€")
            ->assertDontSee('Hotel Brescia')
            ->assertDontSee("86\u{A0}€");
    }

    public function test_go_to_checkout_persists_dedication_and_message_then_redirects(): void
    {
        $this->actingAs($this->giulia);
        $key = $this->addGiftSmartboxLine();

        Livewire::withQueryParams(['regalo' => 1])->test(Cart::class)
            ->assertSet('gift', true)
            ->set('giftDedication.'.$key, 'Marco')
            ->set('giftMessage.'.$key, 'Tanti auguri!')
            ->call('goToCheckout')
            ->assertRedirect(route('checkout', ['regalo' => 1]));

        // Dedica e messaggio persistiti nelle options.gift della riga regalo.
        $options = CartItem::query()->findOrFail($key)->options;
        $this->assertSame('Marco', $options['gift']['dedication']);
        $this->assertSame('Tanti auguri!', $options['gift']['message']);
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

    /** Riga struttura: Hotel Brescia, offset giorni relativi a oggi, 2 adulti e 1 cane. */
    private function addHotelLine(int $checkInDays = 20, int $checkOutDays = 25): int|string
    {
        return $this->cart()->addItem('structure', $this->hotel()->id, [
            'check_in' => CarbonImmutable::today()->addDays($checkInDays)->toDateString(),
            'check_out' => CarbonImmutable::today()->addDays($checkOutDays)->toDateString(),
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga attività: Weekend di escursioni (starts_at null), 2 adulti = 236 €. */
    private function addActivityLine(): int|string
    {
        return $this->cart()->addItem('event', Event::where('slug', 'weekend-escursioni')->firstOrFail()->id, [
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga regalo: smartbox Weekend di relax in Lombardia (215 €, validità 12 mesi). */
    private function addGiftSmartboxLine(): int|string
    {
        return $this->cart()->addItem('smartbox_package', SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail()->id, [
            'animals' => ['cane' => 1],
        ], true)->key;
    }

    /** Aggiunge $count preferiti (di utenti factory distinti) sul prodotto indicato. */
    private function addFavorites(string $type, int $id, int $count): void
    {
        User::factory()->count($count)->create()->each(fn (User $user) => $user->favorites()->create([
            'favoritable_type' => $type,
            'favoritable_id' => $id,
        ]));
    }
}
