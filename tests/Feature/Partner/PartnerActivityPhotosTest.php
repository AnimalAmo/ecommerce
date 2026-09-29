<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityPhotos;
use App\Models\Event\Event;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftCompleter;
use App\Services\Partner\Publishing\DraftPublisher;
use App\Services\Partner\Publishing\EventPublisher;
use App\Services\Partner\Publishing\SmartboxPublisher;
use App\Services\Partner\Publishing\StructurePublisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PartnerActivityPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_page_renders_the_dropzone(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.photos'))
            ->assertOk()
            ->assertSee(__('partner.hotel_photos.heading'))
            ->assertSee(__('partner.activity_photos.step'))
            ->assertSee(__('partner.hotel_photos.drop'))
            ->assertSee(__('partner.hotel_photos.hint'))
            ->assertSee(__('partner.hotel_photos.next'));
    }

    /**
     * L'upload passa dal dropzone di Flux, non da un label costruito a mano:
     * è lui a collegare click, drag&drop e input nascosto a wire:model.
     */
    public function test_the_upload_uses_the_flux_dropzone(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.photos'))
            ->assertOk()
            ->assertSee('data-flux-file-upload', false);
    }

    public function test_next_requires_at_least_four_photos(): void
    {
        Livewire::test(ActivityPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
            ])
            ->call('next')
            ->assertHasErrors('photos');
    }

    public function test_next_stores_four_photos_and_advances(): void
    {
        Livewire::test(ActivityPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
                UploadedFile::fake()->image('4.jpg'),
            ])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.cancellation'));

        $draft = StructureDraft::firstOrFail();
        $this->assertCount(4, $draft->photos);
        Storage::disk('public')->assertExists($draft->photos[0]);
        $this->assertDatabaseHas('structure_drafts', ['current_step' => 9]);
    }

    public function test_remove_photo_drops_a_thumbnail(): void
    {
        Livewire::test(ActivityPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
            ])
            ->assertCount('photos', 3)
            ->call('removePhoto', 1)
            ->assertCount('photos', 2);
    }

    public function test_rejects_a_non_image_file(): void
    {
        Livewire::test(ActivityPhotos::class)
            ->set('photos', [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])
            ->assertHasErrors('photos.*');
    }

    public function test_rehydrates_saved_photos_from_the_draft(): void
    {
        $draft = StructureDraft::create(['photos' => ['structure-photos/existing.jpg']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityPhotos::class)
            ->assertCount('saved', 1)
            ->assertSet('saved', ['structure-photos/existing.jpg']);
    }

    // ── Difetto F1: la X cancella dal disco una foto ancora a catalogo ───────

    /** Attività già pubblicata con quattro foto: `events.img` punta alla prima. */
    private function publishedActivityWithFourPhotos(int $ownerId): array
    {
        $photos = [
            'structure-photos/aaa.jpg',
            'structure-photos/bbb.jpg',
            'structure-photos/ccc.jpg',
            'structure-photos/ddd.jpg',
        ];

        $draft = StructureDraft::create([
            'user_id' => $ownerId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Passeggiate al lago'],
            'description' => ['it' => 'Una passeggiata con i vostri amici pelosi.'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'price_type' => 'pagamento',
            'price_per_person' => '20',
            'cancellation_when' => '1',
            'photos' => $photos,
        ]);

        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'jpeg-finto');
        }

        $activity = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Event::class, $activity, 'La fixture non è arrivata a catalogo.');
        $this->assertSame($photos[0], $activity->img, 'La copertina deve essere la prima foto della bozza.');

        session(['structure_draft_id' => $draft->id]);

        return [$draft, $activity];
    }

    /**
     * `removeSaved()` cancella il file dal disco e riscrive `photos` nello
     * stesso click, fuori dal ciclo saveStep → completeDraft → publisher.
     * `events.img` continua a puntare a quel path e `resolveImage()` non
     * controlla l'esistenza del file: la scheda pubblica serve un'immagine
     * rotta, e il file non è più recuperabile nemmeno abbandonando la modifica.
     */
    public function test_togliere_una_foto_salvata_non_cancella_il_file_a_cui_il_catalogo_punta(): void
    {
        $partner = $this->actingAsPayablePartner();
        [, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->assertCount('saved', 4)
            ->call('removeSaved', 0)
            ->assertCount('saved', 3);

        // La riga a catalogo non è stata toccata: il publisher gira solo alla
        // ripubblicazione, che qui non è avvenuta.
        $this->assertSame('structure-photos/aaa.jpg', $activity->fresh()->img);

        $this->assertTrue(
            Storage::disk('public')->exists('structure-photos/aaa.jpg'),
            'Il file della copertina non va cancellato prima che il publisher abbia riscritto img/hero_img: '
            .'la scheda pubblica lo punta ancora e servirebbe un\'immagine rotta.',
        );
    }

    /**
     * L'aggravante: togliendo una foto si scende sotto il minimo, quindi
     * `collectPhotos()` blocca l'avanzamento. Se il partner abbandona qui, il
     * file è già perduto e la scheda resta online con l'immagine morta.
     */
    public function test_abbandonare_la_modifica_dopo_la_x_non_lascia_la_scheda_con_un_file_inesistente(): void
    {
        $partner = $this->actingAsPayablePartner();
        [, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->call('removeSaved', 0)
            // Tre foto su quattro: il minimo non è più raggiunto.
            ->call('next')
            ->assertHasErrors('photos');

        $this->assertTrue(
            Storage::disk('public')->exists((string) $activity->fresh()->img),
            'La copertina della scheda a catalogo deve esistere su disco: senza il file '
            .'HasCatalogImages::resolveImage() compone comunque lo Storage URL e il cliente vede un buco.',
        );
    }

    /*
     * Il resto del ciclo F1: la copertina tolta con la X sparisce dal disco
     * quando la versione nuova è a catalogo (FamilyPublisher::pruneReplacedPhotos,
     * con DB::afterCommit dentro la transazione di DraftCompleter), e mai prima.
     */

    /** X sulla copertina, una foto nuova per tornare a quattro, e lo step foto salvato. */
    private function replaceCoverInTheWizard(): void
    {
        Livewire::test(ActivityPhotos::class)
            ->call('removeSaved', 0)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();
    }

    public function test_rimozione_e_completamento_potano_la_vecchia_copertina_solo_dopo_la_riga_nuova(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        $this->replaceCoverInTheWizard();

        // Salvato lo step, la scheda online punta ancora la copertina vecchia: il file resta.
        $this->assertSame('structure-photos/aaa.jpg', $activity->fresh()->img);
        Storage::disk('public')->assertExists('structure-photos/aaa.jpg');

        // Nel momento in cui la riga a catalogo si riscrive, il file vecchio c'è ancora.
        $existedWhenRowWritten = null;
        Event::saved(function (Event $event) use (&$existedWhenRowWritten): void {
            $existedWhenRowWritten ??= Storage::disk('public')->exists('structure-photos/aaa.jpg');
        });

        app(DraftCompleter::class)->complete($draft->fresh(), 11);

        $fresh = $activity->fresh();
        $this->assertSame('structure-photos/bbb.jpg', $fresh->img, 'La nuova copertina è la prima foto rimasta.');
        $this->assertSame('structure-photos/bbb.jpg', $fresh->hero_img);
        Storage::disk('public')->assertExists('structure-photos/bbb.jpg');
        $this->assertTrue($existedWhenRowWritten, 'Il file vecchio non deve sparire prima che la riga a catalogo punti la copertina nuova.');
        Storage::disk('public')->assertMissing('structure-photos/aaa.jpg');
    }

    /**
     * Pubblicazione fallita a metà: la transazione si annulla, la riga resta
     * sulla copertina vecchia e il file con lei. La cancellazione prenotata
     * con afterCommit deve sparire col rollback.
     *
     * Il fallimento arriva DOPO che il publisher ha scritto la riga nuova
     * (review del 28/09/2026: un'eccezione dentro il save della riga non
     * distingueva afterCommit da una cancellazione immediata). Così, nel
     * momento del fallimento, la riga punta già a bbb: una cancellazione non
     * differita troverebbe aaa non più referenziata e la toglierebbe, il
     * rollback riporterebbe la riga su aaa e la scheda resterebbe col buco.
     */
    public function test_una_pubblicazione_fallita_non_pota_la_copertina_ancora_a_catalogo(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        $this->replaceCoverInTheWizard();

        $this->app->bind(DraftPublisher::class, fn ($app): DraftPublisher => new class($app->make(StructurePublisher::class), $app->make(EventPublisher::class), $app->make(SmartboxPublisher::class)) extends DraftPublisher
        {
            public function publish(StructureDraft $draft): ?Model
            {
                parent::publish($draft);

                throw new RuntimeException('Pubblicazione interrotta dopo la scrittura della riga.');
            }
        });

        try {
            app(DraftCompleter::class)->complete($draft->fresh(), 11);
            $this->fail('La fixture doveva far fallire la pubblicazione.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Pubblicazione interrotta dopo la scrittura della riga.', $exception->getMessage());
        }

        $this->assertSame('structure-photos/aaa.jpg', $activity->fresh()->img);
        Storage::disk('public')->assertExists('structure-photos/aaa.jpg');
    }

    /**
     * La copertina vecchia è anche la foto di un ordine: `order_items.photo_url`
     * fotografa l'URL al momento dell'acquisto e lo mostrano lo storico del
     * cliente e il dettaglio prenotazione del partner. Cambiata la copertina,
     * il file resta.
     */
    public function test_una_copertina_ancora_in_uno_storico_ordini_non_si_cancella(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        OrderItem::factory()->forEvent()->create([
            'purchasable_id' => $activity->id,
            'photo_url' => Storage::disk('public')->url('structure-photos/aaa.jpg'),
        ]);

        $this->replaceCoverInTheWizard();
        app(DraftCompleter::class)->complete($draft->fresh(), 11);

        $this->assertSame('structure-photos/bbb.jpg', $activity->fresh()->img);
        Storage::disk('public')->assertExists('structure-photos/aaa.jpg');
    }

    /** Bozza mai pubblicata: nessuna pagina mostra le sue foto, la X le cancella subito. */
    public function test_la_x_su_una_bozza_mai_pubblicata_cancella_subito_il_file(): void
    {
        $partner = $this->actingAsPayablePartner();
        $photos = ['structure-photos/p1.jpg', 'structure-photos/p2.jpg', 'structure-photos/p3.jpg'];
        foreach ($photos as $path) {
            Storage::disk('public')->put($path, 'jpeg-finto');
        }
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 8,
            'service_category' => 'attivita',
            'photos' => $photos,
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityPhotos::class)
            ->call('removeSaved', 1)
            ->assertSet('saved', ['structure-photos/p1.jpg', 'structure-photos/p3.jpg']);

        $this->assertSame(['structure-photos/p1.jpg', 'structure-photos/p3.jpg'], $draft->fresh()->photos);
        Storage::disk('public')->assertMissing('structure-photos/p2.jpg');
        Storage::disk('public')->assertExists('structure-photos/p1.jpg');
    }

    /**
     * Una foto non-copertina di una scheda pubblicata la mostra la galleria
     * («Vedere tutte le foto», 29/09/2026): la X la toglie dalla bozza ma il
     * file resta finché la versione nuova non è a catalogo, come la copertina.
     * Fino a quel giorno le non-copertina non le mostrava nessuna pagina e si
     * cancellavano subito.
     */
    public function test_la_x_su_una_foto_della_galleria_pubblicata_non_cancella_il_file(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        Livewire::test(ActivityPhotos::class)->call('removeSaved', 2);

        $this->assertNotContains('structure-photos/ccc.jpg', $draft->fresh()->photos);
        $this->assertContains('structure-photos/ccc.jpg', $activity->fresh()->gallery, 'Senza ripubblicazione la scheda online non cambia.');
        Storage::disk('public')->assertExists('structure-photos/ccc.jpg');
    }

    /** Il resto del ciclo: ripubblicata la scheda, la foto tolta esce dalla galleria e dal disco. */
    public function test_la_ripubblicazione_pota_la_foto_tolta_dalla_galleria(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->call('removeSaved', 2)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();

        app(DraftCompleter::class)->complete($draft->fresh(), 11);

        $gallery = $activity->fresh()->gallery;
        $this->assertCount(4, $gallery);
        $this->assertNotContains('structure-photos/ccc.jpg', $gallery);
        $this->assertSame('structure-photos/aaa.jpg', $gallery[0], 'La copertina non è cambiata.');
        Storage::disk('public')->assertMissing('structure-photos/ccc.jpg');
        Storage::disk('public')->assertExists('structure-photos/aaa.jpg');
    }

    /** Pubblicazione fallita a metà: la galleria resta la vecchia, e i suoi file con lei. */
    public function test_una_pubblicazione_fallita_non_pota_una_foto_ancora_in_galleria(): void
    {
        $partner = $this->actingAsPayablePartner();
        [$draft, $activity] = $this->publishedActivityWithFourPhotos($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->call('removeSaved', 2)
            ->set('photos', [UploadedFile::fake()->image('nuova.jpg')])
            ->call('next')
            ->assertHasNoErrors();

        $this->app->bind(DraftPublisher::class, fn ($app): DraftPublisher => new class($app->make(StructurePublisher::class), $app->make(EventPublisher::class), $app->make(SmartboxPublisher::class)) extends DraftPublisher
        {
            public function publish(StructureDraft $draft): ?Model
            {
                parent::publish($draft);

                throw new RuntimeException('Pubblicazione interrotta dopo la scrittura della riga.');
            }
        });

        try {
            app(DraftCompleter::class)->complete($draft->fresh(), 11);
            $this->fail('La fixture doveva far fallire la pubblicazione.');
        } catch (RuntimeException) {
        }

        $this->assertContains('structure-photos/ccc.jpg', $activity->fresh()->gallery);
        Storage::disk('public')->assertExists('structure-photos/ccc.jpg');
    }

    /** Foto di un altro partner, in una bozza mai pubblicata: nessuna riga a catalogo la protegge. */
    private function someoneElsesPhoto(): string
    {
        $path = 'structure-photos/altrui.jpg';
        Storage::disk('public')->put($path, 'jpeg-altrui');
        StructureDraft::create([
            'user_id' => User::factory()->create()->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 9,
            'service_category' => 'attivita',
            'photos' => [$path],
        ]);

        return $path;
    }

    /** Bozza propria mai pubblicata, in sessione. */
    private function ownUnpublishedDraft(int $partnerId): StructureDraft
    {
        Storage::disk('public')->put('structure-photos/mia.jpg', 'jpeg-mio');
        $draft = StructureDraft::create([
            'user_id' => $partnerId,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 8,
            'service_category' => 'attivita',
            'photos' => ['structure-photos/mia.jpg'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /**
     * Il gemello del buco dei due passi, dal lato di next(): collectPhotos()
     * salvava nella bozza `saved` così come arrivava dal client, quindi un path
     * altrui entrava nella bozza, contava nel minimo di quattro e poteva
     * finire in copertina a catalogo. Ora entrano solo le foto che la bozza
     * possiede già, più quelle appena caricate.
     */
    public function test_next_non_porta_nella_bozza_un_path_forgiato(): void
    {
        $partner = $this->actingAsPayablePartner();
        $victim = $this->someoneElsesPhoto();
        $draft = $this->ownUnpublishedDraft($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->set('saved', ['structure-photos/mia.jpg', $victim])
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
            ])
            ->call('next')
            ->assertHasNoErrors();

        $photos = $draft->fresh()->photos;

        $this->assertNotContains($victim, $photos, 'Un path che la bozza non possedeva non deve entrarci.');
        $this->assertContains('structure-photos/mia.jpg', $photos);
        $this->assertCount(4, $photos);
    }

    /**
     * Seconda guardia, indipendente dalla prima: un file che un'altra bozza
     * contiene ancora non si cancella, anche se la X arriva da una bozza che lo
     * possiede davvero. Le foto di una bozza mai pubblicata vivono solo lì,
     * quindi nessuna riga a catalogo le proteggerebbe.
     */
    public function test_un_file_che_unaltra_bozza_contiene_non_si_cancella(): void
    {
        $partner = $this->actingAsPayablePartner();
        $shared = $this->someoneElsesPhoto();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 8,
            'service_category' => 'attivita',
            'photos' => [$shared],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityPhotos::class)
            ->assertSet('saved', [$shared])
            ->call('removeSaved', 0)
            ->assertSet('saved', []);

        $this->assertSame([], $draft->fresh()->photos, 'Dalla propria bozza la foto sparisce comunque.');
        Storage::disk('public')->assertExists($shared);
    }

    /**
     * `saved` arriva dal client (proprietà Livewire non bloccata): da solo non
     * deve bastare a far sparire un file del disco public che la bozza non contiene.
     */
    public function test_un_saved_forgiato_con_un_path_estraneo_non_cancella_nulla(): void
    {
        $partner = $this->actingAsPayablePartner();
        $victim = $this->someoneElsesPhoto();
        $this->ownUnpublishedDraft($partner->id);

        Livewire::test(ActivityPhotos::class)
            ->set('saved', [$victim])
            ->call('removeSaved', 0);

        Storage::disk('public')->assertExists($victim);
    }

    /**
     * Trovato dal tester il 28/09/2026, chiuso lo stesso giorno. La guardia di
     * removeSaved() confrontava il path con `$draft->photos`, ma poco prima
     * removeSaved() stessa riscriveva `$draft->photos` con il `saved` del
     * client. Due chiamate bastavano: la prima, su un indice qualsiasi,
     * scriveva il path altrui nella bozza; la seconda lo trovava "posseduto" e
     * lo cancellava. Ora la bozza è la fonte di verità, e un file che un'altra
     * bozza contiene non si cancella comunque (FamilyPublisher::isPhotoReferenced).
     */
    public function test_un_saved_forgiato_in_due_passi_non_cancella_un_file_altrui(): void
    {
        $partner = $this->actingAsPayablePartner();
        $victim = $this->someoneElsesPhoto();
        $this->ownUnpublishedDraft($partner->id);

        $draft = StructureDraft::query()->where('user_id', $partner->id)->sole();

        $component = Livewire::test(ActivityPhotos::class)
            ->set('saved', [$victim, 'structure-photos/non-esiste.jpg'])
            ->call('removeSaved', 1);

        // Il primo passo è quello che il difetto sfruttava: non deve scrivere
        // il path altrui nella bozza.
        $this->assertNotContains($victim, $draft->fresh()->photos ?? []);

        $component->call('removeSaved', 0);

        Storage::disk('public')->assertExists($victim);
    }

    /**
     * La prima guardia da sola, senza la seconda a coprirla (review del
     * 28/09/2026). Nei due test sopra la vittima sta nella bozza di un altro
     * partner, quindi FamilyPublisher::isPhotoReferenced() la salverebbe anche
     * se removeSaved() si fidasse di `saved`. Qui il file non è in nessuna
     * bozza, riga di catalogo o ordine — per esempio la copertina di un
     * articolo, che medialibrary salva sullo stesso disco public: solo il
     * confronto con la bozza lo protegge.
     */
    public function test_un_saved_forgiato_non_cancella_un_file_che_nessuna_bozza_contiene(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->ownUnpublishedDraft($partner->id);
        Storage::disk('public')->put('1/articolo.jpg', 'jpeg-articolo');

        Livewire::test(ActivityPhotos::class)
            ->set('saved', ['1/articolo.jpg'])
            ->call('removeSaved', 0)
            ->assertSet('saved', ['structure-photos/mia.jpg']);

        Storage::disk('public')->assertExists('1/articolo.jpg');
    }
}
