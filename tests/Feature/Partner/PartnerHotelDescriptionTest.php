<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelDescription;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_description_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.description'))
            ->assertOk()
            ->assertSee(__('partner.hotel_description.heading'))
            ->assertSee(__('partner.hotel_description.step'))
            ->assertSee(__('partner.hotel_description.section'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
            ->assertSee(__('partner.hotel_description.chars'))
            ->assertSee(__('partner.hotel_description.next'));
    }

    public function test_next_requires_the_italian_description(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description.en', 'Only English')
            ->call('next')
            ->assertHasErrors('description.it');
    }

    public function test_next_rejects_a_description_over_200_chars(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description.it', str_repeat('a', 201))
            ->call('next')
            ->assertHasErrors('description.it');
    }

    public function test_next_saves_the_translations_and_advances(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description.it', 'Un accogliente hotel pet-friendly nel cuore della città.')
            ->set('description.en', 'A cosy pet-friendly hotel in the heart of the city.')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.rooms'));

        $draft = StructureDraft::first();
        $this->assertSame('Un accogliente hotel pet-friendly nel cuore della città.', $draft->getTranslation('description', 'it'));
        $this->assertSame('A cosy pet-friendly hotel in the heart of the city.', $draft->getTranslation('description', 'en'));
        $this->assertSame(4, $draft->current_step);
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        Livewire::test(HotelDescription::class)
            ->set('description.it', 'Un accogliente hotel pet-friendly nel cuore della città.')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Un accogliente hotel pet-friendly nel cuore della città.', $draft->getTranslation('description', 'en'));
        $this->assertSame(['it' => 'Un accogliente hotel pet-friendly nel cuore della città.'], $draft->getTranslations('description'));
    }

    public function test_it_rehydrates_the_saved_translations(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'description' => ['it' => 'Bella struttura', 'en' => 'Lovely place']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelDescription::class)
            ->assertSet('description.it', 'Bella struttura')
            ->assertSet('description.en', 'Lovely place');
    }
}
