<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\CreateService;
use App\Livewire\Partner\Smartbox\SmartboxType;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_three_types(): void
    {
        $this->get(route('partner.smartbox.type'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_type.heading'))
            ->assertSee(__('partner.smartbox_type.step'))
            ->assertSee(__('partner.smartbox_type.soggiorno'))
            ->assertSee(__('partner.smartbox_type.benessere'))
            ->assertSee(__('partner.smartbox_type.avventura'))
            ->assertSee(__('partner.smartbox_type.next'));
    }

    public function test_crea_servizio_routes_smartbox_here(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'smartbox')
            ->call('next')
            ->assertRedirect(route('partner.smartbox.type'));
    }

    public function test_next_rejects_an_unknown_type(): void
    {
        Livewire::test(SmartboxType::class)
            ->set('type', 'lusso')
            ->call('next')
            ->assertHasErrors('type');
    }

    public function test_next_saves_the_type(): void
    {
        Livewire::test(SmartboxType::class)
            ->set('type', 'benessere')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.name'));

        $this->assertDatabaseHas('structure_drafts', ['type' => 'benessere', 'current_step' => 1]);
    }

    public function test_it_rehydrates_the_saved_type(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 1, 'type' => 'avventura']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxType::class)->assertSet('type', 'avventura');
    }
}
