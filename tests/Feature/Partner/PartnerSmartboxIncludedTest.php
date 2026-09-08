<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxIncluded;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxIncludedTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_service_options(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.smartbox.included'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_included.heading'))
            ->assertSee(__('partner.smartbox_included.step'))
            ->assertSee(__('partner.hotel_services.svc_wifi'))
            ->assertSee(__('partner.hotel_services.svc_sauna'));
    }

    public function test_next_saves_the_selection_and_advances(): void
    {
        Livewire::test(SmartboxIncluded::class)
            ->set('included', ['wifi', 'tv', 'piscina'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.included-animals'));

        $draft = StructureDraft::first();
        $this->assertSame(['wifi', 'tv', 'piscina'], $draft->included_services);
        $this->assertSame(8, $draft->current_step);
    }

    public function test_it_rehydrates_the_saved_selection(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 8,
            'included_services' => ['aria_condizionata', 'sauna'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxIncluded::class)->assertSet('included', ['aria_condizionata', 'sauna']);
    }
}
