<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Models\Structure\Structure;
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
            // Pubblicabile: senza stanze il badge direbbe "mancano dei dati",
            // che e' vero ma non e' cio' che queste prove vogliono verificare.
            'rooms' => [['name' => 'Camera doppia', 'guests' => 2]],
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

    public function test_it_lists_the_drafts_awaiting_stripe_with_a_badge(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio in attesa',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);
        $this->service($partner->id, ['name' => 'Hotel Brescia']);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Rifugio in attesa')
            ->assertSee('Hotel Brescia')
            ->assertSeeHtmlInOrder(['Rifugio in attesa', e(__('partner.my_services.awaiting_stripe'))]);
    }

    public function test_a_waiting_draft_missing_data_says_so_instead_of_blaming_stripe(): void
    {
        // La diagnosi falsa che ha generato la segnalazione del 29/09/2026:
        // qualunque bozza ferma leggeva "in attesa del collegamento Stripe".
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio senza stanze',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
            'rooms' => null,
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.incomplete'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_a_ready_draft_of_a_payable_partner_says_it_is_going_live(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio pronto',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.publishing'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_a_listing_waiting_for_approval_says_so(): void
    {
        // Moderazione accesa: la scheda e' a catalogo ma invisibile, e prima
        // l'area partner non aveva modo di dirlo.
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $draft = $this->service($partner->id, ['name' => 'Hotel in moderazione']);
        Structure::factory()->create([
            'user_id' => $partner->id,
            'structure_draft_id' => $draft->id,
            'approval_status' => Structure::APPROVAL_PENDING,
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_approval'));
    }

    public function test_a_published_listing_carries_no_badge(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $draft = $this->service($partner->id, ['name' => 'Hotel online']);
        Structure::factory()->create(['user_id' => $partner->id, 'structure_draft_id' => $draft->id]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel online')
            ->assertDontSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.publishing'))
            ->assertDontSee(__('partner.my_services.awaiting_approval'));
    }

    public function test_the_badge_is_only_on_drafts_awaiting_stripe(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, ['name' => 'Hotel Brescia']);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel Brescia')
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_edit_reopens_a_draft_awaiting_stripe(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'status' => StructureDraft::STATUS_DRAFT,
            'service_category' => 'smartbox',
            'publish_requested_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.smartbox.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
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
