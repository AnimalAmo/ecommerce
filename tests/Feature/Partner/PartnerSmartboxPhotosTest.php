<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxPhotos;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_dropzone(): void
    {
        $this->get(route('partner.smartbox.photos'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_photos.heading'))
            ->assertSee(__('partner.smartbox_photos.step'))
            ->assertSee(__('partner.smartbox_photos.drop'));
    }

    public function test_next_requires_at_least_four_photos(): void
    {
        Storage::fake('public');

        Livewire::test(SmartboxPhotos::class)
            ->set('photos', [UploadedFile::fake()->image('a.jpg')])
            ->call('next')
            ->assertHasErrors('photos');
    }

    public function test_next_stores_photos_and_advances(): void
    {
        Storage::fake('public');

        Livewire::test(SmartboxPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
            ])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.price'));

        $draft = StructureDraft::first();
        $this->assertCount(4, $draft->photos);
        $this->assertSame(11, $draft->current_step);
    }

    public function test_it_rehydrates_saved_photos(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 11,
            'photos' => ['smartbox-photos/x.jpg'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxPhotos::class)->assertSet('saved', ['smartbox-photos/x.jpg']);
    }
}
