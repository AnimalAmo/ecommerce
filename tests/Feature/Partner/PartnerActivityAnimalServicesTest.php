<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityAnimalServices;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityAnimalServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_options(): void
    {
        $this->get(route('partner.activity.animal-services'))
            ->assertOk()
            ->assertSee(__('partner.activity_animal_services.heading'))
            ->assertSee(__('partner.activity_animal_services.step'))
            ->assertSee(__('partner.hotel_animal_services.opt_welcome'))
            ->assertSee(__('partner.hotel_animal_services.opt_petsitting_desc'))
            ->assertSee(__('partner.hotel_animal_services.opt_vet'))
            ->assertSee(__('partner.hotel_animal_services.next'));
    }

    public function test_selections_bind_and_next_passes(): void
    {
        Livewire::test(ActivityAnimalServices::class)
            ->set('services', ['omaggio', 'area_animali'])
            ->assertSet('services', ['omaggio', 'area_animali'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.cost'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 7]);
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(ActivityAnimalServices::class)
            ->assertDontSee(__('partner.hotel_animal_services.other_placeholder'))
            ->set('services', ['altro'])
            ->assertSee(__('partner.hotel_animal_services.other_placeholder'));
    }

    public function test_other_english_is_optional(): void
    {
        Livewire::test(ActivityAnimalServices::class)
            ->set('services', ['altro'])
            ->set('other.it', 'Toelettatura in loco')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Toelettatura in loco', $draft->getTranslation('animal_services_other', 'it'));
        $this->assertSame(['it' => 'Toelettatura in loco'], $draft->getTranslations('animal_services_other'));
    }

    public function test_it_rehydrates_the_saved_services(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 7, 'animal_services' => ['omaggio']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityAnimalServices::class)->assertSet('services', ['omaggio']);
    }
}
