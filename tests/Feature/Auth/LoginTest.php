<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\AuthModal;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_from_the_login_modal(): void
    {
        $user = User::factory()->create();

        Livewire::test(AuthModal::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Vale anche dalla modale cliente: chi ha il ruolo partner ha un'area sua,
     * e lasciarlo sulla pagina pubblica di partenza sembrava un login fallito.
     */
    public function test_a_partner_logging_in_from_the_client_modal_lands_in_the_dashboard(): void
    {
        $this->seed(RoleSeeder::class);

        $partner = User::factory()->create(['is_active' => true]);
        $partner->assignRole('partner');

        Livewire::test(AuthModal::class)
            ->set('form.email', $partner->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('partner.dashboard'));
    }

    /** Il cliente resta dov'era: la dashboard partner gli risponderebbe 403. */
    public function test_a_client_stays_on_the_page_they_came_from(): void
    {
        $this->seed(RoleSeeder::class);

        $client = User::factory()->create();
        $client->assignRole('client');

        Livewire::test(AuthModal::class)
            ->set('form.email', $client->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('home'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        Livewire::test(AuthModal::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'password-sbagliata')
            ->call('login')
            ->assertHasErrors(['form.email'])
            ->assertSee(trans('auth.failed'));

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        Livewire::test(AuthModal::class)
            ->call('login')
            ->assertHasErrors(['form.email' => 'required', 'form.password' => 'required']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        $component = Livewire::test(AuthModal::class)
            ->set('form.email', $user->email)
            ->set('form.password', 'password-sbagliata');

        foreach (range(1, 5) as $attempt) {
            $component->call('login');
        }

        // Sesto tentativo: scatta il throttle, anche con la password giusta.
        $component
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email'])
            ->assertSee('Troppi tentativi di accesso');

        $this->assertGuest();
    }
}
