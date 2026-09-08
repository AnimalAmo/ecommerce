<?php

namespace Tests\Feature;

use App\Models\Cart\Cart;
use App\Models\CartItem\CartItem;
use App\Models\Community\CommunityPost;
use App\Models\Event\Event;
use App\Models\Faq\Faq;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Page\Page;
use App\Models\Region\Region;
use App\Models\Review\Review;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Models\Venue\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La messa online su animalamo.it rinomina il sito di staging, quindi il
 * database arriva con dentro il catalogo mock dell'XD e le persone demo: il
 * seeder non li ricrea più, ma non cancella quelli già seminati. Questo comando
 * è l'unico passo distruttivo del runbook, e gira in produzione — quindi il
 * confine fra "mock" e "roba vera dei partner" è un test, non una convenzione.
 */
class PurgeMockCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function seedWithMockCatalog(): void
    {
        config(['app.seed_demo_data' => true]);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_removes_the_mock_catalog_and_the_demo_people(): void
    {
        $this->seedWithMockCatalog();

        $this->assertGreaterThan(0, Structure::count());
        $this->assertGreaterThan(0, Event::count());
        $this->assertGreaterThan(0, User::count());

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertSame(0, Structure::count());
        $this->assertSame(0, Event::count());
        $this->assertSame(0, SmartboxPackage::count());
        $this->assertSame(0, Review::count());
        $this->assertSame(0, CommunityPost::count());
        $this->assertSame(0, User::whereIn('email', [
            'giulia.rossi@gmail.com',
            'partner@animalamo.test',
            'susanna.rossi@pec.it',
        ])->count());

        // Le righe polimorfiche non hanno vincolo di chiave esterna: senza
        // pulizia esplicita resterebbero appese a id che non esistono più.
        $this->assertSame(0, DB::table('amenityables')->count());
        $this->assertSame(0, DB::table('reviews')->count());
    }

    /**
     * faqs, cart_items e favorites dopo il solo seed sono vuote, quindi
     * asserirle a zero non proverebbe niente: qui le righe si creano apposta,
     * agganciate a una struttura mock e a una vera, e si guarda quali restano.
     */
    public function test_it_only_unhooks_what_hangs_from_the_mock(): void
    {
        $this->seedWithMockCatalog();

        $mock = Structure::whereNull('user_id')->whereNull('structure_draft_id')->firstOrFail();

        $partner = User::factory()->create();
        $real = Structure::factory()->create(['user_id' => $partner->id]);

        $customer = User::factory()->create();
        $cart = Cart::create(['user_id' => $customer->id]);

        foreach ([$mock, $real] as $structure) {
            Faq::factory()->create(['faqable_type' => 'structure', 'faqable_id' => $structure->id]);
            CartItem::create([
                'cart_id' => $cart->id,
                'purchasable_type' => 'structure',
                'purchasable_id' => $structure->id,
                'price_cents' => 1000,
            ]);
            DB::table('favorites')->insert([
                'user_id' => $customer->id,
                'favoritable_type' => 'structure',
                'favoritable_id' => $structure->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        foreach (['faqs' => 'faqable', 'cart_items' => 'purchasable', 'favorites' => 'favoritable'] as $table => $morph) {
            $this->assertSame(0, DB::table($table)->where($morph.'_id', $mock->id)->where($morph.'_type', 'structure')->count(), $table);
            $this->assertSame(1, DB::table($table)->where($morph.'_id', $real->id)->where($morph.'_type', 'structure')->count(), $table);
        }
    }

    /**
     * Il difetto più pericoloso trovato in review: fino all'08/09/2026 il
     * wizard partner era pubblico e salvava le bozze da ospite, quindi esiste
     * contenuto vero con user_id nullo. A distinguerlo è la bozza dietro.
     */
    public function test_it_spares_content_published_before_the_wizard_required_a_login(): void
    {
        $this->seedWithMockCatalog();

        $draft = StructureDraft::create([
            'user_id' => null,
            'status' => StructureDraft::STATUS_COMPLETED,
            'service_category' => 'struttura',
        ]);
        $orphanButReal = Structure::factory()->create([
            'user_id' => null,
            'structure_draft_id' => $draft->id,
        ]);

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertDatabaseHas('structures', ['id' => $orphanButReal->id]);
    }

    /** La delete di massa non fa scattare l'hook di spatie: i ruoli resterebbero appesi. */
    public function test_it_does_not_leave_orphan_roles_behind(): void
    {
        $this->seedWithMockCatalog();

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $orphans = DB::table('model_has_roles')
            ->where('model_type', 'user')
            ->whereNotIn('model_id', User::pluck('id'))
            ->count();

        $this->assertSame(0, $orphans);
    }

    /** Gli ordini demo sono nullOnDelete: senza cancellarli resterebbero orfani, con dentro nome e mail della persona. */
    public function test_it_removes_the_demo_orders_and_their_payments(): void
    {
        $this->seedWithMockCatalog();

        $this->assertGreaterThan(0, DB::table('orders')->count());

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('order_payments')->count());
        $this->assertSame(0, DB::table('structure_drafts')->count());
    }

    /** I luoghi del mock non scendono in cascata con gli eventi: la FK va nel verso opposto. */
    public function test_it_removes_the_mock_venues(): void
    {
        $this->seedWithMockCatalog();

        $this->assertGreaterThan(0, Venue::count());

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertSame(0, Venue::count());
    }

    /** Un comando che si esegue una volta sola deve poter essere provato prima. */
    public function test_the_dry_run_counts_without_deleting(): void
    {
        $this->seedWithMockCatalog();

        $before = Structure::count();

        $this->artisan('animalamo:purge-mock-catalog --dry-run')->assertSuccessful();

        $this->assertSame($before, Structure::count());
    }

    public function test_it_keeps_the_platform_data(): void
    {
        $this->seedWithMockCatalog();

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertSame(20, Region::count());
        $this->assertSame(3, Role::count());
        $this->assertGreaterThan(0, Page::count());
        $this->assertGreaterThan(0, DB::table('articles')->count());
        $this->assertGreaterThan(0, DB::table('provinces')->count());
        $this->assertGreaterThan(0, DB::table('amenities')->count());
        $this->assertGreaterThan(0, DB::table('payment_gateways')->count());
    }

    /** Il badge della home conta davvero, ma la colonna del mock resta finché non la si azzera. */
    public function test_it_zeroes_the_frozen_structures_count(): void
    {
        $this->seedWithMockCatalog();

        $this->assertGreaterThan(0, Region::sum('structures_count'));

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertSame(0, (int) Region::sum('structures_count'));
    }

    /** Il discrimine è il proprietario: un partner vero, non una persona demo. */
    public function test_it_spares_what_a_partner_published(): void
    {
        $this->seedWithMockCatalog();

        $partner = User::factory()->create();
        $published = Structure::factory()->create(['user_id' => $partner->id]);
        $publishedEvent = Event::factory()->create(['user_id' => $partner->id]);

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertDatabaseHas('structures', ['id' => $published->id]);
        $this->assertDatabaseHas('events', ['id' => $publishedEvent->id]);
        $this->assertDatabaseHas('users', ['id' => $partner->id]);
    }

    /**
     * order_items è uno snapshot (titolo e prezzo restano), quindi cancellare
     * un prodotto non riscrive la storia dell'ordine — ma il morph resterebbe
     * puntato a un id sparito. Va azzerato, non lasciato appeso.
     */
    public function test_it_unhooks_the_orders_from_the_deleted_products(): void
    {
        $this->seedWithMockCatalog();

        $structure = Structure::whereNull('user_id')->firstOrFail();
        $order = Order::factory()->create();
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'title' => 'Soggiorno acquistato prima della pulizia',
        ]);

        $this->artisan('animalamo:purge-mock-catalog --force')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'title' => 'Soggiorno acquistato prima della pulizia',
            'purchasable_type' => null,
            'purchasable_id' => null,
        ]);
    }

    /** Senza --force in produzione il comando deve fermarsi da solo. */
    public function test_it_refuses_to_run_unattended_in_production(): void
    {
        $this->seedWithMockCatalog();
        $this->app['env'] = 'production';

        $this->artisan('animalamo:purge-mock-catalog')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();

        $this->assertGreaterThan(0, Structure::count());
    }
}
