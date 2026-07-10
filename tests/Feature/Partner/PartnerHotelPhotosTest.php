<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelPhotosTest extends TestCase
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
        $this->get(route('partner.structure.hotel.photos'))
            ->assertOk()
            ->assertSee(__('partner.hotel_photos.heading'))
            ->assertSee(__('partner.hotel_photos.step'))
            ->assertSee(__('partner.hotel_photos.drop'))
            ->assertSee(__('partner.hotel_photos.hint'))
            ->assertSee(__('partner.hotel_photos.next'));
    }

    public function test_next_requires_at_least_four_photos(): void
    {
        Livewire::test(HotelPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
            ])
            ->call('next')
            ->assertHasErrors('photos');
    }

    public function test_next_stores_four_photos_and_advances(): void
    {
        Livewire::test(HotelPhotos::class)
            ->set('photos', [
                UploadedFile::fake()->image('1.jpg'),
                UploadedFile::fake()->image('2.jpg'),
                UploadedFile::fake()->image('3.jpg'),
                UploadedFile::fake()->image('4.jpg'),
            ])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.payment'));

        $draft = \App\Models\Structure\StructureDraft::firstOrFail();
        $this->assertCount(4, $draft->photos);
        Storage::disk('public')->assertExists($draft->photos[0]);
    }

    public function test_remove_photo_drops_a_thumbnail(): void
    {
        Livewire::test(HotelPhotos::class)
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
        Livewire::test(HotelPhotos::class)
            ->set('photos', [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])
            ->assertHasErrors('photos.*');
    }
}
