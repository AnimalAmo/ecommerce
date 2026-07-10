<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\StructureType;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerStructureTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_three_structure_types(): void
    {
        $this->get(route('partner.structure.type'))
            ->assertOk()
            ->assertSee(__('partner.structure_type.step'))
            ->assertSee(__('partner.structure_type.hotel'))
            ->assertSee(__('partner.structure_type.bb'))
            ->assertSee(__('partner.structure_type.agriturismo'))
            ->assertSee(__('partner.structure_type.next'));
    }

    public function test_next_requires_a_type(): void
    {
        Livewire::test(StructureType::class)
            ->call('next')
            ->assertHasErrors('type');
    }

    public function test_next_rejects_an_unknown_type(): void
    {
        Livewire::test(StructureType::class)
            ->set('type', 'castello')
            ->call('next')
            ->assertHasErrors('type');
    }

    public function test_next_saves_the_type_and_advances(): void
    {
        Livewire::test(StructureType::class)
            ->set('type', 'agriturismo')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.title'));

        $this->assertDatabaseHas('structure_drafts', [
            'type' => 'agriturismo',
            'status' => 'draft',
            'current_step' => 1,
        ]);
    }

    public function test_it_rehydrates_the_saved_type(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 1, 'type' => 'bb']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(StructureType::class)->assertSet('type', 'bb');
    }
}
