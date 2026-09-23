<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\PartnerCreate;
use App\Mail\PartnerWelcomeMail;
use App\Models\Region\Province;
use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerCreateTest extends TestCase
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

    private function filled(array $overrides = []): Testable
    {
        $data = array_merge([
            'firstName' => 'Marco',
            'lastName' => 'Galli',
            'businessName' => 'Agriturismo Le Corti',
            'email' => 'marco@example.com',
            'address' => 'Via Roma 1',
            'province' => 'BS',
            'zip' => '25100',
            'phone' => '+393331112222',
            'vat' => '01234567890',
            'taxCode' => 'GLLMRC80A01B157X',
            'paymentMode' => 'online',
            'paymentUrl' => '',
        ], $overrides);

        return Livewire::test(PartnerCreate::class)
            ->set(collect($data)->mapWithKeys(fn ($value, string $key): array => ['form.'.$key => $value])->all());
    }

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_the_page_renders_with_the_provinces(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee(__('admin-people.partner_create.title'))
            ->assertSee(__('admin-people.partner_create.submit'))
            ->assertSee('Brescia');
    }

    public function test_the_users_list_links_to_the_form(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('admin-people.partner_create.new_button'))
            ->assertSee(route('admin.users.create'));
    }

    public function test_the_form_creates_a_partner_and_opens_its_profile(): void
    {
        $admin = $this->actingAsSuperadmin();

        $component = $this->filled()->call('save')->assertHasNoErrors();

        $partner = User::query()->where('email', 'marco@example.com')->sole();
        $component
            ->assertRedirect(route('admin.users.show', $partner))
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.created')));

        $this->assertTrue($partner->hasRole('partner'));
        $this->assertSame('BS', $partner->partnerProfile->province);
        $this->assertTrue($partner->partnerProfile->requiresOnlinePayment());
        Mail::assertSent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->hasTo('marco@example.com') && $mail->setPasswordUrl !== null);
        // L'admin resta l'admin: nessun login col nuovo account.
        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_admin_is_warned_when_the_payment_mode_could_not_be_saved(): void
    {
        $this->actingAsSuperadmin();
        Exceptions::fake();

        $this->mock(PartnerPaymentModeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('set')->once()->andThrow(new RuntimeException('publish job failed'));
        });

        $this->filled(['paymentMode' => 'on_site', 'paymentUrl' => 'https://lecorti.example'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.payment_mode_failed')))
            ->assertNotDispatched('toast-show', $this->toast(__('admin-people.partner_create.created')));
    }

    public function test_an_upper_case_email_is_stored_in_lower_case(): void
    {
        $this->actingAsSuperadmin();

        $this->filled(['email' => 'Marco@Example.COM'])->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'marco@example.com']);
    }

    public function test_an_active_customer_is_promoted_and_mailed_without_a_password_link(): void
    {
        $this->actingAsSuperadmin();
        $client = User::factory()->create(['email' => 'giulia@example.com']);
        $client->assignRole('client');

        $this->filled(['email' => 'Giulia@Example.COM'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.show', $client))
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.promoted')));

        $client->refresh();
        $this->assertTrue($client->hasRole('client'));
        $this->assertTrue($client->hasRole('partner'));
        $this->assertSame(2, User::count());
        Mail::assertSent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->hasTo('giulia@example.com') && $mail->setPasswordUrl === null);
    }

    public function test_the_email_of_an_existing_partner_links_to_its_profile(): void
    {
        $this->actingAsSuperadmin();
        $existing = User::factory()->offlinePartner()->create(['email' => 'marco@example.com']);

        $this->filled(['email' => 'MARCO@example.com'])
            ->call('save')
            ->assertHasErrors(['form.email'])
            ->assertSee(__('admin-people.partner_create.errors.already_partner'))
            ->assertSet('existingUserId', $existing->id)
            ->assertSee(route('admin.users.show', $existing))
            ->assertNoRedirect();

        Mail::assertNothingSent();
    }

    public function test_the_email_of_a_superadmin_is_refused(): void
    {
        $this->actingAsSuperadmin(['email' => 'admin@example.com']);

        $this->filled(['email' => 'admin@example.com'])
            ->call('save')
            ->assertHasErrors(['form.email'])
            ->assertSee(__('admin-people.partner_create.errors.superadmin'))
            ->assertSet('existingUserId', null);
    }

    public function test_a_deactivated_account_is_refused(): void
    {
        $this->actingAsSuperadmin();
        User::factory()->inactive()->create(['email' => 'marco@example.com']);

        $this->filled()
            ->call('save')
            ->assertHasErrors(['form.email'])
            ->assertSee(__('admin-people.partner_create.errors.inactive'));

        $this->assertDatabaseCount('partner_profiles', 0);
    }

    public function test_an_on_site_partner_is_saved_with_its_link(): void
    {
        $this->actingAsSuperadmin();

        $this->filled(['paymentMode' => 'on_site', 'paymentUrl' => 'https://lecorti.example/prenota'])
            ->call('save')
            ->assertHasNoErrors();

        $profile = User::query()->where('email', 'marco@example.com')->sole()->partnerProfile;
        $this->assertFalse($profile->online_payment);
        $this->assertSame('https://lecorti.example/prenota', $profile->payment_url);
    }

    public function test_a_javascript_link_is_refused(): void
    {
        $this->actingAsSuperadmin();

        $this->filled(['paymentMode' => 'on_site', 'paymentUrl' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasErrors(['form.paymentUrl']);

        $this->assertDatabaseMissing('users', ['email' => 'marco@example.com']);
    }

    public function test_an_unknown_province_is_refused(): void
    {
        $this->actingAsSuperadmin();

        $this->filled(['province' => 'ZZ'])
            ->call('save')
            ->assertHasErrors(['form.province']);

        $this->assertDatabaseMissing('users', ['email' => 'marco@example.com']);
    }

    public function test_the_fields_of_the_site_form_are_required_here_too(): void
    {
        $this->actingAsSuperadmin();

        $this->filled(['firstName' => '', 'vat' => '', 'zip' => '123'])
            ->call('save')
            ->assertHasErrors(['form.firstName' => 'required', 'form.vat' => 'required', 'form.zip' => 'digits']);
    }

    public function test_only_a_superadmin_opens_the_form(): void
    {
        $this->get(route('admin.users.create'))->assertRedirect(route('admin.login'));

        $this->actingAsActivePartner();
        $this->get(route('admin.users.create'))->assertForbidden();
    }
}
