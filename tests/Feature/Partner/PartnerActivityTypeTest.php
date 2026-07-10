<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\ActivityType;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_two_types(): void
    {
        $this->get(route('partner.activity.type'))
            ->assertOk()
            ->assertSee(__('partner.activity_type.step'))
            ->assertSee(__('partner.activity_type.attivita'))
            ->assertSee(__('partner.activity_type.eventi'))
            ->assertSee(__('partner.activity_type.next'));
    }

    public function test_next_rejects_an_unknown_type(): void
    {
        Livewire::test(ActivityType::class)
            ->set('type', 'concerto')
            ->call('next')
            ->assertHasErrors('type');
    }

    public function test_next_saves_the_type(): void
    {
        Livewire::test(ActivityType::class)
            ->set('type', 'eventi')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['type' => 'eventi', 'current_step' => 1]);
    }

    public function test_it_rehydrates_the_saved_type(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 1, 'type' => 'attivita']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityType::class)->assertSet('type', 'attivita');
    }

    public function test_it_ignores_a_structure_type_value(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 1, 'type' => 'hotel']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityType::class)->assertSet('type', '');
    }
}
