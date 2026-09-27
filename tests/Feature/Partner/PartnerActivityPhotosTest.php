<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityPhotos;
use App\Models\Event\Event;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
    public function test_abbandonare_la_modifica_dopo_la_X_non_lascia_la_scheda_con_un_file_inesistente(): void
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
}
