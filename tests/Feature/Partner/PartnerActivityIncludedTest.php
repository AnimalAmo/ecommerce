<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\ActivityIncluded;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityIncludedTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_sections(): void
    {
        $this->get(route('partner.activity.included'))
            ->assertOk()
            ->assertSee(__('partner.activity_included.heading'))
            ->assertSee(__('partner.activity_included.step'))
            ->assertSee(__('partner.hotel_services.svc_ac'))
            ->assertSee(__('partner.hotel_services.additional_heading'))
            ->assertSee(__('partner.hotel_services.rules_heading'));
    }

    public function test_selections_save_and_advance(): void
    {
        Livewire::test(ActivityIncluded::class)
            ->set('services', ['wifi', 'piscina'])
            ->set('additional', ['colazione'])
            ->set('rules', ['vietato_fumare'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.animal-services'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 6]);
    }

    public function test_altro_reveals_the_detail_textarea(): void
    {
        Livewire::test(ActivityIncluded::class)
            ->assertDontSee(__('partner.hotel_services.other_placeholder'))
            ->set('additional', ['altro'])
            ->assertSee(__('partner.hotel_services.other_placeholder'));
    }

    public function test_it_rehydrates_the_saved_services(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 6, 'services' => ['wifi']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityIncluded::class)->assertSet('services', ['wifi']);
    }
}
