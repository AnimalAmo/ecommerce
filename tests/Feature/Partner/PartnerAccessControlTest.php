<?php

namespace Tests\Feature\Partner;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_home_from_the_partner_area(): void
    {
        $this->get(route('partner.dashboard'))->assertRedirect(route('home'));
        $this->get(route('partner.profile'))->assertRedirect(route('home'));
    }

    public function test_a_client_cannot_access_the_partner_area(): void
    {
        Role::findOrCreate('client', 'web');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('partner.dashboard'))->assertForbidden();
        $this->actingAs($client)->get(route('partner.profile'))->assertForbidden();
    }

    public function test_a_deactivated_partner_cannot_access_the_partner_area(): void
    {
        $this->actingAsActivePartner(['is_active' => false]);

        $this->get(route('partner.dashboard'))->assertForbidden();
        $this->get(route('partner.profile'))->assertForbidden();
    }

    public function test_an_active_partner_can_access_the_partner_area(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.dashboard'))->assertOk();
        $this->get(route('partner.profile'))->assertOk();
        $this->get(route('partner.profile.payment'))->assertOk();
        $this->get(route('partner.profile.security'))->assertOk();
    }

    /**
     * L'iscrizione resta pubblica — è il punto d'ingresso del partner — ma gli
     * step del wizard no: da ospite creavano bozze con user_id null e
     * accettavano foto. L'elenco completo degli step lo verifica WizardAuthTest
     * leggendolo dal router; qui restano le tre teste come sentinella.
     */
    public function test_the_signup_is_public_but_the_wizard_is_not(): void
    {
        $this->get(route('partner.register'))->assertOk();

        $this->get(route('partner.structure.type'))->assertRedirect(route('home'));
        $this->get(route('partner.activity.type'))->assertRedirect(route('home'));
        $this->get(route('partner.smartbox.type'))->assertRedirect(route('home'));
    }
}
