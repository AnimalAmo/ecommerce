<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelTitle;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_name_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.title'))
            ->assertOk()
            ->assertSee(__('partner.hotel_title.heading'))
            ->assertSee(__('partner.hotel_title.step'))
            ->assertSee(__('partner.hotel_title.field_label'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
            ->assertSee(__('partner.hotel_title.next'));
    }

    public function test_next_requires_the_italian_name(): void
    {
        Livewire::test(HotelTitle::class)
            ->set('name.en', 'Only English')
            ->call('next')
            ->assertHasErrors('name.it');
    }

    public function test_next_saves_the_translations_and_advances(): void
    {
        Livewire::test(HotelTitle::class)
            ->set('name.it', 'Hotel Bau Resort')
            ->set('name.en', 'Bau Resort Hotel')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.location'));

        $draft = StructureDraft::first();
        $this->assertSame('Hotel Bau Resort', $draft->getTranslation('name', 'it'));
        $this->assertSame('Bau Resort Hotel', $draft->getTranslation('name', 'en'));
        $this->assertSame(2, $draft->current_step);
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        Livewire::test(HotelTitle::class)
            ->set('name.it', 'Hotel Bau Resort')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Hotel Bau Resort', $draft->getTranslation('name', 'en'));
        $this->assertSame(['it' => 'Hotel Bau Resort'], $draft->getTranslations('name'));
    }

    public function test_it_rehydrates_the_saved_translations(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => ['it' => 'Agriturismo Rex', 'en' => 'Rex Farm']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelTitle::class)
            ->assertSet('name.it', 'Agriturismo Rex')
            ->assertSet('name.en', 'Rex Farm');
    }
}
