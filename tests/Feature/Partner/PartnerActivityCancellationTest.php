<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityCancellation;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_cancellation_timeline(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsPayablePartner();

        $this->get(route('partner.activity.cancellation'))
            ->assertOk()
            ->assertSee(__('partner.hotel_cancellation.heading'))
            ->assertSee(__('partner.activity_cancellation.step'))
            ->assertSee(__('partner.hotel_cancellation.free'))
            ->assertSee(__('partner.hotel_cancellation.pays'))
            ->assertSee(__('partner.hotel_cancellation.arrival'))
            ->assertSee(__('partner.activity_cancellation.save'));
    }

    public function test_rejects_an_invalid_window(): void
    {
        Livewire::test(ActivityCancellation::class)
            ->set('when', '99')
            ->call('next')
            ->assertHasErrors('when');
    }

    public function test_accepts_a_valid_window_and_completes_the_draft(): void
    {
        // La bozza va a catalogo solo se il partner può essere pagato.
        $this->actingAsPayablePartner();

        Livewire::test(ActivityCancellation::class)
            ->set('when', '7')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertDatabaseHas('structure_drafts', [
            'cancellation_when' => '7',
            'status' => 'completed',
            // completeDraft() closes the onboarding at current_step 11 (shared concern).
            'current_step' => 11,
        ]);
    }

    public function test_it_rehydrates_the_saved_window(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 10, 'cancellation_when' => '30']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityCancellation::class)->assertSet('when', '30');
    }
}
