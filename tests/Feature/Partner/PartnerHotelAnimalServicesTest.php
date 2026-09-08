<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelAnimalServices;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelAnimalServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_options(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.animal-services'))
            ->assertOk()
            ->assertSee(__('partner.hotel_animal_services.heading'))
            ->assertSee(__('partner.hotel_animal_services.step'))
            ->assertSee(__('partner.hotel_animal_services.opt_welcome'))
            ->assertSee(__('partner.hotel_animal_services.opt_petsitting_desc'))
            ->assertSee(__('partner.hotel_animal_services.opt_vet'))
            ->assertSee(__('partner.hotel_animal_services.next'));
    }

    public function test_selections_bind_and_next_passes(): void
    {
        Livewire::test(HotelAnimalServices::class)
            ->set('services', ['omaggio', 'area_animali'])
            ->assertSet('services', ['omaggio', 'area_animali'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.smartbox'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 8]);
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(HotelAnimalServices::class)
            ->assertDontSee(__('partner.hotel_animal_services.other_placeholder'))
            ->set('services', ['altro'])
            ->assertSee(__('partner.hotel_animal_services.other_placeholder'));
    }

    public function test_other_english_is_optional(): void
    {
        Livewire::test(HotelAnimalServices::class)
            ->set('services', ['altro'])
            ->set('other.it', 'Toelettatura in struttura')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Toelettatura in struttura', $draft->getTranslation('animal_services_other', 'it'));
        $this->assertSame(['it' => 'Toelettatura in struttura'], $draft->getTranslations('animal_services_other'));
    }

    public function test_it_rehydrates_the_saved_services(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 8, 'animal_services' => ['omaggio']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelAnimalServices::class)->assertSet('services', ['omaggio']);
    }
}
