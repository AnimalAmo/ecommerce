<?php

namespace Tests\Feature\Partner;

use App\Enums\OrderStatus;
use App\Livewire\Partner\Smartbox\SmartboxPhotos;
use App\Livewire\Partner\Structure\HotelPhotos;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Event\Event;
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
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Le foto della galleria («Vedere tutte le foto», 29/09/2026) nei flussi che
 * PartnerActivityPhotosTest non cammina: gli step foto di struttura e
 * smartbox (stesso trait HandlesPhotoUploads, altre tabelle a catalogo) e il
 * cambio di ramo F5, dove la riga della famiglia vecchia è quella che punta
 * la foto tolta.
 *
 * La regola è la stessa ovunque: la X non cancella un file che una riga a
 * catalogo mostra ancora (anche solo nella galleria); lo pota la
 * ripubblicazione, dopo il commit, se nessuna riga lo punta più.
 */
class GalleryPhotoLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    /** @param  list<string>  $photos */
    private function putOnDisk(array $photos): void
    {
        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'jpeg-finto');
        }
    }

    /** @return array{0: StructureDraft, 1: Structure} */
    private function publishedHotel(User $partner, array $photos): array
    {
        $this->putOnDisk($photos);

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
        $this->assertSame($photos, $structure->gallery);

        session(['structure_draft_id' => $draft->id]);

        return [$draft, $structure];
    }

    // ── Step foto della struttura (HotelPhotos) ───────────────────────────────

    public function test_struttura_la_x_su_una_foto_della_galleria_la_tiene_finche_non_si_ripubblica(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['structure-photos/h1.jpg', 'structure-photos/h2.jpg', 'structure-photos/h3.jpg', 'structure-photos/h4.jpg'];
        [$draft, $structure] = $this->publishedHotel($partner, $photos);

        Livewire::test(HotelPhotos::class)
            ->call('removeSaved', 2)
            ->assertSet('saved', ['structure-photos/h1.jpg', 'structure-photos/h2.jpg', 'structure-photos/h4.jpg']);

        $this->assertNotContains('structure-photos/h3.jpg', $draft->fresh()->photos);
        $this->assertContains('structure-photos/h3.jpg', $structure->fresh()->gallery, 'Senza ripubblicazione la scheda online non cambia.');
        Storage::disk('public')->assertExists('structure-photos/h3.jpg');

        // Il partner rimette una foto e completa: ora la foto tolta esce dalla galleria e dal disco.
        Livewire::test(HotelPhotos::class)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();

        app(DraftCompleter::class)->complete($draft->fresh(), $draft->fresh()->finalStep());

        $gallery = $structure->fresh()->gallery;
        $this->assertCount(4, $gallery);
        $this->assertSame(['structure-photos/h1.jpg', 'structure-photos/h2.jpg', 'structure-photos/h4.jpg'], array_slice($gallery, 0, 3));
        Storage::disk('public')->assertExists($gallery[3]);
        Storage::disk('public')->assertMissing('structure-photos/h3.jpg');
        Storage::disk('public')->assertExists('structure-photos/h1.jpg');
    }

    // ── Step foto della smartbox (SmartboxPhotos) ─────────────────────────────

    public function test_smartbox_la_x_su_una_foto_della_galleria_la_tiene_finche_non_si_ripubblica(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['smartbox-photos/s1.jpg', 'smartbox-photos/s2.jpg', 'smartbox-photos/s3.jpg', 'smartbox-photos/s4.jpg'];
        $this->putOnDisk($photos);

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'benessere',
            'name' => ['it' => 'Weekend Zen col tuo cane'],
            'description' => ['it' => 'Relax e coccole per entrambi.'],
            'duration_days' => 3,
            'cancellation_when' => '15',
            'price' => '215',
            'photos' => $photos,
        ]);
        $package = app(DraftPublisher::class)->publish($draft);
        $this->assertInstanceOf(SmartboxPackage::class, $package, 'La fixture non è arrivata a catalogo.');
        $this->assertSame($photos, $package->gallery);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxPhotos::class)->call('removeSaved', 1);

        $this->assertNotContains('smartbox-photos/s2.jpg', $draft->fresh()->photos);
        $this->assertContains('smartbox-photos/s2.jpg', $package->fresh()->gallery);
        Storage::disk('public')->assertExists('smartbox-photos/s2.jpg');

        Livewire::test(SmartboxPhotos::class)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();

        app(DraftCompleter::class)->complete($draft->fresh(), $draft->fresh()->finalStep());

        $gallery = $package->fresh()->gallery;
        $this->assertCount(4, $gallery);
        $this->assertNotContains('smartbox-photos/s2.jpg', $gallery);
        Storage::disk('public')->assertMissing('smartbox-photos/s2.jpg');
        Storage::disk('public')->assertExists('smartbox-photos/s1.jpg');
    }

    // ── Cambio di ramo (F5): la foto tolta la punta la riga dell'altra famiglia ──

    /** Evento pubblicato con quattro foto, riaperto e portato a hotel. */
    private function publishedEventSwitchedToHotel(User $partner, array $photos): array
    {
        $this->putOnDisk($photos);

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sagra del cane'],
            'description' => ['it' => 'Una sagra a sei zampe.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-12-01',
            'cancellation_when' => '1',
            'photos' => $photos,
        ]);
        $event = app(DraftPublisher::class)->publish($draft);
        $this->assertInstanceOf(Event::class, $event, 'La fixture non è arrivata a catalogo.');
        $this->assertSame($photos, $event->gallery);

        session(['structure_draft_id' => $draft->id]);
        Livewire::test(StructureType::class)->set('type', 'hotel')->call('next')->assertHasNoErrors();
        $draft->refresh()->update(['rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']]]);
        $this->assertSame($photos, $draft->fresh()->photos, 'Il cambio di ramo non tocca le foto.');

        return [$draft, $event];
    }

    /** X su una foto, una nuova per tornare a quattro, completamento. */
    private function replacePhotoAndComplete(StructureDraft $draft, int $index): void
    {
        Livewire::test(HotelPhotos::class)->call('removeSaved', $index);

        Livewire::test(HotelPhotos::class)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();

        app(DraftCompleter::class)->complete($draft->fresh(), $draft->fresh()->finalStep());
    }

    public function test_cambio_di_ramo_la_x_su_una_foto_che_solo_la_riga_vecchia_mostra_non_la_cancella_subito(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['structure-photos/e1.jpg', 'structure-photos/e2.jpg', 'structure-photos/e3.jpg', 'structure-photos/e4.jpg'];
        [$draft, $event] = $this->publishedEventSwitchedToHotel($partner, $photos);

        Livewire::test(HotelPhotos::class)->call('removeSaved', 2);

        // Nessuna struttura ancora: è l'evento, famiglia vecchia, che la mostra in galleria.
        $this->assertFalse(Structure::withHidden()->where('structure_draft_id', $draft->id)->exists());
        $this->assertContains('structure-photos/e3.jpg', $event->fresh()->gallery);
        Storage::disk('public')->assertExists('structure-photos/e3.jpg');
    }

    public function test_cambio_di_ramo_ripubblicato_pota_la_foto_tolta_con_la_riga_vecchia(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['structure-photos/e1.jpg', 'structure-photos/e2.jpg', 'structure-photos/e3.jpg', 'structure-photos/e4.jpg'];
        [$draft] = $this->publishedEventSwitchedToHotel($partner, $photos);

        $this->replacePhotoAndComplete($draft, 2);

        $this->assertFalse(Event::withHidden()->where('structure_draft_id', $draft->id)->exists(), 'La riga dell\'evento esce dal catalogo.');
        $structure = Structure::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertCount(4, $structure->gallery);
        $this->assertNotContains('structure-photos/e3.jpg', $structure->gallery);
        $this->assertSame('structure-photos/e1.jpg', $structure->hero_img);
        Storage::disk('public')->assertMissing('structure-photos/e3.jpg');
        foreach (['structure-photos/e1.jpg', 'structure-photos/e2.jpg', 'structure-photos/e4.jpg'] as $kept) {
            Storage::disk('public')->assertExists($kept);
        }
    }

    /**
     * La riga vecchia con una prenotazione futura non si cancella, si ritira
     * (DraftPublisher::removeRows): resta leggibile a chi deve onorarla, con
     * la sua galleria. Il file che quella galleria punta resta su disco.
     */
    public function test_cambio_di_ramo_la_riga_vecchia_ritirata_protegge_ancora_le_sue_foto(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['structure-photos/e1.jpg', 'structure-photos/e2.jpg', 'structure-photos/e3.jpg', 'structure-photos/e4.jpg'];
        [$draft, $event] = $this->publishedEventSwitchedToHotel($partner, $photos);

        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        OrderItem::factory()->for($order)->create([
            'purchasable_type' => 'event',
            'purchasable_id' => $event->id,
            'booked_from' => now()->addDays(10),
            'booked_until' => now()->addDays(10),
        ]);

        $this->replacePhotoAndComplete($draft, 2);

        $old = Event::withHidden()->find($event->id);
        $this->assertNotNull($old?->withheld_at, 'Con una prenotazione futura la riga vecchia si ritira.');
        $this->assertContains('structure-photos/e3.jpg', $old->gallery);
        Storage::disk('public')->assertExists('structure-photos/e3.jpg');

        $structure = Structure::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertNotContains('structure-photos/e3.jpg', $structure->gallery);
    }
}
