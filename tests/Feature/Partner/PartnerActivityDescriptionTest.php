<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityDescription;
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
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
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
            ->set('description.it', 'Un evento cinofilo imperdibile.')
            ->set('description.en', 'An unmissable dog event.')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.info'));

        $draft->refresh();
        $this->assertSame('Un evento cinofilo imperdibile.', $draft->getTranslation('description', 'it'));
        $this->assertSame('An unmissable dog event.', $draft->getTranslation('description', 'en'));
        $this->assertSame(4, $draft->current_step);
    }

    public function test_activity_also_requires_the_detailed_description(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'attivita']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->assertSet('isActivity', true)
            ->assertSee(__('partner.activity_description.detailed_label'))
            ->set('description.it', 'Passeggiata guidata.')
            ->call('next')
            ->assertHasErrors('detailedDescription.it')
            ->set('detailedDescription.it', 'Percorso di 3 ore tra i boschi con soste.')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame('Percorso di 3 ore tra i boschi con soste.', $draft->getTranslation('detailed_description', 'it'));
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'eventi']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->set('description.it', 'Un evento cinofilo imperdibile.')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Un evento cinofilo imperdibile.', $draft->getTranslation('description', 'en'));
        $this->assertSame(['it' => 'Un evento cinofilo imperdibile.'], $draft->getTranslations('description'));
    }

    public function test_rejects_a_description_over_200_chars(): void
    {
        Livewire::test(ActivityDescription::class)
            ->set('description.it', str_repeat('a', 201))
            ->call('next')
            ->assertHasErrors('description.it');
    }
}
