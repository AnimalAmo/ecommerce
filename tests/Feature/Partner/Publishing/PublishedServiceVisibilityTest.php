<?php

namespace Tests\Feature\Partner\Publishing;

use App\Livewire\Partner\Activity\ActivityCancellation;
use App\Livewire\Partner\MyServices\DeleteServiceModal;
use App\Livewire\Partner\Smartbox\SmartboxPrice;
use App\Livewire\Partner\Structure\HotelPayment;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * e2e: completare il wizard partner (ultimo step Livewire) pubblica il servizio
 * e lo rende visibile sulle pagine B2C (listing + detail).
 */
class PublishedServiceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_completing_the_hotel_wizard_publishes_the_structure_on_the_b2c(): void
    {
        // Il wizard vive dietro ['auth','partner'] e pubblica solo se il
        // partner può essere pagato: la bozza ha sempre un proprietario.
        $partner = $this->actingAsPayablePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 10,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice'],
            'description' => ['it' => 'Sul lago, con spiaggia per cani.'],
            'city' => 'Brescia',
            'province' => 'BS',
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
            'cancellation_when' => '7',
            'photos' => ['structure-photos/zampa.jpg'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelPayment::class)->call('skip');

        $structure = Structure::firstWhere('structure_draft_id', $draft->id);
        $this->assertNotNull($structure);

        // Il listing regione mostra il servizio partner (oggi elenca tutte le strutture, fedeltà mock).
        $this->get('/animal-holiday/lombardia')
            ->assertOk()
            ->assertSee('Hotel Zampa Felice')
            ->assertSee('Brescia (BS), Italia')
            ->assertSee('Nuovo');

        // Il detail per slug risponde 200 con i dati del draft (stato "Nuovo", niente mappa).
        $this->get('/animal-holiday/lombardia/'.$structure->slug)
            ->assertOk()
            ->assertSee('Hotel Zampa Felice')
            ->assertSee('Sul lago, con spiaggia per cani.')
            ->assertSee('Cancellazione gratuita')
            ->assertSee('Nuovo')
            ->assertDontSee('Dove siamo');
    }

    public function test_completing_the_activity_wizard_publishes_the_event_on_the_b2c(): void
    {
        // Il wizard vive dietro ['auth','partner'] e pubblica solo se il
        // partner può essere pagato: la bozza ha sempre un proprietario.
        $partner = $this->actingAsPayablePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 9,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Sfilata a 4 zampe'],
            'description' => ['it' => 'La sfilata pet friendly.'],
            'meeting_point' => ['it' => 'Parco Sempione'],
            'city' => 'Milano',
            'province' => 'MI',
            'date_start' => now()->addDays(10)->toDateString(),
            'date_end' => now()->addDays(10)->toDateString(),
            'time_start' => '15:00',
            'time_end' => '18:00',
            'price_type' => 'gratuito',
            'photos' => ['structure-photos/sfilata.jpg'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityCancellation::class)
            ->set('when', '7')
            ->call('next');

        $event = Event::firstWhere('structure_draft_id', $draft->id);
        $this->assertNotNull($event);

        // La griglia eventi pagina 12 card e il nuovo evento va in coda (position max+1):
        // il detail per slug basta a coprire la visibilità B2C.
        // Il nome venue compare solo nella card mappa (nascosta senza map_img):
        // il detail mostra l'indirizzo del venue derivato da città/provincia.
        $this->get('/eventi/'.$event->slug)
            ->assertOk()
            ->assertSee('Sfilata a 4 zampe')
            ->assertSee('Milano (MI)');
    }

    public function test_deleting_a_service_from_my_services_unpublishes_the_catalog_row(): void
    {
        $partner = $this->actingAsPayablePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Da Cancellare'],
            'description' => ['it' => 'Presto rimosso.'],
            'city' => 'Brescia',
            'province' => 'BS',
            'rooms' => [['type' => 'doppia', 'count' => 1, 'price' => '60']],
            'photos' => ['structure-photos/x.jpg'],
        ]);
        app(DraftPublisher::class)->publish($draft);
        $this->assertNotNull(Structure::firstWhere('structure_draft_id', $draft->id));

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $draft->id)
            ->call('delete');

        // Senza unpublish la riga resterebbe live orfana (FK nullOnDelete).
        $this->assertNull(Structure::firstWhere('structure_draft_id', $draft->id));
        $this->assertNull(StructureDraft::find($draft->id));
    }

    public function test_completing_the_smartbox_wizard_publishes_the_package_on_the_b2c(): void
    {
        // Il wizard vive dietro ['auth','partner'] e pubblica solo se il
        // partner può essere pagato: la bozza ha sempre un proprietario.
        $partner = $this->actingAsPayablePartner();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 11,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Fuga romantica pet friendly'],
            'description' => ['it' => 'Un weekend per tre.'],
            'detailed_description' => ['it' => 'Due notti con colazione.'],
            'duration_days' => 2,
            'cancellation_when' => '15',
            'photos' => ['smartbox-photos/fuga.jpg'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxPrice::class)
            ->set('price', '189,50')
            ->call('save');

        $package = SmartboxPackage::firstWhere('structure_draft_id', $draft->id);
        $this->assertNotNull($package);
        $this->assertSame(18950, $package->price_cents);

        // La griglia smartbox pagina 12 box: il nuovo pacchetto va in coda (position max+1),
        // quindi verifichiamo il detail per slug, che basta a coprire la visibilità B2C.
        $this->get('/smartbox/'.$package->slug)
            ->assertOk()
            ->assertSee('Fuga romantica pet friendly')
            ->assertSee('Due notti con colazione.')
            ->assertSee("189,50\u{A0}€");
    }
}
