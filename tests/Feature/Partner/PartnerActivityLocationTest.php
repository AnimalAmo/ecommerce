<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityLocation;
use App\Models\Structure\StructureDraft;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Step 3 "Luogo". Dal 27/09/2026 questo step ha DUE rami (risposta della
 * cliente): un evento ha un punto d'incontro obbligatorio, un servizio
 * professionale non ha un ritrovo ma una zona in cui lavora, facoltativa.
 *
 * Le prove che prima non dicevano il ramo lo dicono adesso: una bozza senza
 * `type` vale come ramo professionale, quindi chiedere il punto d'incontro su
 * una bozza muta non provava più il punto d'incontro — provava il ramo
 * sbagliato. Ogni prova del punto d'incontro nasce ora con `type => 'eventi'`,
 * e accanto c'è la gemella della zona, che prima non era coperta.
 */
class PartnerActivityLocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `ActivityLocationForm` verifica la sigla con `exists:provinces,short_name`:
     * senza le province a database anche 'VR' sarebbe rifiutata. Stessa coppia di
     * seeder di StructurePublisherTest (ProvinceSeeder vuole le regioni).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RegionSeeder::class, ProvinceSeeder::class]);
    }

    /**
     * Bozza del ramo richiesto, messa in sessione. `user_id` esplicito (anche
     * null) perché `ownedDraft()` confronta il proprietario con l'utente
     * loggato: una bozza senza proprietario si apre solo da ospite, ed è così
     * che girano le prove Livewire di questo step.
     */
    private function draftInSession(string $type, ?int $userId = null): StructureDraft
    {
        $draft = StructureDraft::create([
            'user_id' => $userId,
            'status' => 'draft',
            'current_step' => 3,
            'type' => $type,
        ]);
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /** I quattro campi dell'indirizzo, comuni ai due rami. */
    private function withAddress(Testable $component): Testable
    {
        return $component
            ->set('form.address', 'Via Lago 5')
            ->set('form.city', 'Garda')
            ->set('form.province', 'VR')
            ->set('form.zip', '37016');
    }

    public function test_page_renders_the_location_fields_of_an_event(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $partner = $this->actingAsActivePartner();
        $this->draftInSession('eventi', $partner->id);

        $this->get(route('partner.activity.location'))
            ->assertOk()
            ->assertSee(__('partner.activity_location.heading'))
            ->assertSee(__('partner.activity_location.step'))
            ->assertSee(__('partner.activity_location.address'))
            ->assertSee(__('partner.activity_location.meeting_point'))
            ->assertSee(__('partner.activity_location.next'))
            // Il campo del ramo abbandonato non si disegna: il Form non lo
            // valida e non lo salva, e mostrarlo sarebbe un campo perduto.
            ->assertDontSee(__('partner.activity_location.operating_area'));
    }

    /**
     * La metà che prima non esisteva. Un professionista arriva qui dalla card
     * "Servizio professionale", che salta lo step del tipo: se questa pagina
     * gli chiedesse il ritrovo resterebbe bloccato, ed è il difetto da cui
     * viene la modifica.
     */
    public function test_page_renders_the_operating_area_of_an_activity(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->draftInSession('attivita', $partner->id);

        $this->get(route('partner.activity.location'))
            ->assertOk()
            ->assertSee(__('partner.activity_location.address'))
            ->assertSee(__('partner.activity_location.operating_area'))
            ->assertSee(__('partner.activity_location.operating_area_hint'))
            ->assertDontSee(__('partner.activity_location.meeting_point'));
    }

    public function test_next_requires_the_fields_of_an_event(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityLocation::class)
            ->call('next')
            ->assertHasErrors(['form.address', 'form.city', 'form.province', 'form.zip', 'form.meetingPoint.it']);
    }

    /**
     * L'indirizzo resta obbligatorio per tutti — è lui che fa l'etichetta del
     * luogo a catalogo — ma il punto d'incontro no, e la zona nemmeno: la
     * cliente la chiama «eventuale».
     */
    public function test_next_asks_an_activity_only_for_the_address(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityLocation::class)
            ->call('next')
            ->assertHasErrors(['form.address', 'form.city', 'form.province', 'form.zip'])
            ->assertHasNoErrors(['form.meetingPoint.it', 'form.operatingArea.it']);
    }

    public function test_next_saves_the_location_of_an_event(): void
    {
        $this->draftInSession('eventi');

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->set('form.meetingPoint.it', 'Ingresso del parco')
            ->set('form.meetingPoint.en', 'Park entrance')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.description'));

        $this->assertDatabaseHas('structure_drafts', [
            'city' => 'Garda',
            'current_step' => 3,
        ]);

        $draft = StructureDraft::first();
        $this->assertSame('Ingresso del parco', $draft->getTranslation('meeting_point', 'it'));
        $this->assertSame('Park entrance', $draft->getTranslation('meeting_point', 'en'));
        // La colonna dell'altro ramo non viene sfiorata da questo step.
        $this->assertSame([], $draft->getTranslations('operating_area'));
    }

    /** La gemella: sul ramo professionale lo step scrive `operating_area`. */
    public function test_next_saves_the_operating_area_of_an_activity(): void
    {
        $this->draftInSession('attivita');

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->set('form.operatingArea.it', 'Milano e provincia')
            ->set('form.operatingArea.en', 'Milan and province')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.description'));

        $draft = StructureDraft::first();
        $this->assertSame('Milano e provincia', $draft->getTranslation('operating_area', 'it'));
        $this->assertSame('Milan and province', $draft->getTranslation('operating_area', 'en'));
        $this->assertSame([], $draft->getTranslations('meeting_point'));
    }

    public function test_the_english_meeting_point_is_optional(): void
    {
        $this->draftInSession('eventi');

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->set('form.meetingPoint.it', 'Ingresso del parco')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Ingresso del parco', $draft->getTranslation('meeting_point', 'en'));
        $this->assertSame(['it' => 'Ingresso del parco'], $draft->getTranslations('meeting_point'));
    }

    /**
     * Zona vuota in tutte due le lingue: lo step passa e la colonna resta
     * vuota. Un dog sitter che non vuole dichiarare un raggio deve arrivare
     * comunque allo step dopo.
     */
    public function test_the_operating_area_is_optional_in_both_languages(): void
    {
        $this->draftInSession('attivita');

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.description'));

        $this->assertSame([], StructureDraft::first()->getTranslations('operating_area'));
    }

    /**
     * Sigla fuori elenco. Un'attività non ha `region_id` — EventPublisher scrive
     * un Venue — ma la sigla finisce testuale nell'indirizzo e nell'etichetta del
     * luogo: una sigla inventata diventa un «Garda (ZZ)» a catalogo, non più
     * modificabile dal partner a scheda pubblicata.
     */
    public function test_next_rejects_a_province_outside_the_list(): void
    {
        $this->draftInSession('eventi');

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->set('form.province', 'ZZ')
            ->set('form.meetingPoint.it', 'Ingresso del parco')
            ->call('next')
            ->assertHasErrors(['form.province' => 'exists']);

        // Quel che conta è che lo step non abbia scritto la sigla inventata.
        $this->assertNull(StructureDraft::first()->province);
    }

    public function test_it_rehydrates_the_saved_meeting_point(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 3, 'type' => 'eventi', 'meeting_point' => ['it' => 'Piazza centrale', 'en' => 'Main square']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityLocation::class)
            ->assertSet('form.isEvent', true)
            ->assertSet('form.meetingPoint.it', 'Piazza centrale')
            ->assertSet('form.meetingPoint.en', 'Main square');
    }

    public function test_it_rehydrates_the_saved_operating_area(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 3, 'type' => 'attivita', 'operating_area' => ['it' => 'Lombardia', 'en' => 'Lombardy']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityLocation::class)
            ->assertSet('form.isEvent', false)
            ->assertSet('form.operatingArea.it', 'Lombardia')
            ->assertSet('form.operatingArea.en', 'Lombardy');
    }

    // ── Giro del tester, 28/09/2026: W5 sul luogo ────────────────────────────
    //
    // Punto d'incontro e zona passano da `ActivityLocationForm::toDraft()`, che
    // la lane nome ha portato su Translations::replacing(): nessuna prova lo
    // guardava. Svuotato il tab EN, l'inglese sparisce e l'italiano resta.

    public function test_svuotare_linglese_del_punto_dincontro_lo_toglie(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 3,
            'type' => 'eventi',
            'meeting_point' => ['it' => 'Piazza centrale', 'en' => 'Main square'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->assertSet('form.meetingPoint.en', 'Main square')
            ->set('form.meetingPoint.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame(['it' => 'Piazza centrale'], $draft->fresh()->getTranslations('meeting_point'));
    }

    public function test_svuotare_linglese_della_zona_la_toglie(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 3,
            'type' => 'attivita',
            'operating_area' => ['it' => 'Lombardia', 'en' => 'Lombardy'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->assertSet('form.operatingArea.en', 'Lombardy')
            ->set('form.operatingArea.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame(['it' => 'Lombardia'], $draft->fresh()->getTranslations('operating_area'));
    }

    /** La zona è facoltativa in tutte due le lingue: svuotata del tutto, la colonna non porta più niente. */
    public function test_svuotare_la_zona_in_tutte_le_lingue_la_toglie(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 3,
            'type' => 'attivita',
            'operating_area' => ['it' => 'Lombardia', 'en' => 'Lombardy'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->withAddress(Livewire::test(ActivityLocation::class))
            ->set('form.operatingArea.it', '')
            ->set('form.operatingArea.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame([], $draft->getTranslations('operating_area'));
        $this->assertTrue(blank($draft->operating_area));
    }
}
