<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\ActivityCreate;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Models\User;
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

class ActivityCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Storage::fake('public');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    /** Il partner arriva dalla query string: $partnerId è #[Url] #[Locked], non un parametro di mount. */
    private function componentFor(User $partner): Testable
    {
        return Livewire::withQueryParams(['partner' => $partner->id])->test(ActivityCreate::class);
    }

    /** Gli stessi dati della fixture di EventPublisherTest, dal lato pannello. */
    private function fill(Testable $component): Testable
    {
        return $component
            ->set('type', 'eventi')
            ->set('name.it', 'Aperitivo a 6 zampe')
            ->set('name.en', 'Six-legged happy hour')
            ->set('description.it', 'Un aperitivo con i vostri amici pelosi.')
            ->set('location.address', 'Piazza Duomo 1')
            ->set('location.city', 'Milano')
            ->set('location.province', 'MI')
            ->set('location.zip', '20121')
            ->set('location.meetingPoint.it', 'Piazza Duomo')
            // Campi nati dalle risposte della cliente del 27/09/2026. Stanno DOPO
            // `set('type')` non per ordine estetico: `updatedType()` azzera
            // `categories` e `categoriesOther`, quindi scritti prima verrebbero
            // cancellati dallo stesso set del tipo.
            ->set('categories', ['fiere_mercatini', 'altro'])
            ->set('categoriesOther.it', 'Sagra del cane')
            ->set('info.dateStart', '2026-08-01')
            ->set('info.dateEnd', '2026-08-01')
            ->set('info.timeStart', '10:00')
            ->set('info.timeEnd', '18:00')
            ->set('info.bookingRequirement', 'obbligatoria')
            ->set('info.recurrence', 'ricorrente')
            ->set('info.maxParticipants', '30')
            ->set('included.services', ['wifi'])
            ->set('included.additional', ['colazione'])
            ->set('animalServices', ['veterinario'])
            ->set('costType', 'pagamento')
            ->set('pricePerPerson', '25')
            ->set('when', '1')
            ->set('photos', [
                UploadedFile::fake()->image('uno.jpg'),
                UploadedFile::fake()->image('due.jpg'),
                UploadedFile::fake()->image('tre.jpg'),
                UploadedFile::fake()->image('quattro.jpg'),
            ]);
    }

    /** La bozza che il wizard partner scriverebbe con gli stessi dati. */
    private function wizardDraft(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Aperitivo a 6 zampe', 'en' => 'Six-legged happy hour'],
            'description' => ['it' => 'Un aperitivo con i vostri amici pelosi.'],
            'meeting_point' => ['it' => 'Piazza Duomo'],
            // Le stesse colonne che `fill()` scrive dal lato pannello (27/09/2026).
            'event_categories' => ['fiere_mercatini', 'altro'],
            'event_categories_other' => ['it' => 'Sagra del cane'],
            'booking_requirement' => 'obbligatoria',
            'recurrence' => 'ricorrente',
            'max_participants' => 30,
            'address' => 'Piazza Duomo 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-08-01',
            'date_end' => '2026-08-01',
            'time_start' => '10:00',
            'time_end' => '18:00',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'cancellation_when' => '1',
            'services' => ['wifi'],
            'additional_services' => ['colazione'],
            'animal_services' => ['veterinario'],
            'photos' => ['structure-photos/evento.jpg'],
        ]);
    }

    /** Colonne che devono coincidere: fuori id, slug, posizione e foto. */
    private function comparable(Event $event): array
    {
        $event = $event->fresh(['amenities', 'venue']);

        return [
            'type' => $event->type->value,
            'title' => $event->getTranslations('title'),
            'description' => $event->getTranslations('description'),
            'location' => $event->location,
            'starts_at' => $event->starts_at->toDateTimeString(),
            'ends_at' => $event->ends_at->toDateTimeString(),
            'duration_days' => $event->duration_days,
            'max_participants' => $event->max_participants,
            // Le colonne del 27/09/2026, in questa lista e non in un test a
            // parte: è questo confronto che impedisce al pannello di divergere
            // dal wizard, e una colonna che non ci sta dentro può divergere
            // senza che nessuno se ne accorga. Le tipologie si confrontano su
            // TUTTE E QUATTRO le colonne, comprese quelle del ramo abbandonato:
            // il valore che non deve arrivare è metà della regola.
            'activity_categories' => $event->activity_categories,
            'activity_categories_other' => $event->getTranslations('activity_categories_other'),
            'event_categories' => $event->event_categories,
            'event_categories_other' => $event->getTranslations('event_categories_other'),
            'operating_area' => $event->getTranslations('operating_area'),
            'recurrence' => $event->recurrence,
            'booking_requirement' => $event->booking_requirement,
            'price_cents' => $event->price_cents,
            'is_free' => $event->is_free,
            'cancellation_policy_days' => $event->cancellation_policy_days,
            'hotel' => $event->amenityRows('hotel'),
            'animal' => $event->amenityRows('animal'),
            'venue' => [$event->venue?->name, $event->venue?->address],
        ];
    }

    public function test_the_published_event_is_the_one_the_wizard_would_produce(): void
    {
        $adminPartner = $this->actingAsPayablePartner();
        $wizardPartner = $this->actingAsPayablePartner();
        $reference = app(DraftPublisher::class)->publish($this->wizardDraft($wizardPartner));

        $this->actingAsSuperadmin();
        $this->fill($this->componentFor($adminPartner))->call('save')->assertHasNoErrors();

        $event = Event::withHidden()->where('user_id', $adminPartner->id)->sole();

        $this->assertSame($this->comparable($reference), $this->comparable($event));
    }

    public function test_the_draft_belongs_to_the_partner_and_carries_the_family(): void
    {
        $partner = $this->actingAsPayablePartner();
        $admin = $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->assertSame($partner->id, $draft->user_id);
        $this->assertNotSame($admin->id, $draft->user_id);
        $this->assertSame('attivita', $draft->service_category);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->status);
        $this->assertSame(11, $draft->current_step);
        $this->assertNull($draft->publish_requested_at);
        $this->assertCount(4, $draft->photos);
        Storage::disk('public')->assertExists($draft->photos[0]);
        $this->assertStringStartsWith('structure-photos/', $draft->photos[0]);
    }

    public function test_with_moderation_on_the_row_is_born_approved(): void
    {
        config(['admin.moderation' => true]);

        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $event = Event::withHidden()->sole();

        $this->assertSame('approved', $event->approval_status);
        $this->assertNotNull($event->approved_at);
        $this->assertNull($event->approval_requested_at);
    }

    public function test_an_online_partner_without_stripe_leaves_the_draft_waiting(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->assertSame(0, Event::withHidden()->count());
        $this->assertNotNull($draft->publish_requested_at);
        // Insiemi, non liste: Storage::files() non garantisce l'ordine e i nomi
        // che genera store() sono casuali.
        $this->assertEqualsCanonicalizing($draft->photos, Storage::disk('public')->files('structure-photos'));
    }

    public function test_an_offline_partner_publishes_without_stripe(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $this->assertSame(1, Event::withHidden()->where('user_id', $partner->id)->count());
    }

    /**
     * Si asserisce l'EFFETTO, non il testo: il messaggio ha il segnaposto
     * `:name` e lo mostra un flux:toast, che vive in un popover top-layer e non
     * entra nel DOM della risposta. Il testo è verificato in
     * PartnerServiceExceptionTest.
     */
    public function test_a_partner_without_the_role_is_refused(): void
    {
        $notPartner = User::factory()->create(['is_active' => true]);
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($notPartner))->call('save');

        $this->assertSame(0, StructureDraft::count());
        $this->assertSame(0, Event::withHidden()->count());
        // Un `?partner=` non idoneo non viene nemmeno accettato dal trait: il
        // salvataggio si ferma sul select.
        $this->assertSame([], Storage::disk('public')->files('structure-photos'));
    }

    public function test_a_province_outside_the_table_is_refused(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('location.province', 'ZZ')
            ->call('save')
            ->assertHasErrors('location.province')
            ->assertSee(__('admin-catalog.create.validation.province_exists'));

        $this->assertSame(0, StructureDraft::count());
    }

    public function test_a_name_longer_than_the_limit_is_refused(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('name.it', str_repeat('a', 111))
            ->call('save')
            ->assertHasErrors(['name.it' => 'max']);

        $this->assertSame(0, StructureDraft::count());
    }

    /**
     * Il rafforzamento `max:110` si ACCODA alla regola di ActivityLocationForm
     * (`required|string|max:128`), non la sostituisce. I due test qui sotto
     * sono le due metà di quella frase: vuoto → `required` (che il merge con
     * `??=` faceva sparire), 111 caratteri → `max`. Nessun altro test li
     * copre, perché `fill()` valorizza sempre il punto d'incontro.
     */
    public function test_the_meeting_point_stays_required(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('location.meetingPoint.it', '')
            ->call('save')
            ->assertHasErrors(['location.meetingPoint.it' => 'required']);

        $this->assertSame(0, StructureDraft::count());
    }

    public function test_a_meeting_point_longer_than_the_limit_is_refused(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('location.meetingPoint.it', str_repeat('a', 111))
            ->call('save')
            ->assertHasErrors(['location.meetingPoint.it' => 'max']);

        $this->assertSame(0, StructureDraft::count());
    }

    /** I 200 caratteri del wizard restano: stringere a 110 bloccherebbe il partner su un testo già suo. */
    public function test_a_two_hundred_character_description_is_accepted(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('description.it', str_repeat('a', 200))
            ->call('save')
            ->assertHasNoErrors('description.it');
    }

    public function test_the_italian_comma_is_accepted_in_the_price(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('pricePerPerson', '25,50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2550, Event::withHidden()->sole()->price_cents);
    }

    public function test_fewer_than_four_photos_leave_nothing_behind(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            // `set` con dei file passa da _finishUpload, che in questa versione
            // di Livewire ACCODA invece di sostituire (stessa nota in
            // StructureCreateTest): senza svuotare prima, alle quattro foto di
            // fill() se ne aggiungerebbe una quinta e la scheda verrebbe
            // pubblicata. L'array vuoto non è un upload, quindi quello sostituisce.
            ->set('photos', [])
            ->set('photos', [UploadedFile::fake()->image('sola.jpg')])
            ->call('save')
            ->assertHasErrors('photos')
            // Stesso testo delle altre due famiglie: photoMinError() non è
            // sovrascritto da nessuna parte.
            ->assertSee(__('admin-catalog.create.photos_min'));

        $this->assertSame(0, StructureDraft::count());
        $this->assertSame([], Storage::disk('public')->files('structure-photos'));
    }

    public function test_the_partner_finds_the_service_and_reopens_his_own_wizard(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();

        $this->actingAs($partner);
        Livewire::test(PartnerMyServices::class)
            ->assertSee('Aperitivo a 6 zampe')
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.activity.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    public function test_only_a_superadmin_reaches_the_form(): void
    {
        $partner = $this->actingAsPayablePartner();
        $url = route('admin.catalog.create', ['family' => 'activity', 'partner' => $partner->id]);

        $this->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect(route('admin.login'));
    }

    /**
     * `ActivityInfoForm` dichiara `after_or_equal:dateStart`, che dentro il
     * componente si riferirebbe a un campo `dateStart` inesistente (i campi
     * sono sotto `info.`): la regola passerebbe sempre, e una data di fine
     * prima di quella di inizio arriverebbe al catalogo.
     */
    public function test_an_end_date_before_the_start_is_refused(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('info.dateStart', '2026-08-10')
            ->set('info.dateEnd', '2026-08-01')
            ->call('save')
            ->assertHasErrors(['info.dateEnd' => 'after_or_equal']);

        $this->assertSame(0, StructureDraft::query()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Ramo professionale e campi del 27/09/2026
    |--------------------------------------------------------------------------
    */

    /**
     * Il gemello di `fill()` sull'altro ramo. Non si ottiene da `fill()` con un
     * `set('type','attivita')` dopo: quel set passa da `updatedType()`, che
     * azzera le tipologie, quindi il tipo va scelto PRIMA di tutto il resto.
     * `detailedDescription.it` è obbligatoria solo qui (come ActivityDescription),
     * e la data non c'è: dal 27/09/2026 un servizio professionale non ne ha una.
     */
    private function fillActivity(Testable $component): Testable
    {
        return $component
            ->set('type', 'attivita')
            ->set('name.it', 'Toelettatura a domicilio')
            ->set('description.it', 'Toelettatura per cani e gatti, a casa tua.')
            ->set('detailedDescription.it', 'Lavaggio, taglio e asciugatura, con prodotti naturali.')
            ->set('categories', ['toelettatore', 'dog_sitter'])
            ->set('location.address', 'Via Roma 1')
            ->set('location.city', 'Milano')
            ->set('location.province', 'MI')
            ->set('location.zip', '20121')
            ->set('location.operatingArea.it', 'Milano e provincia')
            ->set('info.bookingRequirement', 'facoltativa')
            ->set('costType', 'pagamento')
            ->set('pricePerPerson', '40')
            ->set('when', '1')
            ->set('photos', [
                UploadedFile::fake()->image('uno.jpg'),
                UploadedFile::fake()->image('due.jpg'),
                UploadedFile::fake()->image('tre.jpg'),
                UploadedFile::fake()->image('quattro.jpg'),
            ]);
    }

    /**
     * Il percorso completo del ramo professionale dal pannello: zona operativa e
     * categorie nella loro colonna, punto d'incontro e colonne dell'altro ramo
     * intonse, e nessuna data — che prima di questa modifica era obbligatoria e
     * rendeva impubblicabile ogni servizio professionale.
     */
    public function test_an_activity_created_from_the_panel_carries_the_operating_area(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fillActivity($this->componentFor($partner))->call('save')->assertHasNoErrors();

        $draft = StructureDraft::sole();
        $this->assertSame('Milano e provincia', $draft->getTranslation('operating_area', 'it'));
        $this->assertSame(['toelettatore', 'dog_sitter'], $draft->activity_categories);
        $this->assertNull($draft->event_categories);
        $this->assertSame([], $draft->getTranslations('meeting_point'));
        $this->assertNull($draft->date_start);

        $event = Event::withHidden()->where('user_id', $partner->id)->sole();
        $this->assertSame('activity', $event->type->value);
        $this->assertSame('Milano e provincia', $event->getTranslation('operating_area', 'it'));
        $this->assertSame(['toelettatore', 'dog_sitter'], $event->activity_categories);
        $this->assertSame('facoltativa', $event->booking_requirement);
        // Nessun ritrovo inventato: il Venue nasce col nome vuoto, ed è il
        // segnale su cui la scheda decide se stampare la riga «Ritrovo».
        $this->assertSame('', $event->venue->name);
    }

    /**
     * La whitelist di `categories.*` è quella del ramo corrente. Uno slug
     * dell'altra lista — forgiato, o rimasto in una richiesta partita prima del
     * cambio di tipo — è rifiutato, non scritto nella colonna sbagliata.
     */
    public function test_the_panel_refuses_a_category_from_the_other_branch(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('categories', ['toelettatore'])
            ->call('save')
            ->assertHasErrors(['categories.0' => 'in'])
            ->assertSee(__('admin-catalog.create.validation.option_unknown'));

        $this->assertSame(0, StructureDraft::count());
    }

    public function test_the_panel_refuses_an_invented_booking_requirement(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('info.bookingRequirement', 'forse')
            ->call('save')
            ->assertHasErrors(['info.bookingRequirement' => 'in'])
            ->assertSee(__('admin-catalog.create.validation.option_unknown'));

        $this->assertSame(0, StructureDraft::count());
    }

    /**
     * `required_with:dateEnd` di ActivityInfoForm nomina una chiave di primo
     * livello: qui i campi stanno sotto `info.`, e `required_with` su un campo
     * assente è sempre soddisfatto — la regola passava in silenzio e una data di
     * fine senza inizio arrivava alla bozza, dove il publisher ne farebbe un
     * `ends_at` senza `starts_at`. La riscrittura del riferimento è la gemella
     * di quella di `after_or_equal`, e come lei vale solo sul ramo dove la
     * regola esiste.
     */
    public function test_an_end_date_without_a_start_is_refused_on_an_activity(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fillActivity($this->componentFor($partner))
            ->set('info.dateEnd', '2026-08-03')
            ->call('save')
            ->assertHasErrors(['info.dateStart' => 'required_with']);

        $this->assertSame(0, StructureDraft::count());
    }

    /**
     * Il tetto è quello della colonna (`unsignedSmallInteger`): in MySQL strict
     * un valore più grande è un errore SQL, non una validazione, e la suite gira
     * su SQLite, dove passerebbe in silenzio.
     */
    public function test_seats_above_the_column_ceiling_are_refused_by_the_panel(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->fill($this->componentFor($partner))
            ->set('info.maxParticipants', '65536')
            ->call('save')
            ->assertHasErrors(['info.maxParticipants' => 'max']);

        $this->assertSame(0, StructureDraft::count());
    }

    /**
     * Cambiando ramo le tipologie si azzerano SEMPRE, in tutte due le direzioni:
     * le due liste non hanno uno slug in comune, e una scelta rimasta in memoria
     * sarebbe rifiutata da `categories.*` sotto una casella che non viene più
     * disegnata — un errore che l'admin non potrebbe correggere.
     */
    public function test_switching_branch_clears_the_categories(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->componentFor($partner)
            ->set('type', 'attivita')
            ->set('categories', ['toelettatore'])
            ->set('categoriesOther.it', 'Pensione per conigli')
            ->set('type', 'eventi')
            ->assertSet('categories', [])
            ->assertSet('categoriesOther.it', '')
            // Lo stesso set decide anche quale dei due campi del luogo viene
            // chiesto e salvato: senza questa riga il pannello smetterebbe di
            // chiedere il punto d'incontro di un evento.
            ->assertSet('location.isEvent', true)
            ->assertSet('info.isEvent', true);
    }

    /** Il ramo professionale nasce col luogo sul suo ramo, senza passare da un cambio di tipo. */
    public function test_the_form_opens_on_the_activity_branch(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->actingAsSuperadmin();

        $this->componentFor($partner)
            ->assertSet('type', 'attivita')
            ->assertSet('location.isEvent', false)
            ->assertSet('info.isEvent', false);
    }
}
