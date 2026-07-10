<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\SmartboxName;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_title_field(): void
    {
        $this->get(route('partner.smartbox.name'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_name.heading'))
            ->assertSee(__('partner.smartbox_name.step'))
            ->assertSee(__('partner.smartbox_name.field_label'))
            ->assertSee(__('partner.smartbox_name.next'));
    }

    public function test_next_requires_a_title(): void
    {
        Livewire::test(SmartboxName::class)
            ->call('next')
            ->assertHasErrors('name');
    }

    public function test_next_saves_the_title(): void
    {
        Livewire::test(SmartboxName::class)
            ->set('name', 'Weekend di coccole')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.description'));

        $this->assertDatabaseHas('structure_drafts', ['name' => 'Weekend di coccole', 'current_step' => 2]);
    }

    public function test_it_rehydrates_the_saved_title(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => 'Fuga romantica']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxName::class)->assertSet('name', 'Fuga romantica');
    }
}
