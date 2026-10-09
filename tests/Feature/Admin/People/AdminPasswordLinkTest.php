<?php

namespace Tests\Feature\Admin\People;

use App\Exceptions\AdminPasswordLinkException;
use App\Livewire\Admin\People\UserShow;
use App\Livewire\Auth\ResetPassword;
use App\Mail\AdminPasswordLinkMail;
use App\Models\User;
use App\Services\Admin\People\AdminPasswordLinkService;
use App\Services\PasswordResetService;
use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPasswordLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Il pannello è solo italiano: vedi UserShowTest::setUp().
        app()->setLocale('it');
        Mail::fake();

        Role::findOrCreate('client', 'web');
        Role::findOrCreate('superadmin', 'web');
    }

    private function service(): AdminPasswordLinkService
    {
        return app(AdminPasswordLinkService::class);
    }

    private function client(array $attributes = []): User
    {
        $client = User::factory()->create(['password' => 'password-vecchia', ...$attributes]);
        $client->assignRole('client');

        return $client;
    }

    private function sentTo(User $user): AdminPasswordLinkMail
    {
        $sent = null;

        Mail::assertSent(AdminPasswordLinkMail::class, function (AdminPasswordLinkMail $mail) use ($user, &$sent): bool {
            $sent = $mail;

            return $mail->hasTo($user->email);
        });

        return $sent;
    }

    private function tokenOf(string $url): string
    {
        return Str::afterLast((string) parse_url($url, PHP_URL_PATH), '/');
    }

    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_a_customer_gets_a_seven_day_link_and_no_password(): void
    {
        $client = $this->client();

        $this->service()->send($client);

        $mail = $this->sentTo($client);
        $this->assertStringContainsString('admin=1', $mail->link);
        $this->assertStringContainsString('email='.urlencode($client->email), $mail->link);
        $this->assertTrue(Password::broker(PasswordResetService::ADMIN_BROKER)->tokenExists($client, $this->tokenOf($mail->link)));
        $this->assertSame(7, $mail->expiresInDays);
        $mail->assertDontSeeInHtml('password-vecchia');
    }

    public function test_the_mail_is_sent_at_once_in_the_default_language(): void
    {
        app()->setLocale('en');
        $client = $this->client();

        $mail = new AdminPasswordLinkMail($client, 'https://animalamo.test/reimposta-password/abc?admin=1');
        $locale = LaravelLocalization::getDefaultLocale();

        $this->assertNotInstanceOf(ShouldQueue::class, $mail);
        $this->assertSame($locale, $mail->locale);
        $mail->assertHasSubject(__('auth-modal.admin_reset_mail.subject', [], $locale));
        $mail->assertSeeInHtml(__('auth-modal.admin_reset_mail.cta', [], $locale));
        $mail->assertSeeInHtml('https://animalamo.test/reimposta-password/abc?admin=1', false);
    }

    public function test_the_customer_sets_a_new_password_and_every_session_ends(): void
    {
        $client = $this->client(['remember_token' => 'vecchio-token']);
        DB::table('sessions')->insert([
            'id' => 'sessione-aperta',
            'user_id' => $client->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->service()->send($client);
        $url = $this->sentTo($client)->link;

        $this->get($url)->assertOk()->assertSee(__('auth-modal.admin_reset.title'));

        Livewire::test(ResetPassword::class, ['token' => $this->tokenOf($url), 'email' => $client->email, 'admin' => true])
            ->assertSet('adminLink', true)
            ->set('password', 'password-nuova')
            ->set('passwordConfirm', 'password-nuova')
            ->call('save')
            ->assertSet('done', true);

        $client->refresh();
        $this->assertTrue(Hash::check('password-nuova', $client->password));
        $this->assertNotSame('vecchio-token', $client->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $client->id]);
    }

    public function test_a_partner_gets_the_link_too_and_ends_on_the_partner_login(): void
    {
        $partner = User::factory()->offlinePartner()->create();

        $this->service()->send($partner);
        $url = $this->sentTo($partner)->link;

        Livewire::test(ResetPassword::class, ['token' => $this->tokenOf($url), 'email' => $partner->email, 'admin' => true])
            ->set('password', 'password-nuova')
            ->set('passwordConfirm', 'password-nuova')
            ->call('save')
            ->assertSet('done', true)
            ->call('goToLogin')
            ->assertDispatched('modal-show', name: 'partner-login');
    }

    public function test_the_link_lasts_seven_days_and_not_eight(): void
    {
        $client = $this->client();
        $this->service()->send($client);
        $token = $this->tokenOf($this->sentTo($client)->link);

        $this->travel(8)->days();

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $client->email, 'admin' => true])
            ->set('password', 'password-nuova')
            ->set('passwordConfirm', 'password-nuova')
            ->call('save')
            ->assertSet('invalid', true);

        $this->assertTrue(Hash::check('password-vecchia', $client->fresh()->password));
    }

    public function test_a_superadmin_never_gets_the_seven_day_window(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        // Token creato a mano col broker lungo: la pagina non deve accettarlo come link admin.
        $token = Password::broker(PasswordResetService::ADMIN_BROKER)->createToken($admin);

        $this->expectException(AdminPasswordLinkException::class);

        try {
            $this->service()->send($admin);
        } finally {
            Mail::assertNothingSent();
            $this->assertFalse(app(PasswordResetService::class)->acceptsAdminLink($admin->email, $token));
        }
    }

    public function test_sending_again_replaces_the_link_but_not_twice_in_a_minute(): void
    {
        $client = $this->client();
        $this->service()->send($client);
        $first = $this->tokenOf($this->sentTo($client)->link);

        $this->travel(61)->seconds();
        $this->service()->send($client);

        Mail::assertSent(AdminPasswordLinkMail::class, 2);
        $this->assertFalse(Password::broker(PasswordResetService::ADMIN_BROKER)->tokenExists($client, $first));

        try {
            $this->service()->send($client);
            $this->fail('Il secondo invio entro un minuto va rifiutato.');
        } catch (AdminPasswordLinkException $e) {
            $this->assertSame(__('admin-people.users.password_link.errors.throttled'), $e->getMessage());
        }

        Mail::assertSent(AdminPasswordLinkMail::class, 2);
    }

    public function test_deactivated_and_anonymised_accounts_get_no_link(): void
    {
        $inactive = $this->client(['is_active' => false]);
        $anonymised = $this->client();
        $anonymised->forceFill(['anonymized_at' => now()])->save();

        foreach ([$inactive, $anonymised] as $user) {
            try {
                $this->service()->send($user);
                $this->fail('Nessun link a un account non attivo.');
            } catch (AdminPasswordLinkException $e) {
                $this->assertSame(__('admin-people.users.password_link.errors.inactive'), $e->getMessage());
            }
        }

        Mail::assertNothingSent();
    }

    public function test_the_user_page_sends_the_link_and_shows_the_outcome(): void
    {
        $this->actingAsSuperadmin();
        $client = $this->client();

        $this->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertSee(__('admin-people.users.password_link.button'));

        Livewire::test(UserShow::class, ['user' => $client])
            ->call('sendPasswordLink')
            ->assertDispatched('toast-show', $this->toast(__('admin-people.users.password_link.sent', ['email' => $client->email])))
            ->call('sendPasswordLink')
            ->assertDispatched('toast-show', $this->toast(__('admin-people.users.password_link.errors.throttled')));

        Mail::assertSent(AdminPasswordLinkMail::class, 1);
    }

    public function test_a_deactivated_account_has_no_button(): void
    {
        $this->actingAsSuperadmin();
        $client = $this->client(['is_active' => false]);

        $this->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertDontSee(__('admin-people.users.password_link.button'));
    }
}
