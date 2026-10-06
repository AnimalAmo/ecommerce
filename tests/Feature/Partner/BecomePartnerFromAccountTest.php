<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Livewire\Partner\Registration\WorkWithUs;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Richiesta "diventa partner" partendo da un account ecommerce già registrato:
 * la candidatura resta legata all'utente e l'iscrizione B2B promuove quel
 * profilo invece di crearne uno nuovo (l'email sarebbe già presa).
 */
class BecomePartnerFromAccountTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $attributes = []): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(array_merge([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia.rossi@example.com',
            'phone' => '3498798828',
            'city' => 'Padova',
            'is_active' => true,
        ], $attributes));

        $user->assignRole('client');

        return $user;
    }

    private function fillApplication($component)
    {
        return $component
            ->set('form.city', 'Padova')
            ->set('form.businessName', 'B&B Le Palme')
            ->set('form.role', 'Titolare')
            ->set('form.offerType', 'Struttura ricettiva')
            ->set('form.description', 'B&B pet friendly sui colli.');
    }

    /** Richiesta aperta di un cliente, come quelle nate prima dell'iscrizione diretta. */
    private function openRequest(User $user, array $attributes = []): PartnerApplication
    {
        return PartnerApplication::create(array_merge([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'city' => 'Padova',
            'business_name' => 'B&B Le Palme',
            'role' => 'Titolare',
            'offer_type' => 'Struttura ricettiva',
            'description' => 'B&B pet friendly sui colli.',
            'status' => PartnerApplication::STATUS_INVITED,
        ], $attributes));
    }

    private function step1Data(User $user): array
    {
        return [
            'firstName' => 'Giulia',
            'lastName' => 'Rossi',
            'businessName' => 'B&B Le Palme',
            'email' => $user->email,
            'address' => 'Via Roma 1',
            'province' => 'PD',
            'zip' => '35100',
            'phone' => '3498798828',
            'vat' => '86334519757',
            'taxCode' => 'RSSGLI90A41G224X',
        ];
    }

    public function test_the_form_arrives_prefilled_with_the_account_data(): void
    {
        $user = $this->client();

        Livewire::actingAs($user)
            ->test(WorkWithUs::class)
            ->assertSet('form.firstName', 'Giulia')
            ->assertSet('form.lastName', 'Rossi')
            ->assertSet('form.email', 'giulia.rossi@example.com')
            ->assertSet('form.city', 'Padova');
    }

    /**
     * Iscrizione diretta (cliente, 06/10/2026): il cliente loggato diventa
     * partner subito, con il suo account e la sua password. Niente mail.
     */
    public function test_a_logged_in_client_becomes_partner_straight_away(): void
    {
        Mail::fake();
        $user = $this->client(['password' => 'la-sua-password']);

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertSame(1, User::count());

        $user->refresh();
        $this->assertTrue($user->hasRole('partner'));
        $this->assertTrue($user->hasRole('client'), 'Resta cliente: carrello, ordini e preferiti sono ancora suoi.');
        $this->assertTrue(Hash::check('la-sua-password', $user->password));
        $this->assertSame('B&B Le Palme', $user->partnerProfile->business_name);

        $application = PartnerApplication::sole();
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->status);

        Mail::assertNothingOutgoing();
    }

    /** Un profilo rimasto da prima tiene i dati che l'iscrizione diretta non chiede. */
    public function test_the_signup_keeps_the_address_of_an_existing_profile(): void
    {
        $user = $this->client();
        $user->partnerProfile()->create(['address' => 'Via Roma 1', 'province' => 'PD', 'zip' => '35100', 'vat' => '86334519757']);

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit')->assertHasNoErrors();

        $profile = $user->fresh()->partnerProfile;
        $this->assertSame('Via Roma 1', $profile->address);
        $this->assertSame('PD', $profile->province);
        $this->assertSame('35100', $profile->zip);
        $this->assertSame('86334519757', $profile->vat);
    }

    public function test_an_old_open_request_is_closed_by_the_new_signup(): void
    {
        $user = $this->client();
        $this->openRequest($user);

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->set('form.businessName', 'Agriturismo Le Palme')
            ->call('submit')
            ->assertHasNoErrors();

        $application = PartnerApplication::sole();
        $this->assertSame('Agriturismo Le Palme', $application->business_name);
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->status);
    }

    public function test_a_disabled_account_cannot_become_partner(): void
    {
        $user = $this->client(['is_active' => false]);

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->call('submit')
            ->assertHasErrors('form.email');

        $this->assertFalse($user->fresh()->hasRole('partner'));
        $this->assertSame(0, PartnerApplication::count());
    }

    public function test_the_email_cannot_be_moved_off_the_account(): void
    {
        Mail::fake();
        $user = $this->client();

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->set('form.email', 'altro@example.com')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($user->email, PartnerApplication::firstOrFail()->email);
    }

    /**
     * mount() gira una volta sola: se la sessione cambia mentre il form è
     * aperto, il prefill resta quello del vecchio account. L'email era già
     * forzata sull'account, il nome no — e la mail di invito arrivava
     * all'indirizzo giusto salutando l'altra persona.
     */
    public function test_the_name_cannot_survive_a_session_change(): void
    {
        Mail::fake();

        $demo = $this->client(['first_name' => 'Susanna', 'last_name' => 'Bianchi', 'email' => 'demo@example.com']);
        $real = $this->client(['first_name' => 'Matteo', 'last_name' => 'De Prezzo', 'email' => 'matteo@example.com']);

        $component = $this->fillApplication(Livewire::actingAs($demo)->test(WorkWithUs::class))
            ->assertSet('form.firstName', 'Susanna');

        // Stessa tab, account diverso: il componente non rifà mount().
        $this->actingAs($real);

        $component->call('submit')->assertHasNoErrors();

        $application = PartnerApplication::firstOrFail();

        $this->assertSame('matteo@example.com', $application->email);
        $this->assertSame('Matteo', $application->first_name);
        $this->assertSame('De Prezzo', $application->last_name);
        $this->assertSame($real->id, $application->user_id);
    }

    /** Il nome nella mail è quello dell'account che la riceve, sempre. */
    public function test_the_invitation_greets_the_account_holder(): void
    {
        Mail::fake();

        $user = $this->client(['first_name' => 'Matteo', 'last_name' => 'De Prezzo']);

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->set('form.firstName', 'Susanna')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Matteo', PartnerApplication::firstOrFail()->first_name);
    }

    public function test_step_1_prefills_from_the_open_request_without_a_signed_link(): void
    {
        Mail::fake();
        $user = $this->client();
        $this->openRequest($user);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep1::class)
            ->assertSet('emailLocked', true)
            ->assertSet('form.businessName', 'B&B Le Palme')
            ->assertSet('form.email', $user->email);
    }

    public function test_step_1_ignores_the_signed_link_of_another_users_request(): void
    {
        Mail::fake();
        $other = $this->client(['email' => 'mario@example.com']);
        $this->openRequest($other, ['business_name' => 'Hotel Rosovino']);

        $user = $this->client();
        $link = URL::signedRoute('partner.register', ['application' => PartnerApplication::firstOrFail()->id]);

        $this->actingAs($user)
            ->get($link)
            ->assertOk()
            ->assertDontSee('Hotel Rosovino');
    }

    public function test_step_2_promotes_the_account_instead_of_creating_a_new_user(): void
    {
        Mail::fake();
        $user = $this->client();
        $this->openRequest($user);

        Livewire::actingAs($user)->test(PartnerRegisterStep1::class)
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.vat', '86334519757')
            ->set('form.taxCode', 'RSSGLI90A41G224X')
            ->call('submit')
            ->assertRedirect(route('partner.register.step2'));

        Livewire::actingAs($user)->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertSame(1, User::count());

        $user->refresh();
        $this->assertTrue($user->hasRole('partner'));
        // Resta cliente: carrello, ordini e preferiti sono ancora suoi.
        $this->assertTrue($user->hasRole('client'));
        $this->assertSame('B&B Le Palme', $user->partnerProfile->business_name);
        $this->assertSame('86334519757', $user->partnerProfile->vat);

        $application = PartnerApplication::firstOrFail();
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->status);
        $this->assertSame($user->id, $application->user_id);
    }

    public function test_the_promoted_partner_reaches_the_reserved_area(): void
    {
        $user = $this->client();
        session(['partner_registration.step1' => $this->step1Data($user)]);

        Livewire::actingAs($user)->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors();

        $this->actingAs($user->refresh())
            ->get(route('partner.dashboard'))
            ->assertOk();
    }

    public function test_a_stale_registration_session_cannot_promote_another_account(): void
    {
        $user = $this->client();
        $victim = $this->client(['email' => 'vittima@example.com']);

        // Sessione avviata da sloggati con l'email di un altro account.
        session(['partner_registration.step1' => $this->step1Data($victim)]);

        Livewire::actingAs($user)->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors();

        $this->assertTrue($user->refresh()->hasRole('partner'));
        $this->assertFalse($victim->refresh()->hasRole('partner'));
        $this->assertSame(2, User::count());
    }

    public function test_the_profile_menu_offers_the_partner_request(): void
    {
        $user = $this->client();

        $this->actingAs($user)
            ->get(route('profilo'))
            ->assertOk()
            ->assertSee(__('profile.nav_become_partner'))
            ->assertSee(route('work-with-us'));
    }

    public function test_the_profile_menu_tracks_the_open_request(): void
    {
        Mail::fake();
        $user = $this->client();
        $this->openRequest($user);

        $this->actingAs($user)
            ->get(route('profilo'))
            ->assertOk()
            ->assertSee(__('profile.nav_partner_request_sent'))
            ->assertSee(route('partner.register'));
    }

    public function test_the_profile_menu_sends_partners_to_their_area(): void
    {
        $user = $this->client();
        $user->assignRole('partner');

        $this->actingAs($user)
            ->get(route('profilo'))
            ->assertOk()
            ->assertSee(__('profile.nav_partner_area'))
            ->assertDontSee(__('profile.nav_become_partner'));
    }

    public function test_the_thanks_page_lets_a_logged_in_user_continue(): void
    {
        Mail::fake();
        $user = $this->client();
        $this->openRequest($user);

        $this->actingAs($user)
            ->get(route('work-with-us.thanks'))
            ->assertOk()
            ->assertSee(__('partner.thanks_continue'));
    }

    public function test_the_thanks_page_only_sends_a_visitor_back_home(): void
    {
        // Il visitatore aspetta il link firmato dell'email: niente scorciatoia.
        $this->get(route('work-with-us.thanks'))
            ->assertOk()
            ->assertSee(__('partner.back_home'))
            ->assertDontSee(__('partner.thanks_continue'));
    }
}
