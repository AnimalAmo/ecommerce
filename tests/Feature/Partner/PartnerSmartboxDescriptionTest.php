<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxDescription;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_two_description_fields(): void
    {
        $this->get(route('partner.smartbox.description'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_description.heading'))
            ->assertSee(__('partner.smartbox_description.step'))
            ->assertSee(__('partner.smartbox_description.section'))
            ->assertSee(__('partner.smartbox_description.detailed_label'));
    }

    public function test_next_requires_both_descriptions(): void
    {
        Livewire::test(SmartboxDescription::class)
            ->set('description', '')
            ->set('detailedDescription', '')
            ->call('next')
            ->assertHasErrors(['description', 'detailedDescription']);
    }

    public function test_next_saves_both_descriptions_and_advances(): void
    {
        Livewire::test(SmartboxDescription::class)
            ->set('description', 'Un weekend di coccole per te e il tuo cane.')
            ->set('detailedDescription', 'Due notti in una struttura pet-friendly con colazione.')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.duration'));

        $this->assertDatabaseHas('structure_drafts', [
            'description' => 'Un weekend di coccole per te e il tuo cane.',
            'detailed_description' => 'Due notti in una struttura pet-friendly con colazione.',
            'current_step' => 3,
        ]);
    }

    public function test_it_rehydrates_the_saved_descriptions(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 3,
            'description' => 'Breve',
            'detailed_description' => 'Dettagliata',
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxDescription::class)
            ->assertSet('description', 'Breve')
            ->assertSet('detailedDescription', 'Dettagliata');
    }
}
