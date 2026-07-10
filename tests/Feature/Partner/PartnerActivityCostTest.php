<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityCost;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_cost_options(): void
    {
        $this->get(route('partner.activity.cost'))
            ->assertOk()
            ->assertSee(__('partner.activity_cost.heading'))
            ->assertSee(__('partner.activity_cost.step'))
            ->assertSee(__('partner.activity_cost.opt_paid'))
            ->assertSee(__('partner.activity_cost.opt_free'))
            ->assertSee(__('partner.activity_cost.next'));
    }

    public function test_requires_a_cost_option(): void
    {
        Livewire::test(ActivityCost::class)
            ->call('next')
            ->assertHasErrors('costType');
    }

    public function test_paid_reveals_and_requires_the_price(): void
    {
        Livewire::test(ActivityCost::class)
            ->assertDontSee(__('partner.activity_cost.price_label'))
            ->set('costType', 'pagamento')
            ->assertSee(__('partner.activity_cost.price_label'))
            ->call('next')
            ->assertHasErrors('pricePerPerson')
            ->set('pricePerPerson', '25')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.photos'));

        $this->assertDatabaseHas('structure_drafts', [
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'current_step' => 8,
        ]);
    }

    public function test_free_needs_no_price(): void
    {
        Livewire::test(ActivityCost::class)
            ->set('costType', 'gratuito')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.photos'));

        $this->assertDatabaseHas('structure_drafts', ['price_type' => 'gratuito', 'price_per_person' => null]);
    }

    public function test_it_rehydrates_the_saved_cost(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 8, 'price_type' => 'pagamento', 'price_per_person' => '40']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityCost::class)
            ->assertSet('costType', 'pagamento')
            ->assertSet('pricePerPerson', '40');
    }
}
