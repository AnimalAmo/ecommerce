<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxDuration;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_days_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.smartbox.duration'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_duration.heading'))
            ->assertSee(__('partner.smartbox_duration.step'))
            ->assertSee(__('partner.smartbox_duration.field_label'));
    }

    public function test_next_requires_a_positive_number_of_days(): void
    {
        Livewire::test(SmartboxDuration::class)
            ->call('next')
            ->assertHasErrors('durationDays');

        Livewire::test(SmartboxDuration::class)
            ->set('durationDays', 0)
            ->call('next')
            ->assertHasErrors('durationDays');
    }

    public function test_next_saves_the_duration_and_advances(): void
    {
        Livewire::test(SmartboxDuration::class)
            ->set('durationDays', 3)
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.cancellation'));

        $this->assertDatabaseHas('structure_drafts', ['duration_days' => 3, 'current_step' => 4]);
    }

    public function test_it_rehydrates_the_saved_duration(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'duration_days' => 7]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxDuration::class)->assertSet('durationDays', 7);
    }
}
