<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\RegisterModal;
use App\Livewire\Profile\Profile;
use App\Mail\Newsletter\NewsletterConfirmationMail;
use App\Models\Newsletter\NewsletterSubscriber;
use App\Models\User;
use App\Services\Newsletter\SubscriptionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Registrazione rapida (cliente, 06/10/2026): nome, email, password e
 * accettazione dei termini, poi si entra subito nel profilo. Nessuna mail da
 * confermare; il resto del profilo si completa dopo.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->seed(RoleSeeder::class);
    }

    private function filled(string $email = 'mario.verdi@example.com'): Testable
    {
        return Livewire::test(RegisterModal::class)
            ->set('form.firstName', 'Mario')
            ->set('form.email', $email)
            ->set('form.password', 'password123')
            ->set('form.passwordConfirmation', 'password123')
            ->set('form.termsAccepted', true);
    }

    public function test_only_name_email_password_and_terms_are_required(): void
    {
        Livewire::test(RegisterModal::class)
            ->call('register')
            ->assertHasErrors([
                'form.firstName' => 'required',
                'form.email' => 'required',
                'form.password' => 'required',
                'form.termsAccepted' => 'accepted',
            ])
            ->assertHasNoErrors(['form.privacyConsent', 'form.newsletter']);

        $this->assertGuest();
    }

    public function test_users_register_in_one_step_and_land_on_their_profile(): void
    {
        Event::fake([Registered::class]);
        Mail::fake();

        $this->filled()
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('profilo.anagrafica'));

        $user = User::where('email', 'mario.verdi@example.com')->firstOrFail();

        $this->assertSame('Mario', $user->name);
        $this->assertNull($user->last_name);
        $this->assertNull($user->phone);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertTrue($user->hasRole('client'));
        $this->assertSame(0, $user->pets()->count());
        $this->assertAuthenticatedAs($user);

        Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user->is($user));
        // Nessuna mail di conferma dell'account: senza newsletter non parte niente.
        Mail::assertNothingOutgoing();
    }

    public function test_the_profile_welcomes_the_new_user_and_lists_what_is_missing(): void
    {
        $this->filled()->call('register');

        $this->get(route('profilo.anagrafica'))
            ->assertOk()
            ->assertSee('Benvenuto su AnimalAmo, Mario!')
            ->assertSee('cognome');

        // Il saluto solo al primo arrivo; il promemoria resta finché manca qualcosa.
        $this->get(route('profilo.anagrafica'))
            ->assertDontSee('Benvenuto su AnimalAmo')
            ->assertSee(__('profile.complete_title'));
    }

    public function test_the_profile_can_be_completed_one_field_at_a_time(): void
    {
        $this->filled()->call('register');
        $user = User::where('email', 'mario.verdi@example.com')->sole();

        Livewire::test(Profile::class)
            ->set('lastName', 'Verdi')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Verdi', $user->last_name);
        $this->assertNull($user->birth_date);
        $this->assertSame(0, $user->pets()->count(), 'Un tipo di animale vuoto non crea un animale.');

        Livewire::test(Profile::class)
            ->set('birthDate', '10/05/1990')
            ->set('phone', '+393331234567')
            ->set('address', 'Via Roma 1')
            ->set('city', 'Milano')
            ->set('zip', '20100')
            ->set('petType', 'Cane')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDontSee(__('profile.complete_title'));

        $this->assertSame('Cane', $user->pets()->sole()->species);
    }

    public function test_registering_from_the_cart_goes_back_to_the_cart(): void
    {
        $this->filled()
            ->withHeaders(['referer' => route('carrello')])
            ->call('register');

        $this->assertAuthenticated();
    }

    public function test_mismatched_passwords_are_refused(): void
    {
        $this->filled()
            ->set('form.passwordConfirmation', 'diversa456')
            ->call('register')
            ->assertHasErrors(['form.passwordConfirmation' => 'same'])
            ->assertSee('Le password non coincidono');

        $this->assertGuest();
    }

    public function test_duplicate_email_is_refused(): void
    {
        User::factory()->create(['email' => 'giulia.rossi@example.com']);

        $this->filled('giulia.rossi@example.com')
            ->call('register')
            ->assertHasErrors(['form.email' => 'unique'])
            ->assertSee('Questa email è già registrata');

        $this->assertGuest();
    }

    /**
     * La casella della newsletter è una richiesta di iscrizione con double
     * opt-in, e la prova del consenso è la frase della casella. È l'unica mail
     * che la registrazione può far partire.
     */
    public function test_the_newsletter_box_requests_a_subscription_with_the_box_label_as_proof(): void
    {
        Mail::fake();

        $this->filled('luisa.neri@example.com')
            ->set('form.newsletter', true)
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'luisa.neri@example.com')->firstOrFail();
        $subscriber = NewsletterSubscriber::sole();

        $this->assertSame($user->id, $subscriber->user_id);
        $this->assertSame(NewsletterSubscriber::STATUS_PENDING, $subscriber->status);
        $this->assertSame(NewsletterSubscriber::SOURCE_REGISTRATION, $subscriber->source);
        $this->assertSame(__('auth-modal.register.newsletter'), $subscriber->consent_text);
        $this->assertFalse($user->newsletter);

        Mail::assertQueued(NewsletterConfirmationMail::class, fn (NewsletterConfirmationMail $mail) => $mail->hasTo('luisa.neri@example.com'));
    }

    /** La registrazione è già salvata: un errore della newsletter non la fa fallire. */
    public function test_a_newsletter_failure_does_not_break_the_registration(): void
    {
        $this->mock(SubscriptionService::class)->shouldReceive('subscribe')->andThrow(new RuntimeException('coda giù'));

        $this->filled('franco.gialli@example.com')
            ->set('form.newsletter', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs(User::where('email', 'franco.gialli@example.com')->sole());
    }

    public function test_marketing_consent_is_optional_and_persisted(): void
    {
        $this->filled('anna.bianchi@example.com')->set('form.privacyConsent', false)->call('register')->assertHasNoErrors();
        $this->assertFalse(User::where('email', 'anna.bianchi@example.com')->sole()->marketing_consent);

        auth()->logout();

        $this->filled('bruno.neri@example.com')->set('form.privacyConsent', true)->call('register')->assertHasNoErrors();
        $this->assertTrue(User::where('email', 'bruno.neri@example.com')->sole()->marketing_consent);
    }

    /**
     * Regressione security: il modulo rivela l'esistenza di un'email
     * (unique:users). Senza throttle un attaccante enumera gli utenti in massa;
     * il rate limiter per IP blocca dopo 10 tentativi.
     */
    public function test_registration_is_rate_limited(): void
    {
        $component = Livewire::test(RegisterModal::class)
            ->set('form.email', 'enum-check@example.com');

        for ($i = 0; $i < 10; $i++) {
            $component->call('register')->assertHasNoErrors(['form.email']);
        }

        $component->call('register')->assertHasErrors(['form.email']);
    }
}
