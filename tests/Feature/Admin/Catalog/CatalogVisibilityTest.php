<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Event\Event;
use App\Models\OrderItem\OrderItem;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Una scheda sospesa o in attesa di approvazione sparisce dal sito — liste,
 * dettagli, conteggi, acquisto — ma resta legata agli ordini già fatti.
 */
class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_and_pending_rows_are_out_of_every_default_query(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Visibile']]);
        Structure::factory()->create(['name' => ['it' => 'Sospesa'], 'suspended_at' => now()]);
        Structure::factory()->create(['name' => ['it' => 'In attesa']])->forceFill(['approval_status' => 'pending'])->save();

        $this->assertSame(['Visibile'], Structure::query()->get()->map->getTranslation('name', 'it')->all());
        $this->assertSame(3, Structure::withHidden()->count());
    }

    public function test_a_suspended_structure_detail_is_a_404(): void
    {
        $region = Region::factory()->create(['slug' => 'lombardia']);
        $structure = Structure::factory()->create(['region_id' => $region->id, 'slug' => 'hotel-sospeso']);

        $this->get('/animal-holiday/lombardia/hotel-sospeso')->assertOk();

        $structure->forceFill(['suspended_at' => now()])->save();

        $this->get('/animal-holiday/lombardia/hotel-sospeso')->assertNotFound();
    }

    public function test_a_suspended_event_and_smartbox_detail_are_404(): void
    {
        $event = Event::factory()->create(['slug' => 'yoga-sospeso', 'type' => 'event']);
        $box = SmartboxPackage::factory()->create(['slug' => 'box-sospeso']);

        $event->forceFill(['suspended_at' => now()])->save();
        $box->forceFill(['approval_status' => 'pending'])->save();

        $this->get('/eventi/yoga-sospeso')->assertNotFound();
        $this->get('/smartbox/box-sospeso')->assertNotFound();
    }

    public function test_the_region_count_ignores_hidden_structures(): void
    {
        $region = Region::factory()->create();
        Structure::factory()->count(2)->create(['region_id' => $region->id]);
        Structure::factory()->create(['region_id' => $region->id, 'suspended_at' => now()]);

        $this->assertSame(2, $region->structures()->count());
    }

    public function test_an_order_keeps_its_suspended_product(): void
    {
        $item = OrderItem::factory()->create();
        $item->purchasable->forceFill(['suspended_at' => now()])->save();

        $fresh = OrderItem::query()->with('purchasable')->find($item->id);

        $this->assertNotNull($fresh->purchasable, 'eager load');
        $this->assertNotNull(OrderItem::find($item->id)->purchasable, 'lazy load');
    }

    public function test_a_hidden_product_cannot_be_added_to_the_cart(): void
    {
        $structure = Structure::factory()->create(['suspended_at' => now()]);

        $this->expectException(NotFoundHttpException::class);

        app(CartManager::class)->addItem('structure', $structure->id, [], false);
    }
}
