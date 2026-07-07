<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem\CartItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
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
