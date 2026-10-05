<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\CatalogPhotos;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftCompleter;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Foto di una scheda pubblicata dal pannello (richiesta della cliente,
 * 01/10/2026): CatalogPhotos + CatalogPhotoEditor. Riga a catalogo e bozza
 * cambiano insieme, così una ripubblicazione del partner non pota le foto
 * messe dall'admin.
 */
class CatalogPhotosTest extends TestCase
{
    use RefreshDatabase;

    private const PHOTOS = ['structure-photos/h1.jpg', 'structure-photos/h2.jpg', 'structure-photos/h3.jpg', 'structure-photos/h4.jpg', 'structure-photos/h5.jpg'];

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('local');
        Storage::fake('public');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    /** @return array{0: StructureDraft, 1: Structure} */
    private function publishedHotel(array $photos = self::PHOTOS): array
    {
        $partner = $this->actingAsPayablePartner();

        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'jpeg-finto');
        }

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort'],
            'description' => ['it' => 'Hotel pet friendly sul lago.'],
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'province' => 'BS',
            'zip' => '25100',
            'rooms' => [['type' => 'doppia', 'count' => 3, 'price' => '80']],
            'cancellation_when' => '7',
            'photos' => $photos,
        ]);

        $structure = app(DraftPublisher::class)->publish($draft);
        $this->assertInstanceOf(Structure::class, $structure, 'La fixture non è arrivata a catalogo.');

        return [$draft, $structure];
    }

    private function editor(string $type, int $id): Testable
    {
        $this->actingAsSuperadmin();

        return Livewire::test(CatalogPhotos::class, ['type' => $type, 'itemId' => $id]);
    }

    public function test_the_photos_are_listed_in_the_site_order_with_the_cover_first(): void
    {
        [, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->assertSet('order', array_map(fn (string $path): string => 'saved:'.$path, self::PHOTOS))
            ->assertSee(__('admin-catalog.create.cover'))
            ->assertDontSee(__('admin-catalog.show.photos.partner_changes'));

        $this->get(route('admin.catalog.show', ['type' => 'structure', 'id' => $structure->id]))
            ->assertOk()
            ->assertSee(__('admin-catalog.show.photos.heading'));
    }

    public function test_add_remove_and_new_cover_go_to_the_row_and_the_draft(): void
    {
        [$draft, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->set('uploads', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
            ->assertCount('order', 7)
            ->call('remove', 1)            // via h2
            ->call('makeCover', 2)         // h4 in copertina
            ->call('move', 1, 1)           // h1 dopo h3
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('catalog-photos-saved');

        $structure->refresh();
        $gallery = $structure->gallery;

        $this->assertCount(6, $gallery);
        $this->assertSame(['structure-photos/h4.jpg', 'structure-photos/h3.jpg', 'structure-photos/h1.jpg', 'structure-photos/h5.jpg'], array_slice($gallery, 0, 4));
        $this->assertSame('structure-photos/h4.jpg', $structure->img);
        $this->assertSame('structure-photos/h4.jpg', $structure->hero_img);
        $this->assertSame($gallery, $draft->fresh()->photos, 'La bozza deve avere le stesse foto, o la ripubblicazione le poterebbe.');

        Storage::disk('public')->assertExists($gallery[4]);
        Storage::disk('public')->assertExists($gallery[5]);
        $this->assertStringStartsWith('structure-photos/', $gallery[4]);
        Storage::disk('public')->assertMissing('structure-photos/h2.jpg');
    }

    public function test_a_partner_republishing_afterwards_keeps_the_admin_photos(): void
    {
        [$draft, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->call('makeCover', 4)
            ->call('save')
            ->assertHasNoErrors();

        $expected = $structure->fresh()->gallery;

        app(DraftCompleter::class)->complete($draft->fresh(), $draft->fresh()->finalStep());

        $this->assertSame($expected, $structure->fresh()->gallery);
        $this->assertSame('structure-photos/h5.jpg', $structure->fresh()->hero_img);

        foreach (self::PHOTOS as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_fewer_than_four_photos_change_nothing(): void
    {
        [$draft, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->call('remove', 0)
            ->call('remove', 0)
            ->call('remove', 0)
            ->set('uploads', [UploadedFile::fake()->image('a.jpg')])
            ->assertCount('order', 3)
            ->call('save')
            ->assertHasErrors(['photos']);

        $this->assertSame(self::PHOTOS, $structure->fresh()->gallery);
        $this->assertSame(self::PHOTOS, $draft->fresh()->photos);
        $this->assertCount(5, Storage::disk('public')->files('structure-photos'), 'Nessun file nuovo, nessun file tolto.');
    }

    public function test_a_forged_path_of_another_listing_is_ignored(): void
    {
        [, $structure] = $this->publishedHotel();
        Storage::disk('public')->put('structure-photos/altrui.jpg', 'jpeg-finto');

        $this->editor('structure', $structure->id)
            ->set('order', ['saved:structure-photos/altrui.jpg', 'saved:structure-photos/h1.jpg', 'saved:structure-photos/h2.jpg', 'saved:structure-photos/h3.jpg', 'saved:structure-photos/h4.jpg'])
            ->call('save')
            ->assertHasNoErrors();

        $gallery = $structure->fresh()->gallery;
        $this->assertNotContains('structure-photos/altrui.jpg', $gallery);
        $this->assertSame('structure-photos/h1.jpg', $structure->fresh()->hero_img);
        Storage::disk('public')->assertExists('structure-photos/altrui.jpg');
    }

    public function test_a_duplicated_path_does_not_count_twice_for_the_minimum(): void
    {
        [, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->set('order', array_fill(0, 4, 'saved:structure-photos/h1.jpg'))
            ->call('save')
            ->assertHasErrors(['photos']);

        $this->assertSame(self::PHOTOS, $structure->fresh()->gallery);
    }

    public function test_a_removed_photo_still_in_an_order_stays_on_disk(): void
    {
        [, $structure] = $this->publishedHotel();

        OrderItem::factory()->for(Order::factory()->create())->create([
            'photo_url' => Storage::disk('public')->url('structure-photos/h1.jpg'),
        ]);

        $this->editor('structure', $structure->id)
            ->call('remove', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotContains('structure-photos/h1.jpg', $structure->fresh()->gallery);
        Storage::disk('public')->assertExists('structure-photos/h1.jpg');
    }

    public function test_only_images_under_8_mb_are_accepted(): void
    {
        [, $structure] = $this->publishedHotel();

        $this->editor('structure', $structure->id)
            ->set('uploads', [UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')])
            ->assertHasErrors(['uploads.0' => 'image'])
            ->assertCount('order', 5)
            ->set('uploads', [UploadedFile::fake()->image('enorme.jpg')->size(9000)])
            ->assertHasErrors(['uploads.0' => 'max'])
            ->assertCount('order', 5);
    }

    public function test_an_unpublished_partner_change_is_flagged_and_replaced_on_save(): void
    {
        [$draft, $structure] = $this->publishedHotel();
        Storage::disk('public')->put('structure-photos/del-partner.jpg', 'jpeg-finto');
        $draft->update(['photos' => [...self::PHOTOS, 'structure-photos/del-partner.jpg']]);

        $this->editor('structure', $structure->id)
            ->assertSee(__('admin-catalog.show.photos.partner_changes'))
            ->call('move', 0, 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotContains('structure-photos/del-partner.jpg', $draft->fresh()->photos);
        Storage::disk('public')->assertMissing('structure-photos/del-partner.jpg');
    }

    public function test_a_smartbox_without_draft_writes_the_row_and_stores_in_its_folder(): void
    {
        $smartbox = SmartboxPackage::factory()->create(['structure_draft_id' => null]);

        $this->editor('smartbox_package', $smartbox->id)
            ->set('uploads', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
            ])
            ->assertCount('order', 4)
            ->call('save')
            ->assertHasNoErrors();

        $smartbox->refresh();
        $this->assertCount(4, $smartbox->gallery);
        $this->assertSame('smartbox-dettaglio-hero', $smartbox->gallery[0], 'La copertina del template resta prima.');
        $this->assertStringStartsWith('smartbox-photos/', $smartbox->gallery[1]);
        Storage::disk('public')->assertExists($smartbox->gallery[1]);
    }

    public function test_only_a_superadmin_reaches_the_editor(): void
    {
        [, $structure] = $this->publishedHotel();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.catalog.show', ['type' => 'structure', 'id' => $structure->id]))
            ->assertForbidden();
    }
}
