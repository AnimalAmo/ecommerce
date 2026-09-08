<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityPhotos;
use App\Models\Structure\StructureDraft;
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
}
