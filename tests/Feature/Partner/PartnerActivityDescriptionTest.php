<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\ActivityDescription;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_description_field(): void
    {
        $this->get(route('partner.activity.description'))
            ->assertOk()
            ->assertSee(__('partner.activity_description.heading'))
            ->assertSee(__('partner.activity_description.step'))
            ->assertSee(__('partner.activity_description.section'))
            ->assertSee(__('partner.activity_description.chars'))
            ->assertSee(__('partner.activity_description.next'));
    }

    public function test_event_needs_only_the_short_description(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'eventi']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->assertSet('isActivity', false)
            ->assertDontSee(__('partner.activity_description.detailed_label'))
            ->set('description', 'Un evento cinofilo imperdibile.')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['description' => 'Un evento cinofilo imperdibile.', 'current_step' => 4]);
    }

    public function test_activity_also_requires_the_detailed_description(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'attivita']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->assertSet('isActivity', true)
            ->assertSee(__('partner.activity_description.detailed_label'))
            ->set('description', 'Passeggiata guidata.')
            ->call('next')
            ->assertHasErrors('detailedDescription')
            ->set('detailedDescription', 'Percorso di 3 ore tra i boschi con soste.')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['detailed_description' => 'Percorso di 3 ore tra i boschi con soste.']);
    }

    public function test_rejects_a_description_over_200_chars(): void
    {
        Livewire::test(ActivityDescription::class)
            ->set('description', str_repeat('a', 201))
            ->call('next')
            ->assertHasErrors('description');
    }
}
