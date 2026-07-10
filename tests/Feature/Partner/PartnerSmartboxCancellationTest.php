<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\SmartboxCancellation;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_timeline(): void
    {
        $this->get(route('partner.smartbox.cancellation'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_cancellation.heading'))
            ->assertSee(__('partner.smartbox_cancellation.step'))
            ->assertSee(__('partner.smartbox_cancellation.free'))
            ->assertSee(__('partner.smartbox_cancellation.pays'));
    }

    public function test_next_rejects_an_unknown_window(): void
    {
        Livewire::test(SmartboxCancellation::class)
            ->set('when', '99')
            ->call('next')
            ->assertHasErrors('when');
    }

    public function test_next_saves_the_window(): void
    {
        Livewire::test(SmartboxCancellation::class)
            ->set('when', '7')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.meals'));

        $this->assertDatabaseHas('structure_drafts', ['cancellation_when' => '7', 'current_step' => 5]);
    }

    public function test_it_rehydrates_the_saved_window(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'cancellation_when' => '30']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxCancellation::class)->assertSet('when', '30');
    }
}
