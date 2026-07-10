<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\SmartboxIncludedAnimals;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxIncludedAnimalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_animal_service_options(): void
    {
        $this->get(route('partner.smartbox.included-animals'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_included_animals.heading'))
            ->assertSee(__('partner.smartbox_included_animals.step'))
            ->assertSee(__('partner.hotel_animal_services.opt_welcome'))
            ->assertSee(__('partner.hotel_animal_services.opt_area'));
    }

    public function test_altro_reveals_a_free_text_field(): void
    {
        Livewire::test(SmartboxIncludedAnimals::class)
            ->assertDontSee(__('partner.hotel_animal_services.other_placeholder'))
            ->set('services', ['altro'])
            ->assertSee(__('partner.hotel_animal_services.other_placeholder'));
    }

    public function test_next_saves_the_animal_services_and_advances_step(): void
    {
        Livewire::test(SmartboxIncludedAnimals::class)
            ->set('services', ['omaggio', 'altro'])
            ->set('other', 'Cuccia in omaggio')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        $this->assertSame(['omaggio', 'altro'], $draft->animal_services);
        $this->assertSame('Cuccia in omaggio', $draft->animal_services_other);
        $this->assertSame(9, $draft->current_step);
    }

    public function test_it_rehydrates_the_saved_animal_services(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 9,
            'animal_services' => ['pet_sitting'],
            'animal_services_other' => '',
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxIncludedAnimals::class)->assertSet('services', ['pet_sitting']);
    }
}
