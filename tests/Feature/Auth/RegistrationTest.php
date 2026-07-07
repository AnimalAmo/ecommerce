<?php

namespace Tests\Feature\Auth;

use App\Livewire\RegisterModal;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_cannot_advance_with_invalid_data(): void
    {
        Livewire::test(RegisterModal::class)
            ->call('next')
            ->assertHasErrors([
                'form.firstName' => 'required',
                'form.lastName' => 'required',
                'form.birthDate' => 'required',
                'form.email' => 'required',
            ])
            ->assertSet('step', 1);
    }

    public function test_mismatched_passwords_block_step_two(): void
    {
        Livewire::test(RegisterModal::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Verdi')
            ->set('form.birthDate', '1990-05-10')
            ->set('form.email', 'mario.verdi@example.com')
            ->call('next')
            ->assertSet('step', 2)
            ->set('form.phone', '3331234567')
            ->set('form.password', 'password123')
            ->set('form.passwordConfirmation', 'diversa456')
            ->call('next')
            ->assertHasErrors(['form.passwordConfirmation' => 'same'])
            ->assertSet('step', 2)
            ->assertSee('Le password non coincidono');
    }

    public function test_users_can_register_through_the_four_steps(): void
    {
        $this->seed(RoleSeeder::class);

        Event::fake([Registered::class]);

        Livewire::test(RegisterModal::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Verdi')
            ->set('form.birthDate', '1990-05-10')
            ->set('form.email', 'mario.verdi@example.com')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->set('form.phone', '3331234567')
            ->set('form.password', 'password123')
            ->set('form.passwordConfirmation', 'password123')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 3)
            ->set('form.address', 'Via Roma 1')
            ->set('form.city', 'Milano')
            ->set('form.postalCode', '20100')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 4)
            ->set('form.petType', 'Cane')
            ->set('form.newsletter', true)
            ->set('form.privacyConsent', true)
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'mario.verdi@example.com')->firstOrFail();

        $this->assertSame('Mario Verdi', $user->name);
        $this->assertSame('1990-05-10', $user->birth_date->toDateString());
        $this->assertTrue($user->newsletter);
        $this->assertTrue($user->marketing_consent);
        $this->assertTrue($user->hasRole('client'));
        $this->assertSame('Cane', $user->pets()->sole()->species);

        Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user->is($user));

        $this->assertAuthenticatedAs($user);
    }

    public function test_marketing_consent_is_optional_and_persisted_as_declined(): void
    {
        // Consenso marketing ("promozioni esclusive") facoltativo per GDPR: la
        // registrazione va a buon fine anche senza spunta e salva il rifiuto.
        Livewire::test(RegisterModal::class, ['step' => 4])
            ->set('form.firstName', 'Anna')
            ->set('form.lastName', 'Bianchi')
            ->set('form.birthDate', '1985-01-15')
            ->set('form.email', 'anna.bianchi@example.com')
            ->set('form.phone', '3399876543')
            ->set('form.password', 'password123')
            ->set('form.passwordConfirmation', 'password123')
            ->set('form.address', 'Via Milano 2')
            ->set('form.city', 'Brescia')
            ->set('form.postalCode', '25121')
            ->set('form.petType', 'Gatto')
            ->set('form.privacyConsent', false)
            ->call('next')
            ->assertHasNoErrors();

        $user = User::where('email', 'anna.bianchi@example.com')->firstOrFail();
        $this->assertFalse($user->marketing_consent);
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_is_blocked_at_step_one(): void
    {
        User::factory()->create(['email' => 'giulia.rossi@example.com']);

        Livewire::test(RegisterModal::class)
            ->set('form.firstName', 'Giulia')
            ->set('form.lastName', 'Rossi')
            ->set('form.birthDate', '1998-03-22')
            ->set('form.email', 'giulia.rossi@example.com')
            ->call('next')
            ->assertHasErrors(['form.email' => 'unique'])
            ->assertSet('step', 1)
            ->assertSee('Questa email è già registrata');

        $this->assertGuest();
    }
}
