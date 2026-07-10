<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityName;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_name_field(): void
    {
        $this->get(route('partner.activity.name'))
            ->assertOk()
            ->assertSee(__('partner.activity_name.heading'))
            ->assertSee(__('partner.activity_name.step'))
            ->assertSee(__('partner.activity_name.field_label'))
            ->assertSee(__('partner.activity_name.next'));
    }

    public function test_next_requires_a_name(): void
    {
        Livewire::test(ActivityName::class)
            ->call('next')
            ->assertHasErrors('name');
    }

    public function test_next_saves_the_name(): void
    {
        Livewire::test(ActivityName::class)
            ->set('name', 'Passeggiata coi cani')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.location'));

        $this->assertDatabaseHas('structure_drafts', ['name' => 'Passeggiata coi cani', 'current_step' => 2]);
    }

    public function test_it_rehydrates_the_saved_name(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => 'Gita al lago']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityName::class)->assertSet('name', 'Gita al lago');
    }
}
