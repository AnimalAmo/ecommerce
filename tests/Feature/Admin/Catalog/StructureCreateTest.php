<?php

namespace Tests\Feature\Admin\Catalog;

use App\Enums\ProductType;
use App\Livewire\Admin\Catalog\StructureCreate;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Mail\CatalogModerationMail;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
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

    public function test_the_page_shows_one_card_per_wizard_step(): void
    {
        $partner = $this->payablePartner();

        $this->get(route('admin.catalog.create', ['family' => 'structure', 'partner' => $partner->id]))
            ->assertOk()
            ->assertSeeInOrder([
                'Categoria e tipologia',
                'Nome della scheda',
                'Luogo',
                'Descrizione',
                'Camere',
                'Cancellazione',
                'Servizi della struttura',
                'Servizi per gli animali',
                'Smartbox',
                'Foto',
                'Coordinate bancarie',
            ])
            // Etichette delle opzioni: restano quelle del percorso partner.
            ->assertSee('Agriturismo')
            ->assertSee('Wi-fi gratuito')
            ->assertSee('Pet sitting')
            ->assertSee('Tutta la struttura')
            // Provincia dalla tabella, non da un elenco scritto a mano.
            ->assertSee('Brescia (BS)')
            // Riquadro laterale: modalità di pagamento E stato Stripe (spec
            // §5.3). Il testo è quello che il partial stampa davvero —
            // «Stripe: Collegato e pagabile» — non una stringa che vive solo
            // in una chiave lang che nessuno usa.
            ->assertSee(__('admin-catalog.create.stripe_status.payable'))
            // Nessuna chiave lang grezza a video: è l'unico modo di accorgersi
            // che un blocco `create.*` è stato dichiarato e mai definito.
            ->assertDontSee('admin-catalog.create.');
    }

    public function test_a_rejected_save_shows_the_error_and_jumps_to_the_language_that_has_it(): void
    {
        $partner = $this->payablePartner();

        Livewire::withQueryParams(['partner' => $partner->id])
            ->test(StructureCreate::class, ['family' => 'structure'])
            ->set('lang', 'it')
            ->set('name.it', 'Hotel Bau Resort')
            ->set('name.en', str_repeat('a', 111))
            ->call('save')
            ->assertHasErrors(['name.en' => 'max'])
            ->assertSet('lang', 'en')
            // La coda del messaggio, non il suo inizio: «Massimo 110
            // caratteri» compare anche in `structure.name_help`, che la pagina
            // stampa sempre — un assertSee su quel pezzo passerebbe pure
            // cancellando la regola.
            ->assertSee('il nome italiano e quello inglese vivono nello stesso campo del catalogo');
    }

    /**
     * I gruppi di checkbox falliscono su chiavi indicizzate
     * (`services.services.1`): se la vista non le mostra, il salvataggio si
     * blocca e il bottone sembra morto. È il difetto che la spec §5.3 chiede
     * di evitare.
     */
    public function test_an_option_group_shows_its_own_error(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('services.services', ['wifi', 'elicottero'])
            ->call('save')
            ->assertHasErrors('services.services.1')
            ->assertSee('Questa opzione non esiste più');
    }

    /**
     * Gli stessi dati della fixture di StructurePublisherTest, in formato
     * colonne della bozza: servono a costruire la bozza di riferimento con cui
     * confrontare quella creata dal pannello.
     *
     * @return array<string, mixed>
     */
    private function referenceAttributes(array $photos, string $category = 'struttura'): array
    {
        return [
            'service_category' => $category,
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort', 'en' => 'Bau Resort Hotel'],
            'description' => ['it' => 'Hotel pet friendly sul lago.', 'en' => 'Pet friendly hotel on the lake.'],
            'address' => 'Via Roma 1',
            'city' => 'Brescia',
            'province' => 'BS',
            'zip' => '25100',
            'license' => 'SCIA 2026/14',
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
            'additional_other' => [],
            'meal_times' => [
                'colazione' => ['from' => '07:30', 'to' => '10:00'],
                'pranzo' => ['from' => '12:30', 'to' => '14:30'],
                'cena' => ['from' => '', 'to' => ''],
            ],
            'rules' => [],
            'animal_services' => ['pet_sitting', 'omaggio'],
            'animal_services_other' => [],
            'smartbox_consent' => 'si',
            'smartbox_types' => [],
            'photos' => $photos,
        ];
    }

    /** Colonne che dipendono dall'id o dal proprietario, non dai dati. */
    private const IGNORED_COLUMNS = ['id', 'slug', 'user_id', 'structure_draft_id', 'position', 'created_at', 'updated_at'];

    /**
     * Il cuore del contratto: la riga prodotta dal pannello deve essere quella
     * che produrrebbe il percorso del partner con gli stessi dati. Se un giorno
     * l'admin scrivesse una colonna in modo suo, la scheda si comporterebbe
     * diversamente sul B2C e il partner, ripubblicandola, la rimetterebbe com'è
     * qui: è proprio questo che il confronto impedisce.
     */
    public function test_the_row_is_identical_to_the_one_the_partner_wizard_would_publish(): void
    {
        // Timestamp congelato: approved_at e created_at non devono divergere
        // per i millisecondi che passano fra le due pubblicazioni.
        $this->freezeTime();

        $partner = $this->payablePartner();

        $this->filled($partner)->call('save')->assertHasNoErrors();

        $fromPanel = Structure::withHidden()->sole();
        $draft = StructureDraft::query()->sole();

        // Stessi dati, stesse foto, altro partner: la bozza del wizard.
        $other = User::factory()->stripeConnected()->create();
        $reference = StructureDraft::create([
            ...$this->referenceAttributes($draft->photos),
            'user_id' => $other->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);

        $fromWizard = app(DraftPublisher::class)->publish($reference);

        // `->fresh()` su ENTRAMBI i lati e assertEquals, non assertSame: il
        // model che il publisher restituisce è quello in memoria, e porta solo
        // gli attributi che gli sono stati passati; la riga riletta dal
        // database porta anche rating, suspended_at, approval_*, e in un altro
        // ordine di chiavi. assertSame su array confronta chiavi E ordine, e
        // il test uscirebbe rosso per un insieme di colonne diverso, non per
        // una divergenza vera.
        $this->assertEquals(
            Arr::except($fromWizard->fresh()->getAttributes(), self::IGNORED_COLUMNS),
            Arr::except($fromPanel->getAttributes(), self::IGNORED_COLUMNS),
        );

        // Lo slug differisce solo per l'id della bozza.
        $this->assertSame('hotel-bau-resort-'.$draft->id, $fromPanel->slug);

        // E le amenity, che stanno su un pivot e non fra gli attributi.
        foreach (['hotel', 'animal'] as $group) {
            $this->assertSame(
                collect($fromWizard->amenityRows($group))->map(fn (array $row): array => Arr::only($row, ['label', 'included']))->all(),
                collect($fromPanel->amenityRows($group))->map(fn (array $row): array => Arr::only($row, ['label', 'included']))->all(),
            );
        }
    }

    /**
     * La famiglia `servizi` è un caso a sé del contratto: stesso componente,
     * `service_category` diversa. Senza questo confronto era coperta dal solo
     * test di categoria, cioè da nient'altro che la stringa salvata.
     */
    public function test_the_servizi_family_row_is_identical_to_the_wizard_one_too(): void
    {
        $this->freezeTime();

        $partner = $this->payablePartner();

        $this->filled($partner, 'service')->call('save')->assertHasNoErrors();

        $fromPanel = Structure::withHidden()->sole();
        $draft = StructureDraft::query()->sole();

        $other = User::factory()->stripeConnected()->create();
        $reference = StructureDraft::create([
            ...$this->referenceAttributes($draft->photos, 'servizi'),
            'user_id' => $other->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);

        $fromWizard = app(DraftPublisher::class)->publish($reference);

        // Stesso trattamento dell'altro confronto: fresh() su entrambi i lati e
        // assertEquals, così l'ordine delle colonne non può tingere di rosso un
        // test che non ha trovato nessuna divergenza.
        $this->assertEquals(
            Arr::except($fromWizard->fresh()->getAttributes(), self::IGNORED_COLUMNS),
            Arr::except($fromPanel->getAttributes(), self::IGNORED_COLUMNS),
        );
    }

    public function test_with_moderation_on_the_listing_is_born_approved_and_visible(): void
    {
        config(['admin.moderation' => true]);
        Mail::fake();

        $partner = $this->payablePartner();

        $this->filled($partner)->call('save')->assertHasNoErrors();

        $structure = Structure::withHidden()->sole();

        $this->assertSame(Structure::APPROVAL_APPROVED, $structure->approval_status);
        $this->assertNotNull($structure->approved_at);
        $this->assertNull($structure->approval_requested_at);
        // Visibile subito: lo scope di visibilità non la nasconde.
        $this->assertSame($structure->id, Structure::query()->sole()->id);

        // Una sola asserzione sulla mail, tenuta come regressione dichiarata
        // (oggi CatalogModerationMail parte solo da CatalogAdmin, mai dai
        // publisher). Quelle su assertNothingQueued/assertNotQueued sono state
        // tolte: passerebbero anche cancellando approveWithoutNotice().
        Mail::assertNotSent(CatalogModerationMail::class);
    }

    public function test_an_online_partner_without_stripe_leaves_the_draft_waiting_without_orphan_files(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();
        $this->actingAsSuperadmin();

        $this->filled($partner)->call('save')->assertHasNoErrors();

        $draft = StructureDraft::query()->sole();

        $this->assertSame(0, Structure::withHidden()->count());
        $this->assertNotNull($draft->publish_requested_at);
        $this->assertSame($partner->id, $draft->user_id);

        // Le foto restano appese alla bozza, che P4 pubblicherà appena il
        // partner collega Stripe: nessun file scollegato sul disco.
        // Confronto fra INSIEMI: Storage::files() non garantisce l'ordine e i
        // nomi generati da store() sono casuali.
        $this->assertCount(4, $draft->photos);
        $this->assertEqualsCanonicalizing(
            $draft->photos,
            Storage::disk('public')->files('structure-photos'),
            'Sul disco ci sono file che la bozza non cita, o viceversa.',
        );
    }

    public function test_a_province_that_does_not_exist_is_refused(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('location.province', 'ZZ')
            ->call('save')
            ->assertHasErrors(['location.province' => 'exists']);

        $this->assertSame(0, StructureDraft::query()->count());
        $this->assertSame(0, Structure::withHidden()->count());
        // Nessuna foto su disco: la validazione gira PRIMA di collectPhotos().
        $this->assertSame([], Storage::disk('public')->files('structure-photos'));
    }

    /**
     * Il rafforzamento si ACCODA alla regola del Form, non la sostituisce:
     * `location.province` deve restare obbligatoria (HotelLocationForm) E
     * dover esistere in tabella (P3). Con il merge scritto nell'ordine
     * sbagliato uno dei due lati sparisce senza dirlo, e questo test è il solo
     * posto in cui si vede: vuota deve dare `required`, inventata `exists`.
     */
    public function test_an_empty_province_is_still_required(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('location.province', '')
            ->call('save')
            ->assertHasErrors(['location.province' => 'required']);

        $this->assertSame(0, StructureDraft::query()->count());
    }

    public function test_a_name_longer_than_one_hundred_and_ten_characters_is_refused(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('name.it', str_repeat('b', 111))
            ->call('save')
            ->assertHasErrors(['name.it' => 'max']);

        $this->assertSame(0, StructureDraft::query()->count());
        $this->assertSame([], Storage::disk('public')->files('structure-photos'));
    }

    public function test_fewer_than_four_photos_are_refused(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            // `set` con dei file passa da _finishUpload, che in questa versione
            // di Livewire ACCODA invece di sostituire: senza svuotare prima,
            // alle quattro foto di filled() se ne aggiungerebbero due e la
            // scheda verrebbe pubblicata. L'array vuoto non è un upload, quindi
            // quello sì che sostituisce.
            ->set('photos', [])
            ->set('photos', [UploadedFile::fake()->image('una.jpg'), UploadedFile::fake()->image('due.jpg')])
            ->call('save')
            ->assertHasErrors('photos')
            // Il testo del trait, uguale nelle tre famiglie: StructureCreate
            // non sovrascrive photoMinError().
            ->assertSee(__('admin-catalog.create.photos_min'));

        $this->assertSame(0, StructureDraft::query()->count());
        $this->assertSame([], Storage::disk('public')->files('structure-photos'));
    }

    public function test_a_holiday_home_switches_the_rooms_section_to_the_whole_property(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('type', 'casa_vacanza')
            // Le righe dell'hotel sono state azzerate dal cambio tipologia.
            ->assertSet('rooms.wholeProperty', true)
            ->assertCount('rooms.rooms', 1)
            ->set('rooms.rooms.0.beds', 6)
            ->set('rooms.rooms.0.price', '140')
            ->call('save')
            ->assertHasNoErrors();

        $draft = StructureDraft::query()->sole();

        $this->assertSame('casa_vacanza', $draft->type);
        // La tipologia fittizia arriva dal gruppo room_type_whole del Task 1.
        $this->assertSame('intera_struttura', $draft->rooms[0]['type']);
        $this->assertSame(1, $draft->rooms[0]['count']);
        $this->assertSame(14000, Structure::withHidden()->sole()->price_cents);
    }

    public function test_the_superadmin_never_owns_the_draft_and_the_partner_finds_it_in_his_services(): void
    {
        $partner = $this->payablePartner();
        $superadmin = auth()->user();

        $this->filled($partner)->call('save')->assertHasNoErrors();

        $draft = StructureDraft::query()->sole();
        $this->assertNotSame($superadmin->id, $draft->user_id);

        $this->actingAs($partner);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel Bau Resort')
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.structure.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    /**
     * Le whitelist sono su slug, non su etichette: con `Rule::in(options())`
     * questo test passerebbe rifiutando *tutto*, compreso 'wifi' e 'doppia',
     * e il test di pubblicazione non pubblicherebbe più nulla.
     */
    public function test_an_unknown_option_slug_is_refused_while_the_good_ones_pass(): void
    {
        $partner = $this->payablePartner();

        $this->filled($partner)
            ->set('services.services', ['wifi', 'elicottero'])
            ->set('rooms.rooms.0.type', 'mansarda')
            ->call('save')
            ->assertHasErrors(['services.services.1', 'rooms.rooms.0.type'])
            ->assertHasNoErrors('services.services.0')
            ->assertSee('Scegli una tipologia di camera dall’elenco.');

        $this->assertSame(0, StructureDraft::query()->count());
    }
}
