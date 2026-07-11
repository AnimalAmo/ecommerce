<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerMyServicesTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $userId, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'name' => 'Hotel Brescia',
            'city' => 'Darfo Boario Terme',
            'province' => 'BS',
        ], $attributes));
    }

    public function test_it_lists_only_the_partners_completed_services(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, ['name' => 'Hotel Brescia']);
        $this->service($partner->id, ['name' => 'Puppy Yoga', 'status' => StructureDraft::STATUS_DRAFT]); // draft: hidden
        $this->service(User::factory()->create()->id, ['name' => 'Altrui Resort']);                       // altro utente: hidden

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel Brescia')
            ->assertSee('Darfo Boario Terme (BS), Italia')
            ->assertSee(__('partner.services.view_details'))
            ->assertDontSee('Puppy Yoga')
            ->assertDontSee('Altrui Resort');
    }

    public function test_empty_state_when_no_services(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerMyServices::class)->assertSee(__('partner.services.empty'));
    }

    public function test_page_is_reachable_by_an_active_partner(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.services'))->assertOk()->assertSee(__('partner.services.heading'));
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('partner.services'))->assertRedirect(route('home'));
    }

    public function test_edit_loads_the_draft_and_returns_to_the_structure_flow(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, ['type' => 'hotel']);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.structure.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    public function test_edit_routes_each_family_to_its_own_flow(): void
    {
        $partner = $this->actingAsActivePartner();
        $activity = $this->service($partner->id, ['service_category' => 'attivita', 'type' => 'attivita', 'current_step' => 10]);
        $smartbox = $this->service($partner->id, ['service_category' => 'smartbox', 'type' => 'soggiorno', 'current_step' => 12]);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $activity->id)
            ->assertRedirect(route('partner.activity.type'));

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $smartbox->id)
            ->assertRedirect(route('partner.smartbox.type'));
    }

    public function test_edit_ignores_services_of_other_partners(): void
    {
        $this->actingAsActivePartner();
        $other = User::factory()->create();
        $foreign = $this->service($other->id);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $foreign->id)
            ->assertNoRedirect();

        $this->assertNull(session('structure_draft_id'));
    }
}
