<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\Inbox;
use App\Livewire\Admin\People\PartnerCreate;
use App\Mail\PartnerWelcomeMail;
use App\Models\Partner\PartnerApplication;
use App\Models\Region\Province;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Crea partner" da una candidatura e password scelta dall'admin (richieste
 * della cliente, 05/10/2026): il candidato senza account diventa partner dal
 * pannello, e può entrare senza passare dalla mail.
 */
class PartnerFromApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Mail::fake();

        Role::findOrCreate('partner', 'web');
        Role::findOrCreate('client', 'web');
        Province::create(['short_name' => 'BS', 'name' => 'Brescia']);
    }

    private function application(array $attributes = []): PartnerApplication
    {
        return PartnerApplication::create(array_merge([
            'first_name' => 'Marco',
            'last_name' => 'Galli',
            'email' => 'marco@example.com',
            'phone' => '+393331112222',
            'city' => 'Belgioioso',
            'business_name' => 'Agriturismo Le Corti',
            'role' => 'Titolare',
            'offer_type' => 'Struttura ricettiva',
            'description' => 'Cascina con sei camere e parco recintato.',
            'status' => PartnerApplication::STATUS_INVITED,
        ], $attributes));
    }

    private function openFrom(?PartnerApplication $application): Testable
    {
        return Livewire::withQueryParams($application !== null ? ['application' => $application->id] : [])
            ->test(PartnerCreate::class);
    }

    /** I campi che la candidatura non ha: quelli che l'admin scrive a mano. */
    private function complete(Testable $component, array $overrides = []): Testable
    {
        return $component->set(collect(array_merge([
            'address' => 'Via Roma 1',
            'province' => 'BS',
            'zip' => '25100',
            'paymentMode' => 'on_site',
        ], $overrides))->mapWithKeys(fn ($value, string $key): array => ['form.'.$key => $value])->all());
    }

    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_the_form_is_prefilled_from_an_open_application(): void
    {
        $this->actingAsSuperadmin();
        $application = $this->application();

        $this->openFrom($application)
            ->assertSet('applicationId', $application->id)
            ->assertSet('form.firstName', 'Marco')
            ->assertSet('form.lastName', 'Galli')
            ->assertSet('form.email', 'marco@example.com')
            ->assertSet('form.phone', '+393331112222')
            ->assertSet('form.businessName', 'Agriturismo Le Corti')
            ->assertSet('form.vat', '')
            ->assertSee('Città indicata nella candidatura: Belgioioso');
    }

    public function test_a_registered_or_unknown_application_opens_an_empty_form(): void
    {
        $this->actingAsSuperadmin();
        $registered = $this->application(['status' => PartnerApplication::STATUS_REGISTERED]);

        $this->openFrom($registered)->assertSet('applicationId', null)->assertSet('form.firstName', '');

        Livewire::withQueryParams(['application' => 999])
            ->test(PartnerCreate::class)
            ->assertSet('applicationId', null);
    }

    public function test_saving_closes_the_application_and_the_others_with_the_same_email(): void
    {
        $this->actingAsSuperadmin();
        $application = $this->application();
        $twin = $this->application(['email' => 'Marco@Example.com', 'status' => PartnerApplication::STATUS_PENDING]);
        $other = $this->application(['email' => 'altro@example.com']);

        $this->complete($this->openFrom($application))->call('save')->assertHasNoErrors();

        $partner = User::query()->where('email', 'marco@example.com')->sole();
        $this->assertTrue($partner->hasRole('partner'));

        foreach ([$application, $twin] as $closed) {
            $closed->refresh();
            $this->assertSame(PartnerApplication::STATUS_REGISTERED, $closed->status);
            $this->assertSame($partner->id, $closed->user_id);
            $this->assertNotNull($closed->registered_at);
            $this->assertNotNull($closed->handled_at);
        }

        $this->assertSame(PartnerApplication::STATUS_INVITED, $other->fresh()->status);
        $this->assertNull($other->fresh()->handled_at);
    }

    public function test_the_source_application_is_closed_even_when_the_admin_corrects_the_email(): void
    {
        $this->actingAsSuperadmin();
        $application = $this->application(['email' => 'marco@exmaple.com']);

        $this->complete($this->openFrom($application), ['email' => 'marco@example.com'])->call('save')->assertHasNoErrors();

        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->fresh()->status);
    }

    public function test_a_password_chosen_by_the_admin_skips_the_set_password_link(): void
    {
        $this->actingAsSuperadmin();

        $this->complete($this->openFrom($this->application()), ['password' => 'segreta-123', 'passwordConfirmation' => 'segreta-123'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.created_with_password')));

        $partner = User::query()->where('email', 'marco@example.com')->sole();
        $this->assertTrue(Hash::check('segreta-123', $partner->password));

        Mail::assertSent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->hasTo('marco@example.com')
            && $mail->setPasswordUrl === null
            && $mail->passwordGiven);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'marco@example.com']);
    }

    public function test_the_welcome_mail_never_contains_the_password(): void
    {
        $this->actingAsSuperadmin();

        $this->complete($this->openFrom($this->application()), ['password' => 'segreta-123', 'passwordConfirmation' => 'segreta-123'])->call('save');

        Mail::assertSent(PartnerWelcomeMail::class, function (PartnerWelcomeMail $mail): bool {
            $html = $mail->render();

            return ! str_contains($html, 'segreta-123') && str_contains($html, 'la password che ti abbiamo comunicato');
        });
    }

    public function test_the_password_must_be_long_enough_and_repeated(): void
    {
        $this->actingAsSuperadmin();

        $this->complete($this->openFrom($this->application()), ['password' => 'corta', 'passwordConfirmation' => 'corta'])
            ->call('save')
            ->assertHasErrors(['form.password' => 'min']);

        $this->complete($this->openFrom($this->application()), ['password' => 'segreta-123', 'passwordConfirmation' => 'segreta-124'])
            ->call('save')
            ->assertHasErrors(['form.passwordConfirmation' => 'same']);

        $this->assertSame(0, User::query()->where('email', 'marco@example.com')->count());
    }

    public function test_a_promoted_customer_keeps_its_own_password(): void
    {
        $this->actingAsSuperadmin();
        $client = User::factory()->create(['email' => 'marco@example.com', 'password' => 'la-sua-password']);
        $client->assignRole('client');
        $application = $this->application(['user_id' => $client->id]);

        $this->complete($this->openFrom($application), ['password' => 'segreta-123', 'passwordConfirmation' => 'segreta-123'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.promoted')));

        $client->refresh();
        $this->assertTrue($client->hasRole('partner'));
        $this->assertTrue(Hash::check('la-sua-password', $client->password));
        Mail::assertSent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->setPasswordUrl === null && ! $mail->passwordGiven);
    }

    public function test_a_customer_application_is_prefilled_with_the_account_email(): void
    {
        $this->actingAsSuperadmin();
        $client = User::factory()->create(['email' => 'account@example.com']);
        $client->assignRole('client');

        $this->openFrom($this->application(['user_id' => $client->id, 'email' => 'altra@example.com']))
            ->assertSet('form.email', 'account@example.com');
    }

    public function test_the_inbox_offers_create_partner_only_on_open_applications(): void
    {
        $this->actingAsSuperadmin();
        $application = $this->application();

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertSee(__('admin-people.inbox.create_partner'))
            ->assertSeeHtml(e(route('admin.users.create', ['application' => $application->id])));

        $application->update(['status' => PartnerApplication::STATUS_REGISTERED]);

        Livewire::test(Inbox::class)
            ->set('tab', 'applications')
            ->assertDontSee(__('admin-people.inbox.create_partner'));
    }

    public function test_a_customer_page_offers_make_partner_for_its_open_application(): void
    {
        $this->actingAsSuperadmin();
        $client = User::factory()->create();
        $client->assignRole('client');
        $application = $this->application(['user_id' => $client->id]);

        $this->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertSee(__('admin-people.users.make_partner'))
            ->assertSee(route('admin.users.create', ['application' => $application->id]), false);
    }

    public function test_the_old_invitation_of_a_registered_application_says_the_account_is_ready(): void
    {
        $application = $this->application(['status' => PartnerApplication::STATUS_REGISTERED]);

        $this->get(URL::signedRoute('partner.register', ['application' => $application->id]))
            ->assertOk()
            ->assertSee('Il tuo account partner è già pronto')
            ->assertDontSee('Agriturismo Le Corti');

        $this->assertNull(session('partner_registration.application_id'));
    }
}
