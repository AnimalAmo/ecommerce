<?php

namespace Tests\Feature\Admin\Catalog;

use App\Enums\ProductType;
use App\Livewire\Admin\Catalog\StructureCreate;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class StructureCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('public');

        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);

        $this->actingAsSuperadmin();
    }

    /** Partner destinatario della scheda: attivo, con Stripe collegato. */
    private function payablePartner(): User
    {
        $partner = $this->actingAsPayablePartner();

        // actingAsPayablePartner autentica il partner: il pannello lo apre il superadmin.
        $this->actingAsSuperadmin();

        return $partner;
    }

    /** @return list<UploadedFile> */
    private function fourPhotos(): array
    {
        return [
            UploadedFile::fake()->image('copertina.jpg'),
            UploadedFile::fake()->image('camera.jpg'),
            UploadedFile::fake()->image('piscina.jpg'),
            UploadedFile::fake()->image('colazione.jpg'),
        ];
    }

    /**
     * Compila l'intera pagina con la fixture di StructurePublisherTest, così il
     * confronto con il publisher del wizard (Task 5) parte dagli stessi dati.
     *
     * Il partner arriva dalla query string, non da un parametro di mount:
     * `$partnerId` è #[Url] #[Locked] e il trait lo legge da lì.
     */
    private function filled(User $partner, string $family = 'structure'): Testable
    {
        return Livewire::withQueryParams(['partner' => $partner->id])
            ->test(StructureCreate::class, ['family' => $family])
            ->set('type', 'hotel')
            ->set('name.it', 'Hotel Bau Resort')
            ->set('name.en', 'Bau Resort Hotel')
            ->set('description.it', 'Hotel pet friendly sul lago.')
            ->set('description.en', 'Pet friendly hotel on the lake.')
            ->set('location.address', 'Via Roma 1')
            ->set('location.city', 'Brescia')
            ->set('location.province', 'BS')
            ->set('location.zip', '25100')
            ->set('location.license', 'SCIA 2026/14')
            ->set('rooms.rooms', [
                ['type' => 'doppia', 'count' => 3, 'price' => '80'],
                ['type' => 'singola', 'count' => 2, 'price' => '55.50'],
            ])
            ->set('rooms.checkinFrom', '14:00')
            ->set('rooms.checkinTo', '20:00')
            ->set('rooms.checkoutFrom', '08:00')
            ->set('rooms.checkoutTo', '10:00')
            ->set('cancellationWhen', '7')
            ->set('services.services', ['wifi', 'sauna', 'tv'])
            ->set('services.additional', ['colazione', 'pranzo'])
            ->set('services.mealTimes', [
                'colazione' => ['from' => '07:30', 'to' => '10:00'],
                'pranzo' => ['from' => '12:30', 'to' => '14:30'],
                'cena' => ['from' => '', 'to' => ''],
            ])
            ->set('animalServices', ['pet_sitting', 'omaggio'])
            ->set('photos', $this->fourPhotos());
    }

    public function test_the_page_opens_for_the_structure_family(): void
    {
        $partner = $this->payablePartner();

        $this->get(route('admin.catalog.create', ['family' => 'structure', 'partner' => $partner->id]))
            ->assertOk();
    }

    public function test_it_publishes_the_structure_for_the_chosen_partner(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)->call('save')->assertHasNoErrors();

        $draft = StructureDraft::query()->sole();
        $structure = Structure::withHidden()->sole();

        $this->assertSame($partner->id, $draft->user_id);
        $this->assertSame('struttura', $draft->service_category);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->status);
        $this->assertSame(11, $draft->current_step);
        $this->assertNull($draft->publish_requested_at);

        $this->assertSame($partner->id, $structure->user_id);
        $this->assertSame($draft->id, $structure->structure_draft_id);
        $this->assertSame(ProductType::Structure, $structure->type);
        $this->assertSame('Brescia (BS), Italia', $structure->location);
        $this->assertSame(5550, $structure->price_cents);

        // Le quattro foto sono su disco e la prima è la copertina.
        $this->assertCount(4, $draft->photos);
        foreach ($draft->photos as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringStartsWith('structure-photos/', $path);
        }
        $this->assertSame($draft->photos[0], $structure->img);
    }

    public function test_the_service_family_saves_the_servizi_category(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner, 'service')->call('save')->assertHasNoErrors();

        $this->assertSame('servizi', StructureDraft::query()->sole()->service_category);
        // Come il wizard: 'servizi' va a catalogo come Structure, prezzo a notte.
        $this->assertSame(ProductType::Structure, Structure::withHidden()->sole()->type);
    }

    /** Il gruppo giusto è `smartbox_consent`: con un nome inventato ogni test di questa classe morirebbe su InvalidArgumentException. */
    public function test_the_smartbox_membership_options_come_from_the_right_group(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('smartboxConsent', 'si')
            ->set('smartboxTypes', ['pernottamento', 'benessere'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['pernottamento', 'benessere'], StructureDraft::query()->sole()->smartbox_types);
    }
}
