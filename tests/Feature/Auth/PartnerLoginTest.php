<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\PartnerLoginModal;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_partners_can_authenticate_from_the_partner_modal(): void
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        Livewire::test(PartnerLoginModal::class)
            ->set('form.email', $partner->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($partner);
    }

    /**
     * Il login rimandava "dov'eri": aprendo la modale dalla home il partner
     * tornava sulla home, con l'impressione che il login fosse fallito. Un
     * partner attivo ha una sua area, e va lì.
     */
    public function test_an_active_partner_lands_in_the_dashboard(): void
    {
        $partner = User::factory()->create(['is_active' => true]);
        $partner->assignRole('partner');

        Livewire::test(PartnerLoginModal::class)
            ->set('form.email', $partner->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('partner.dashboard'));
    }

    public function test_clients_are_rejected_from_the_partner_modal_and_logged_out(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        Livewire::test(PartnerLoginModal::class)
            ->set('form.email', $client->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email'])
            ->assertSee(trans('auth.failed'));

        $this->assertGuest();
    }
}
