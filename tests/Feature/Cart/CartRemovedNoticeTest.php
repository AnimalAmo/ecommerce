<?php

namespace Tests\Feature\Cart;

use App\Livewire\Commerce\Cart;
use App\Mail\CatalogModerationMail;
use App\Models\Cart\Cart as CartModel;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartNotice;
use App\Services\Cart\SessionCartStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Difetto C9 (audit 27/09/2026, corretto il 28/09/2026), visto dalla pagina
 * Carrello: le righe tolte senza che le togliesse il cliente — prodotto
 * ritirato, sospeso, rimandato al partner, cancellato, righe ospite scartate
 * all'accesso — prima sparivano in silenzio e la riga restava a database.
 *
 * I test della sezione «Difetto C9» di CartStorageTest guardano lo storage.
 * Qui si guarda quello che il cliente legge: l'avviso c'è, nomina il prodotto
 * col motivo giusto, si vede UNA volta, e la riga è davvero andata. E il
 * negativo, che è il rischio di una pulizia fatta sugli eventi del modello:
 * una scheda ancora in vetrina che cambia non esce da nessun carrello.
 *
 * Scritto dal tester il 28/09/2026.
 */
class CartRemovedNoticeTest extends TestCase
{
    use RefreshDatabase;

    private ?User $seller = null;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Utente registrato: il carrello a database ────────────────────────────

    public function test_la_smartbox_ritirata_si_legge_una_volta_nella_pagina_carrello(): void
    {
        $this->actingAs(User::factory()->create());
        $box = $this->smartbox();
        $this->addSmartboxLine($box);

        $box->forceFill(['withheld_at' => now()])->save();

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.heading'))
            ->assertSee(__('cart.notice.withheld', ['title' => $box->title]))
            ->assertDispatched('cart-updated');

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertNull(CartModel::query()->sole()->notice, 'Letto l\'avviso, sul carrello non resta niente.');

        // Seconda visita: l'avviso è stato consegnato.
        Livewire::test(Cart::class)
            ->assertDontSee(__('cart.notice.heading'))
            ->assertNotDispatched('cart-updated');
    }

    /** Il percorso vero dell'admin: CatalogAdmin::suspend, non una colonna scritta a mano. */
    public function test_una_struttura_sospesa_dal_pannello_esce_dal_carrello_con_l_avviso(): void
    {
        $this->actingAs(User::factory()->create());
        $structure = $this->structure();
        $this->addStructureLine($structure);

        app(CatalogAdmin::class)->suspend(Structure::withHidden()->findOrFail($structure->id));

        $this->assertDatabaseCount('cart_items', 0);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $structure->name]))
            // Al cliente non si dice chi l'ha tolta né perché: «non è più disponibile».
            ->assertDontSee(__('cart.notice.withheld', ['title' => $structure->name]));
    }

    public function test_una_scheda_rimandata_al_partner_per_modifiche_esce_dal_carrello(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $structure = $this->structure();
        $this->addStructureLine($structure);

        app(CatalogAdmin::class)->requestChanges(Structure::withHidden()->findOrFail($structure->id), 'Rifai le foto.');

        $this->assertDatabaseCount('cart_items', 0);
        Mail::assertQueued(CatalogModerationMail::class);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $structure->name]));
    }

    /**
     * La cancellazione definitiva dal pannello. CatalogAdmin::delete() toglie
     * le righe `cart_items` con una query secca PRIMA di `$item->delete()`,
     * quindi l'evento `deleted` registrato in CartServiceProvider non trova più
     * niente da annotare: la riga sparisce dal carrello del cliente senza una
     * parola — il difetto C9 com'era, sull'unico percorso di cancellazione che
     * esiste in produzione.
     *
     * Trovato dal tester il 28/09/2026 e chiuso lo stesso giorno:
     * CatalogAdmin::delete() ora passa da DatabaseCartStorage::withdrawProduct()
     * invece di cancellare le righe col query builder.
     */
    public function test_un_prodotto_cancellato_dal_pannello_esce_dal_carrello_con_l_avviso(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);
        $structure = $this->structure();
        $this->addStructureLine($structure);

        app(CatalogAdmin::class)->delete(Structure::withHidden()->findOrFail($structure->id));

        $this->assertDatabaseCount('cart_items', 0);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $structure->name]));
    }

    /** L'evento `deleted`: il modello in memoria ha ancora il nome da dire. */
    public function test_un_prodotto_cancellato_dal_modello_esce_dal_carrello_con_l_avviso(): void
    {
        $this->actingAs(User::factory()->create());
        $structure = $this->structure();
        $this->addStructureLine($structure);

        $structure->delete();

        $this->assertDatabaseCount('cart_items', 0);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $structure->name]));
    }

    /**
     * Chi scrive con il query builder salta gli eventi del modello: la
     * migrazione del 27/09 e il ritiro delle smartbox di C8
     * (PartnerPaymentModeService::syncSmartboxWithholding). La riga resta a
     * database fino alla prima lettura, e lì se ne va con l'avviso.
     */
    public function test_un_ritiro_scritto_con_una_query_secca_si_pulisce_alla_prima_lettura(): void
    {
        $this->actingAs(User::factory()->create());
        $box = $this->smartbox();
        $this->addSmartboxLine($box);

        SmartboxPackage::withHidden()->whereKey($box->id)->update(['withheld_at' => now()]);

        $this->assertDatabaseCount('cart_items', 1);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.withheld', ['title' => $box->title]));

        $this->assertDatabaseCount('cart_items', 0);
    }

    /**
     * Ogni carrello che conteneva il prodotto perde la riga e riceve l'avviso,
     * non solo quello di chi era loggato quando il prodotto è uscito.
     */
    public function test_il_prodotto_esce_da_tutti_i_carrelli_che_lo_contengono(): void
    {
        $box = $this->smartbox();

        $first = User::factory()->create();
        $this->actingAs($first);
        $this->addSmartboxLine($box);

        $second = User::factory()->create();
        $this->actingAs($second);
        $this->addSmartboxLine($box);

        Auth::logout();
        $box->forceFill(['withheld_at' => now()])->save();

        $this->assertDatabaseCount('cart_items', 0);

        foreach ([$first, $second] as $customer) {
            $this->actingAs($customer);

            Livewire::test(Cart::class)
                ->assertSee(__('cart.notice.withheld', ['title' => $box->title]));
        }
    }

    /**
     * Il negativo che conta: la pulizia sta sugli eventi `updated` dei modelli
     * di catalogo. Una modifica qualsiasi di una scheda ancora in vetrina
     * (prezzo, testi) non deve toglierla da nessun carrello.
     */
    public function test_la_modifica_di_una_scheda_ancora_in_vetrina_non_tocca_i_carrelli(): void
    {
        $this->actingAs(User::factory()->create());
        $structure = $this->structure();
        $this->addStructureLine($structure);

        Structure::withHidden()->findOrFail($structure->id)->update(['price_cents' => 12000]);

        $this->assertDatabaseCount('cart_items', 1);

        Livewire::test(Cart::class)
            ->assertDontSee(__('cart.notice.heading'))
            ->assertSee($structure->name);
    }

    /**
     * Il falso positivo che la lane ha già incontrato: un modello creato nella
     * stessa richiesta non ha `approval_status` in memoria (lo scrive il
     * default di colonna), e al primo update — l'incremento dei posti
     * prenotati di un evento — risultava nascosto e usciva da tutti i carrelli.
     */
    public function test_i_posti_prenotati_di_un_evento_non_lo_tolgono_dai_carrelli(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 2500,
            'max_participants' => 10,
            'starts_at' => '2026-08-10 18:00:00',
            'ends_at' => '2026-08-10 20:00:00',
        ]);

        $this->assertNull($event->approval_status, 'La fixture deve essere il modello appena creato, senza il default in memoria.');

        $this->actingAs(User::factory()->create());
        $this->cart()->addItem('event', $event->id, ['participants' => 2], false);

        $event->increment('booked_participants', 3);

        $this->assertDatabaseCount('cart_items', 1);
    }

    /**
     * L'avviso resta per tutta la visita — togliere un'altra riga non lo fa
     * sparire — finché il cliente non lo chiude con la X.
     */
    public function test_l_avviso_resta_finche_il_cliente_non_lo_chiude(): void
    {
        $this->actingAs(User::factory()->create());
        $gone = $this->structure();
        $kept = $this->structure();
        $this->addStructureLine($gone);
        $keptKey = $this->addStructureLine($kept);

        $gone->forceFill(['suspended_at' => now()])->save();

        $page = Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $gone->name]))
            ->assertSee($kept->name);

        $page->call('removeItem', $keptKey)
            ->assertSee(__('cart.notice.unavailable', ['title' => $gone->name]));

        $page->call('dismissRemovedNotice')
            ->assertDontSee(__('cart.notice.heading'));
    }

    /**
     * La voce nasce nella richiesta di chi ritira (qui in italiano) e si legge
     * in quella del cliente: il titolo resta con tutte le traduzioni, e la
     * frase si compone nella lingua di chi la legge.
     */
    public function test_l_avviso_si_legge_nella_lingua_del_cliente(): void
    {
        $this->actingAs(User::factory()->create());
        $box = SmartboxPackage::factory()->create([
            'user_id' => $this->seller()->id,
            'price_cents' => 21500,
            'title' => ['it' => 'Weekend di relax', 'en' => 'Relaxing weekend'],
        ]);
        $this->addSmartboxLine($box);

        $box->forceFill(['withheld_at' => now()])->save();

        app()->setLocale('en');

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.withheld', ['title' => 'Relaxing weekend'], 'en'))
            ->assertDontSee('Weekend di relax');
    }

    /** Lo scrive solo il server: il client non può riempirlo di frasi sue. */
    public function test_l_avviso_non_si_scrive_dal_client(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(Cart::class)->set('removedNotice', ['Il tuo ordine è stato annullato.']);
    }

    // ── Ospite: il carrello in sessione ──────────────────────────────────────

    public function test_ospite_la_smartbox_ritirata_esce_dalla_sessione_con_l_avviso_una_volta(): void
    {
        $box = $this->smartbox();
        $this->addSmartboxLine($box);

        $box->forceFill(['withheld_at' => now()])->save();

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.withheld', ['title' => $box->title]))
            ->assertDispatched('cart-updated');

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));

        Livewire::test(Cart::class)->assertDontSee(__('cart.notice.heading'));
    }

    public function test_ospite_la_struttura_sospesa_dice_che_non_e_piu_disponibile(): void
    {
        $structure = $this->structure();
        $this->addStructureLine($structure);

        $structure->forceFill(['suspended_at' => now()])->save();

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.unavailable', ['title' => $structure->name]));

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /**
     * Cancellata dal pannello: nella sessione dell'ospite resta l'entry, e il
     * prodotto non c'è più nemmeno per dirne il nome.
     */
    public function test_ospite_il_prodotto_cancellato_dal_pannello_esce_senza_nome(): void
    {
        $structure = $this->structure();
        $this->addStructureLine($structure);

        app(CatalogAdmin::class)->delete(Structure::withHidden()->findOrFail($structure->id));

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.untitled'));

        $this->assertSame([], session()->get(SessionCartStorage::SESSION_KEY, []));
    }

    /**
     * Review del 28/09/2026: l'avviso non vive dentro 'cart' (la prima
     * versione aveva spostato le righe in 'cart.items' per fargli posto). Le
     * entry restano nella forma di sempre, così un rollback del codice legge
     * ancora le sessioni scritte dopo il deploy, e l'avviso sta accanto.
     */
    public function test_ospite_l_avviso_non_cambia_la_forma_delle_righe_in_sessione(): void
    {
        $structure = $this->structure();
        $this->addStructureLine($structure);
        $box = $this->smartbox();
        $this->addSmartboxLine($box);
        // Query secca: salta gli eventi, la riga la toglie la lettura.
        SmartboxPackage::withHidden()->whereKey($box->id)->update(['suspended_at' => now()]);

        $this->cart()->items();

        $entries = session()->get('cart');
        $this->assertCount(1, $entries, 'Resta la sola riga della struttura.');
        $entry = array_values($entries)[0];
        $this->assertSame('structure', $entry['type']);
        $this->assertSame($structure->id, $entry['id']);
        $this->assertNotEmpty(session()->get(CartNotice::SESSION_KEY), 'L\'avviso sta accanto alle righe, non dentro.');
    }

    // ── All'accesso: righe ospite scartate dal merge ─────────────────────────

    public function test_le_righe_scartate_all_accesso_si_leggono_una_volta_nella_pagina_carrello(): void
    {
        $sellerB = User::factory()->stripeConnected()->create();
        $ofB = Structure::factory()->create(['user_id' => $sellerB->id, 'price_cents' => 10000]);
        $ofA = $this->structure();

        $customer = User::factory()->create();
        $this->actingAs($customer);
        $this->addStructureLine($ofB);

        Auth::logout();
        $this->addStructureLine($ofA);

        Auth::login($customer);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.heading'))
            ->assertSee($ofA->name)
            ->assertSee(__('cart.single_partner'))
            // La riga di B, quella che l'utente aveva già, è ancora lì.
            ->assertSee($ofB->name);

        Livewire::test(Cart::class)->assertDontSee(__('cart.notice.heading'));
    }

    /**
     * Una riga ospite il cui prodotto esce dal catalogo prima dell'accesso: il
     * merge la rifiuta con un 404, e l'avviso dice il motivo del ritiro, non un
     * generico «non è entrato».
     */
    public function test_una_riga_ospite_ritirata_prima_dell_accesso_viene_detta_col_suo_motivo(): void
    {
        $box = $this->smartbox();
        $this->addSmartboxLine($box);

        SmartboxPackage::withHidden()->whereKey($box->id)->update(['withheld_at' => now()]);

        $customer = User::factory()->create();
        Auth::login($customer);

        $this->assertDatabaseCount('cart_items', 0);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.withheld', ['title' => $box->title]));
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    /** Un venditore solo per tutte le righe: un ordine, un venditore. */
    private function seller(): User
    {
        return $this->seller ??= User::factory()->stripeConnected()->create();
    }

    private function structure(): Structure
    {
        return Structure::factory()->create(['user_id' => $this->seller()->id, 'price_cents' => 10000]);
    }

    private function smartbox(): SmartboxPackage
    {
        return SmartboxPackage::factory()->create(['user_id' => $this->seller()->id, 'price_cents' => 21500]);
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

    private function addSmartboxLine(SmartboxPackage $box): int|string
    {
        return $this->cart()->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false)->key;
    }
}
