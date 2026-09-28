<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartValidationException;
use App\Models\CartItem\CartItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartNotice;
use App\Services\Cart\SessionCartStorage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Storage del carrello (CartManager + SessionCartStorage/DatabaseCartStorage)
 * e merge al login: dedup applicativo sulle options canonicalizzate, riprezzo
 * sempre server-side, whitelist morph (400/404) e ownership delle chiavi riga.
 */
class CartStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_add_stores_the_line_in_session_without_touching_the_database(): void
    {
        $structure = $this->structure();

        $item = $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        // Prezzo quotato server-side: 2 notti × 4300 cents, supplemento animali seed 0.
        $this->assertSame(8600, $item->priceCents);
        $this->assertFalse($item->isGift);

        // Chiave riga = hash deterministico del contenuto canonicalizzato.
        $this->assertSame(
            SessionCartStorage::itemKey('structure', $structure->id, false, CartManager::canonicalize($this->structureOptions())),
            $item->key,
        );

        // L'entry vive solo in sessione: nessuna riga carts/cart_items da guest.
        $this->assertCount(1, session()->get(SessionCartStorage::SESSION_KEY));
        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guest_update_merges_options_and_reprices_the_single_line(): void
    {
        $manager = $this->manager();
        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);

        // Da 2 a 5 notti: la chiave (hash del contenuto) cambia, la riga resta una.
        $updated = $manager->updateItem($item->key, ['check_out' => Carbon::today()->addDays(12)->toDateString()]);

        $this->assertSame(21500, $updated->priceCents);
        $this->assertNotSame($item->key, $updated->key);
        $this->assertCount(1, $manager->items());
        $this->assertSame(21500, $manager->total());

        // Le options non passate restano: merge, non replace.
        $this->assertSame(['cane' => 1], $manager->items()->first()->options['animals']);
    }

    public function test_guest_remove_and_clear_empty_the_session_cart(): void
    {
        $manager = $this->manager();

        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $this->smartbox()->id, ['animals' => ['cane' => 1]], false);

        $manager->removeItem($item->key);

        $this->assertSame(1, $manager->count());

        $manager->clear();

        $this->assertSame(0, $manager->count());
        $this->assertNull(session()->get(SessionCartStorage::SESSION_KEY));
    }

    public function test_guest_repeated_add_with_identical_canonical_options_dedupes_to_a_single_line(): void
    {
        $structure = $this->structure();
        $manager = $this->manager();

        $first = $manager->addItem('structure', $structure->id, $this->structureOptions(), false);

        // Stesso contenuto con chiavi in ordine diverso: la canonicalizzazione (ksort
        // ricorsivo) produce la stessa riga qualunque sia l'ordine di arrivo dal widget.
        $shuffled = array_reverse($this->structureOptions(), true);
        $shuffled['guests'] = array_reverse($shuffled['guests'], true);

        // Il prodotto è rincarato nel frattempo: l'add ripetuto riallinea lo snapshot.
        $structure->update(['price_cents' => 5000]);

        $second = $manager->addItem('structure', $structure->id, $shuffled, false);

        $this->assertSame($first->key, $second->key);
        $this->assertCount(1, $manager->items());
        $this->assertSame(10000, $manager->items()->first()->priceCents);
    }

    public function test_different_options_or_gift_flag_create_separate_lines(): void
    {
        $manager = $this->manager();
        $structure = $this->structure();
        $box = $this->smartbox();

        $manager->addItem('structure', $structure->id, $this->structureOptions(), false);
        // Stessa struttura ma date diverse: seconda riga.
        $manager->addItem('structure', $structure->id, $this->structureOptions(7, 12), false);
        // Stessa smartbox e stesse options ma is_gift diverso: righe separate.
        $manager->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);
        $manager->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], true);

        $this->assertSame(4, $manager->count());
        $this->assertCount(1, $manager->items(true));
        $this->assertCount(3, $manager->items(false));
    }

    public function test_authenticated_add_persists_cart_and_item_rows(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();

        $this->actingAs($user);

        $item = $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        // Chiave riga = id cart_items per l'utente autenticato.
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'id' => $item->key,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'is_gift' => false,
            'price_cents' => 8600,
        ]);

        // Lo storage guest non viene toccato dall'utente autenticato.
        $this->assertNull(session()->get(SessionCartStorage::SESSION_KEY));
    }

    public function test_authenticated_repeated_add_dedupes_and_realigns_the_price(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();

        $this->actingAs($user);

        $first = $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        // Chiavi options in ordine diverso + prezzo cambiato: stessa riga, snapshot fresco.
        $shuffled = array_reverse($this->structureOptions(), true);
        $structure->update(['price_cents' => 5000]);

        $second = $this->manager()->addItem('structure', $structure->id, $shuffled, false);

        $this->assertSame($first->key, $second->key);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['id' => $first->key, 'price_cents' => 10000]);
    }

    public function test_authenticated_update_reprices_the_row_in_place(): void
    {
        $this->actingAs(User::factory()->create());

        $manager = $this->manager();
        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);

        $checkOut = Carbon::today()->addDays(12)->toDateString();
        $updated = $manager->updateItem($item->key, ['check_out' => $checkOut]);

        // Stessa riga db (la chiave è l'id), prezzo e options aggiornati.
        $this->assertSame($item->key, $updated->key);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['id' => $item->key, 'price_cents' => 21500]);
        $this->assertSame($checkOut, CartItem::findOrFail($item->key)->options['check_out']);
    }

    public function test_authenticated_remove_and_clear_delete_only_item_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $manager = $this->manager();
        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $this->smartbox()->id, ['animals' => ['cane' => 1]], false);

        $manager->removeItem($item->key);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->key]);
        $this->assertDatabaseCount('cart_items', 1);

        $manager->clear();

        // Svuotare le righe non elimina la riga carts dell'utente.
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_still_holds_tells_whether_a_snapshot_is_still_in_the_database_cart(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $manager = $this->manager();
        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $this->smartbox()->id, ['animals' => ['cane' => 1]], false);
        $snapshot = $manager->items();

        $this->assertTrue($manager->stillHolds($snapshot));
        $this->assertTrue($manager->stillHolds(collect()));

        // Lo stesso snapshot letto da un altro utente non è nel suo carrello.
        $this->actingAs(User::factory()->create());
        $this->assertFalse($manager->stillHolds($snapshot));

        // Basta una riga tolta (prenotata o eliminata altrove) perché lo snapshot non valga più.
        $this->actingAs($owner);
        $manager->removeItem($item->key);
        $this->assertFalse($manager->stillHolds($snapshot));
    }

    public function test_still_holds_tells_whether_a_snapshot_is_still_in_the_session_cart(): void
    {
        $manager = $this->manager();
        $item = $manager->addItem('structure', $this->structure()->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $this->smartbox()->id, ['animals' => ['cane' => 1]], false);
        $snapshot = $manager->items();

        $this->assertTrue($manager->stillHolds($snapshot));

        $manager->removeItem($item->key);
        $this->assertFalse($manager->stillHolds($snapshot));
    }

    public function test_reads_never_create_a_carts_row(): void
    {
        $this->actingAs(User::factory()->create());

        $manager = $this->manager();

        $this->assertTrue($manager->get()->isEmpty());
        $this->assertCount(0, $manager->items());
        $this->assertSame(0, $manager->count());
        $this->assertSame(0, $manager->total());

        // Le letture passano da resolveCart(): nessuna riga carts vuota dai render.
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_invalid_morph_alias_is_rejected_with_400(): void
    {
        $this->actingAs(User::factory()->create());

        // Né alias fuori whitelist né class-string (pattern FavoriteService).
        $this->assertAborts(400, fn () => $this->manager()->addItem('venue', 1, [], false));
        $this->assertAborts(400, fn () => $this->manager()->addItem(Structure::class, $this->structure()->id, $this->structureOptions(), false));

        $this->assertDatabaseCount('carts', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_missing_product_id_is_rejected_with_404(): void
    {
        $this->assertAborts(404, fn () => $this->manager()->addItem('structure', 999999, $this->structureOptions(), false));

        // Da guest: nemmeno l'entry di sessione viene creata.
        $this->assertNull(session()->get(SessionCartStorage::SESSION_KEY));
    }

    public function test_line_keys_of_another_user_are_not_reachable(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner);
        $item = $this->manager()->addItem('structure', $this->structure()->id, $this->structureOptions(), false);

        $this->actingAs($other);

        // removeItem è idempotente: la riga altrui resta (no-op silenzioso).
        $this->manager()->removeItem($item->key);
        $this->assertDatabaseHas('cart_items', ['id' => $item->key]);

        // update/updateGift su chiave altrui: stesso 404 della riga inesistente.
        $this->assertAborts(404, fn () => $this->manager()->updateItem($item->key, ['check_out' => Carbon::today()->addDays(12)->toDateString()]));
        $this->assertAborts(404, fn () => $this->manager()->updateGift($item->key, ['dedication' => 'Per te']));

        // E le letture dell'altro utente non vedono la riga.
        $this->assertCount(0, $this->manager()->items());
    }

    public function test_login_merges_the_session_cart_into_the_database_cart(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();
        $box = $this->smartbox();

        // Carrello guest: una riga normale e una regalo.
        $manager = $this->manager();
        $manager->addItem('structure', $structure->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], true);

        Auth::login($user);

        // Le righe migrano nel carrello db dell'utente (listener MergeCartOnLogin)...
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'is_gift' => false,
            'price_cents' => 8600,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'purchasable_type' => 'smartbox_package',
            'purchasable_id' => $box->id,
            'is_gift' => true,
            'price_cents' => 21500,
        ]);
        $this->assertDatabaseCount('cart_items', 2);

        // ...e la sessione guest viene svuotata.
        $this->assertNull(session()->get(SessionCartStorage::SESSION_KEY));
    }

    public function test_merge_reprices_session_lines_server_side(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();

        // Snapshot guest a 8600 (2 notti × 4300).
        $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        // Il prezzo cambia mentre la riga è in sessione: al merge vince la quotazione fresca.
        $structure->update(['price_cents' => 5000]);

        Auth::login($user);

        $this->assertDatabaseHas('cart_items', [
            'purchasable_id' => $structure->id,
            'price_cents' => 10000,
        ]);
    }

    public function test_merge_dedupes_against_existing_database_lines(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();

        // Riga già nel carrello db dell'utente...
        $this->actingAs($user);
        $existing = $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        Auth::logout();

        // ...e stessa riga aggiunta da guest: al merge niente somma, riga unica.
        $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        Auth::login($user);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['id' => $existing->key, 'price_cents' => 8600]);
    }

    public function test_merge_silently_drops_lines_no_longer_valid(): void
    {
        $user = User::factory()->create();
        $structure = $this->structure();
        $valid = $this->smartbox();
        $vanished = SmartboxPackage::where('slug', 'piemonte')->firstOrFail();

        $manager = $this->manager();
        $manager->addItem('structure', $structure->id, $this->structureOptions(), false);
        $manager->addItem('smartbox_package', $vanished->id, ['animals' => ['cane' => 1]], false);
        $manager->addItem('smartbox_package', $valid->id, ['animals' => ['cane' => 1]], false);

        // Dopo l'aggiunta la struttura chiude nel range prenotato (CartValidationException)
        // e una smartbox sparisce dal catalogo (404): entrambe scartate senza errori.
        $structure->closures()->create(['date' => Carbon::today()->addDays(7)->toDateString()]);
        $vanished->delete();

        Auth::login($user);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'purchasable_type' => 'smartbox_package',
            'purchasable_id' => $valid->id,
        ]);

        // La sessione guest viene svuotata comunque, righe scartate incluse.
        $this->assertNull(session()->get(SessionCartStorage::SESSION_KEY));
    }

    // ── Difetto C9: le righe che escono dal catalogo sparicono in silenzio ─────
    //
    // `CartItem::purchasable()` è un `morphTo()` nudo, quindi eredita il global
    // scope: con `withheld_at` o `suspended_at` valorizzati torna null, e i due
    // storage filtrano `purchasable !== null` senza una parola. La riga resta a
    // database per sempre, perché ClearCartPipe rimuove solo le chiavi ordinate.
    // Per confronto `OrderItem::purchasable()` toglie lo scope di proposito.

    public function test_una_riga_ritirata_dal_catalogo_non_sparisce_senza_dirlo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $box = $this->smartbox();
        $this->manager()->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);

        $this->assertCount(1, $this->manager()->items());

        // La smartbox viene ritirata dalla vetrina (partner passato al pagamento diretto).
        $box->forceFill(['withheld_at' => now()])->save();

        // Il cliente riapre il carrello e trova un totale più basso: la riga non
        // si vede più e nessuno gli ha detto niente.
        $this->assertSame(
            0,
            CartItem::query()->count(),
            'Una riga che non si può più mostrare va rimossa dal carrello (e il cliente avvisato), '
            .'non lasciata a database come fantasma che sposta il totale in silenzio.',
        );

        // Tester 28/09/2026: il nome del test promette anche il «dirlo», che
        // l'asserzione sopra non guardava. L'avviso nomina la smartbox e il motivo.
        $this->assertSame(
            [__('cart.notice.withheld', ['title' => $box->title])],
            app(CartNotice::class)->pull(),
        );
    }

    public function test_una_riga_sospesa_dallamministrazione_non_sparisce_senza_dirlo(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $structure = $this->structure();
        $this->manager()->addItem('structure', $structure->id, $this->structureOptions(), false);

        $structure->forceFill(['suspended_at' => now()])->save();

        $this->assertSame(0, CartItem::query()->count());
        // Tester 28/09/2026: come sopra, anche il «dirlo». Al cliente non si
        // spiega che l'ha sospesa l'amministrazione: «non è più disponibile».
        $this->assertSame(
            [__('cart.notice.unavailable', ['title' => $structure->name])],
            app(CartNotice::class)->pull(),
        );
    }

    /**
     * Lo stesso dal carrello guest, dove la riga vive come entry di sessione.
     *
     * Riscritto dal tester il 28/09/2026. La prima versione leggeva la sessione
     * subito dopo il `save()` del prodotto, senza che nessuno riaprisse il
     * carrello: nel test la sessione di chi ritira (admin, partner, comando) e
     * quella dell'ospite sono lo stesso oggetto, in produzione no — la sessione
     * dell'ospite in quella richiesta non esiste, e farlo passare avrebbe
     * voluto dire ripulire la sessione di chi salva il prodotto. Il difetto vero
     * è «a vita»: l'entry restava anche dopo che l'ospite aveva riaperto il
     * carrello. Quindi l'ospite lo riapre, e dopo l'entry non c'è più e
     * l'avviso sì.
     */
    public function test_una_riga_guest_ritirata_non_resta_in_sessione(): void
    {
        $box = $this->smartbox();
        $this->manager()->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);

        $box->forceFill(['withheld_at' => now()])->save();

        // L'ospite torna: qualunque lettura del carrello (badge, pagina, checkout).
        $this->assertCount(0, $this->manager()->items());

        $this->assertSame(
            [],
            session()->get(SessionCartStorage::SESSION_KEY, []),
            'La entry di sessione che non si può più mostrare non deve restare in sessione a vita.',
        );
        $this->assertSame(
            [__('cart.notice.withheld', ['title' => $box->title])],
            app(CartNotice::class)->pull(),
        );
    }

    /**
     * L'altra metà di C9: al login le righe del partner sbagliato vengono
     * scartate da `guardSinglePartner` e finiscono in un `Log::info`. Il cliente
     * vede un carrello più corto di quello che aveva, senza sapere perché.
     */
    public function test_le_righe_scartate_al_merge_vengono_dette_al_cliente(): void
    {
        $partnerA = User::factory()->stripeConnected()->create();
        $partnerB = User::factory()->stripeConnected()->create();

        $ofB = Structure::factory()->create(['user_id' => $partnerB->id, 'price_cents' => 10000]);
        $ofA = Structure::factory()->create(['user_id' => $partnerA->id, 'price_cents' => 10000]);

        // L'utente ha già una riga del partner B nel suo carrello a database.
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->manager()->addItem('structure', $ofB->id, $this->structureOptions(), false);

        // Poi esce, e da ospite riempie il carrello col partner A.
        Auth::logout();
        session()->forget(SessionCartStorage::SESSION_KEY);
        $this->manager()->addItem('structure', $ofA->id, $this->structureOptions(), false);

        Auth::login($user);

        // Oggi: la riga di A viene buttata con un Log::info e nessun avviso.
        $this->assertNotNull(
            session(CartNotice::SESSION_KEY),
            'Le righe scartate al merge («un ordine, un venditore») devono essere dette: senza, il '
            .'cliente deve indovinare che il carrello è cambiato accedendo.',
        );

        // Tester 28/09/2026: un avviso qualunque non basta. Deve nominare la riga
        // scartata e dire perché, con il messaggio della regola violata; e la riga
        // di B, quella che l'utente aveva già, resta.
        $this->assertSame(
            [trim(__('cart.notice.not_merged', [
                'title' => $ofA->name,
                'reason' => CartValidationException::singlePartner()->getMessage(),
            ]))],
            app(CartNotice::class)->pull(),
        );
        $this->assertSame([$ofB->id], CartItem::query()->pluck('purchasable_id')->all());
    }

    public function test_login_with_an_empty_session_cart_is_a_noop(): void
    {
        $user = User::factory()->create();

        // Carrello db esistente (es. re-login dalla pagina sicurezza del profilo).
        $this->actingAs($user);
        $this->manager()->addItem('structure', $this->structure()->id, $this->structureOptions(), false);

        Auth::logout();
        Auth::login($user);

        // Nessuna sessione guest da riversare: il carrello db resta com'era.
        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseCount('cart_items', 1);
    }

    /** Facciata del carrello (singleton, driver scelto per chiamata su Auth::check()). */
    private function manager(): CartManager
    {
        return app(CartManager::class);
    }

    /** Hotel Mantova Residence (position 3): hotel a 4300 cents/notte, nessuna chiusura seedata. */
    private function structure(): Structure
    {
        return Structure::where('position', 3)->firstOrFail();
    }

    private function smartbox(): SmartboxPackage
    {
        return SmartboxPackage::where('slug', 'relax-lombardia')->firstOrFail();
    }

    /** Options canoniche famiglia structure con date relative a oggi (default 2 notti). */
    private function structureOptions(int $checkInDays = 7, int $checkOutDays = 9): array
    {
        return [
            'animals' => ['cane' => 1],
            'check_in' => Carbon::today()->addDays($checkInDays)->toDateString(),
            'check_out' => Carbon::today()->addDays($checkOutDays)->toDateString(),
            'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
        ];
    }

    /** Esegue la closure aspettandosi un abort con lo status HTTP dato. */
    private function assertAborts(int $status, callable $callback): void
    {
        try {
            $callback();

            $this->fail("Atteso abort {$status}, nessuna eccezione lanciata.");
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}
