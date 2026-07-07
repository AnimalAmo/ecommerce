<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** Le rotte profilo senza parametri, tutte protette dal middleware auth. */
    private const PROTECTED_ROUTES = [
        'profilo',
        'profilo.pagamento',
        'profilo.sicurezza',
        'profilo.ordini',
        'profilo.eventi',
    ];

    public function test_guests_are_redirected_to_home(): void
    {
        foreach (self::PROTECTED_ROUTES as $route) {
            $this->get(route($route))->assertRedirect(route('home'));
        }

        $this->get(route('profilo.ordini.riepilogo', ['order' => 1]))->assertRedirect(route('home'));
    }

    public function test_authenticated_users_can_view_the_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profilo'))->assertOk();
    }
}
