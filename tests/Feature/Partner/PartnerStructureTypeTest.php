<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\StructureType;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerStructureTypeTest extends TestCase
{
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

    public function test_next_accepts_a_valid_type(): void
    {
        Livewire::test(StructureType::class)
            ->set('type', 'agriturismo')
            ->call('next')
            ->assertHasNoErrors();
    }
}
