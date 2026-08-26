<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Livewire\Partner\Registration\WorkWithUs;
use App\Mail\PartnerInvitationMail;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'pec' => 'lepalme@pec.it',
            'sdi' => 'SUBM70N',
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

    public function test_the_request_stays_linked_to_the_account(): void
    {
        Mail::fake();
        $user = $this->client();

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('work-with-us.thanks'));

        $application = PartnerApplication::firstOrFail();
        $this->assertSame($user->id, $application->user_id);
        $this->assertSame($user->email, $application->email);
        $this->assertSame(PartnerApplication::STATUS_INVITED, $application->status);

        Mail::assertQueued(PartnerInvitationMail::class);
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

    public function test_sending_the_request_twice_updates_the_open_one(): void
    {
        Mail::fake();
        $user = $this->client();

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit');

        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))
            ->set('form.businessName', 'Agriturismo Le Palme')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, PartnerApplication::count());
        $this->assertSame('Agriturismo Le Palme', PartnerApplication::firstOrFail()->business_name);
    }

    public function test_step_1_prefills_from_the_open_request_without_a_signed_link(): void
    {
        Mail::fake();
        $user = $this->client();
        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit');

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
        $this->fillApplication(Livewire::actingAs($other)->test(WorkWithUs::class))
            ->set('form.businessName', 'Hotel Rosovino')
            ->call('submit');

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
        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit');

        Livewire::actingAs($user)->test(PartnerRegisterStep1::class)
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.vat', '86334519757')
            ->set('form.taxCode', 'RSSGLI90A41G224X')
            ->set('form.pec', 'lepalme@pec.it')
            ->set('form.sdi', 'SUBM70N')
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
        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit');

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
        $this->fillApplication(Livewire::actingAs($user)->test(WorkWithUs::class))->call('submit');

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
