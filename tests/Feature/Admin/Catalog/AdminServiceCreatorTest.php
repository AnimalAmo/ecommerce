<?php

namespace Tests\Feature\Admin\Catalog;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Exceptions\PartnerServiceException;
use App\Mail\CatalogModerationMail;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\Catalog\AdminServiceCreator;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La scheda creata dal pannello deve essere indistinguibile da quella che il
 * partner avrebbe pubblicato col suo wizard: stessa bozza, stesso publisher,
 * stessa riga. Se divergono, il partner riapre la scheda dal suo percorso e
 * la ripubblicazione cambia i dati sotto ai piedi del cliente.
 *
 * La famiglia di prova è la struttura con i campi minimi pubblicabili, gli
 * stessi di StructurePublisherTest: una famiglia finta non proverebbe niente,
 * perché è proprio la mappatura draft → catalogo ciò che si sta verificando.
 */
class AdminServiceCreatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('public');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    private function creator(): AdminServiceCreator
    {
        return app(AdminServiceCreator::class);
    }

    /** Campi della bozza senza user_id, service_category, status, step e foto: li scrive il service. */
    private function attributes(array $overrides = []): array
    {
        return array_merge([
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort', 'en' => 'Bau Resort Hotel'],
            'description' => ['it' => 'Hotel pet friendly sul lago.', 'en' => 'Pet friendly hotel on the lake.'],
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'province' => 'BS',
            'zip' => '25100',
            'rooms' => [
                ['type' => 'doppia', 'count' => 3, 'price' => '80'],
                ['type' => 'singola', 'count' => 2, 'price' => '55.50'],
            ],
            'checkin_from' => '14:00',
            'checkin_to' => '20:00',
            'checkout_from' => '08:00',
            'checkout_to' => '10:00',
            'cancellation_when' => '7',
            'services' => ['wifi', 'sauna', 'tv'],
            'additional_services' => ['colazione', 'pranzo'],
            'meal_times' => [
                'colazione' => ['from' => '07:30', 'to' => '10:00'],
                'pranzo' => ['from' => '12:30', 'to' => '14:30'],
                'cena' => ['from' => '', 'to' => ''],
            ],
            'animal_services' => ['pet_sitting', 'omaggio'],
        ], $overrides);
    }

    /** Foto già sul disco public, come le lascia HandlesPhotoUploads::collectPhotos(). */
    private function storedPhotos(int $count = 4): array
    {
        return array_map(
            fn (int $i): string => UploadedFile::fake()->image("foto-{$i}.jpg")->store('structure-photos', 'public'),
            range(1, $count),
        );
    }

    private function payablePartner(): User
    {
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create(['is_active' => true]);
        $partner->assignRole('partner');
        PartnerProfile::factory()->connected()->for($partner)->create();

        return $partner->fresh();
    }

    public function test_the_listing_is_the_same_one_the_wizard_would_have_published(): void
    {
        $photos = $this->storedPhotos();

        // Riferimento: la strada del partner, bozza a mano + publisher.
        $reference = StructureDraft::create([
            ...$this->attributes(),
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'service_category' => 'struttura',
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'photos' => $photos,
        ]);
        $expected = app(DraftPublisher::class)->publish($reference);

        $partner = $this->payablePartner();
        $draft = null;
        $outcome = $this->creator()->create($partner, 'struttura', $this->attributes(), $photos, $draft);

        $this->assertSame(DraftCompletion::Published, $outcome);
        $this->assertSame($partner->id, $draft->user_id);
        $this->assertSame('struttura', $draft->service_category);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->status);
        $this->assertSame(11, $draft->current_step);
        $this->assertNull($draft->publish_requested_at);

        $actual = $this->creator()->publishedRow($draft);
        $this->assertInstanceOf(Structure::class, $actual);
        $this->assertSame('hotel-bau-resort-'.$draft->id, $actual->slug);

        // Tutto il resto identico. Fuori: le colonne che dipendono dalla riga
        // (id, proprietario, slug col numero della bozza, posizione, date).
        $ignored = ['id', 'structure_draft_id', 'user_id', 'slug', 'position', 'created_at', 'updated_at'];
        $this->assertEquals(
            Arr::except($expected->fresh()->getAttributes(), $ignored),
            Arr::except($actual->getAttributes(), $ignored),
        );
    }

    public function test_an_offline_partner_publishes_without_stripe(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $draft = null;

        $outcome = $this->creator()->create($partner, 'struttura', $this->attributes(), $this->storedPhotos(), $draft);

        $this->assertSame(DraftCompletion::Published, $outcome);
        $this->assertNotNull($this->creator()->publishedRow($draft));
    }

    public function test_an_online_partner_without_stripe_leaves_the_draft_waiting_and_keeps_its_photos(): void
    {
        Role::findOrCreate('partner', 'web');
        $partner = User::factory()->create(['is_active' => true]);
        $partner->assignRole('partner');
        PartnerProfile::factory()->for($partner)->create();

        $photos = $this->storedPhotos();
        $draft = null;

        $outcome = $this->creator()->create($partner->fresh(), 'struttura', $this->attributes(), $photos, $draft);

        $this->assertSame(DraftCompletion::AwaitingPayout, $outcome);
        $this->assertNotNull($draft->publish_requested_at);
        $this->assertSame(StructureDraft::STATUS_DRAFT, $draft->status);
        $this->assertSame($photos, $draft->photos);
        $this->assertSame(0, Structure::withHidden()->count());

        // Niente file orfani: le foto non sono da buttare, sono della bozza
        // che PublishAwaitingDrafts pubblicherà appena Stripe è collegato.
        foreach ($photos as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_a_draft_without_rooms_is_refused_and_takes_its_photos_with_it(): void
    {
        $photos = $this->storedPhotos();
        $draft = null;

        try {
            $this->creator()->create($this->payablePartner(), 'struttura', $this->attributes(['rooms' => []]), $photos, $draft);
            $this->fail('la bozza senza stanze doveva essere rifiutata');
        } catch (DraftNotPublishableException) {
            // atteso
        }

        $this->assertNull($draft);
        $this->assertSame(0, StructureDraft::query()->count(), 'nessuna bozza orfana');
        $this->assertSame(0, Structure::withHidden()->count());

        foreach ($photos as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    /** @return array<string, array{0: string}> */
    public static function ineligiblePartners(): array
    {
        return [
            'cliente senza ruolo partner' => ['no_role'],
            'partner disattivato' => ['inactive'],
            'partner anonimizzato' => ['anonymized'],
            'partner senza profilo aziendale' => ['no_profile'],
        ];
    }

    #[DataProvider('ineligiblePartners')]
    public function test_an_ineligible_partner_is_refused_before_anything_is_written(string $case): void
    {
        Role::findOrCreate('partner', 'web');

        $user = User::factory()->create([
            'is_active' => $case !== 'inactive',
            'anonymized_at' => $case === 'anonymized' ? now() : null,
        ]);

        if ($case !== 'no_role') {
            $user->assignRole('partner');
        }

        if ($case !== 'no_profile') {
            PartnerProfile::factory()->connected()->for($user)->create();
        }

        $photos = $this->storedPhotos();
        $draft = null;

        try {
            $this->creator()->create($user->fresh(), 'struttura', $this->attributes(), $photos, $draft);
            $this->fail("il caso «{$case}» doveva essere rifiutato");
        } catch (PartnerServiceException $e) {
            $this->assertStringContainsString('non può ricevere schede', $e->getMessage());
            $this->assertSame($user->id, $e->partnerId);
        }

        $this->assertSame(0, StructureDraft::query()->count());
        $this->assertSame(0, Structure::withHidden()->count());

        foreach ($photos as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_with_moderation_on_the_listing_is_born_approved_and_visible(): void
    {
        config(['admin.moderation' => true]);
        Mail::fake();

        $draft = null;
        $this->creator()->create($this->payablePartner(), 'struttura', $this->attributes(), $this->storedPhotos(), $draft);

        $row = $this->creator()->publishedRow($draft);

        // Questo è ciò che approveWithoutNotice() fa davvero, e che smetterebbe
        // di valere se lo si cancellasse: senza, il publisher lascerebbe
        // approval_status a 'pending' e approval_requested_at valorizzato, e la
        // riga resterebbe fuori dallo scope di visibilità.
        $this->assertSame(Structure::APPROVAL_APPROVED, $row->approval_status);
        $this->assertNotNull($row->approved_at);
        $this->assertNull($row->approval_requested_at);
        $this->assertTrue(Structure::query()->whereKey($row->id)->exists(), 'visibile sul sito');

        // Unica asserzione sulla mail, e tenuta come regressione dichiarata:
        // oggi CatalogModerationMail parte solo da CatalogAdmin::approve() e
        // ::requestChanges(), mai dai publisher, quindi non passa per questa
        // strada. Se un domani il publisher notificasse, questo test lo prende.
        Mail::assertNotSent(CatalogModerationMail::class);
    }

    public function test_the_eligible_partners_are_the_ones_create_accepts(): void
    {
        Role::findOrCreate('partner', 'web');

        $good = $this->payablePartner();
        PartnerProfile::query()->where('user_id', $good->id)->update(['business_name' => 'Cascina Bau']);

        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('partner');
        PartnerProfile::factory()->connected()->for($inactive)->create();

        $noProfile = User::factory()->create(['is_active' => true]);
        $noProfile->assignRole('partner');

        $client = User::factory()->create(['is_active' => true]);

        $options = $this->creator()->eligiblePartners();

        // id => ragione sociale: è una mappa di stringhe, non di model.
        $this->assertSame([$good->id], $options->keys()->all());
        $this->assertSame('Cascina Bau', $options[$good->id]);
    }
}
