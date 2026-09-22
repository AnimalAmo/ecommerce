<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerWelcomePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Role::findOrCreate('partner', 'web');
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('superadmin', 'web');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_welcome_token_is_still_valid_after_sixty_one_minutes(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $token = Password::broker('partner_welcome')->createToken($partner);

        Carbon::setTestNow(now()->addMinutes(61));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $partner->email, 'welcome' => true])
            ->assertSet('welcome', true)
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('done', true)
            ->assertSet('invalid', false);

        $this->assertTrue(Hash::check('nuova-password', $partner->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $partner->email]);
    }

    public function test_a_welcome_token_expires_after_seven_days(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $token = Password::broker('partner_welcome')->createToken($partner);

        Carbon::setTestNow(now()->addDays(7)->addMinute());

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $partner->email, 'welcome' => true])
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('invalid', true)
            ->assertSet('done', false)
            ->assertSee(__('auth-modal.reset.invalid_title'));

        $this->assertTrue(Hash::check('password', $partner->fresh()->password));
    }

    public function test_the_welcome_link_shows_the_welcome_copy(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $token = Password::broker('partner_welcome')->createToken($partner);

        $this->get(route('password.reset', ['token' => $token, 'email' => $partner->email, 'welcome' => 1]))
            ->assertOk()
            ->assertSee(__('auth-modal.partner_welcome.title'))
            ->assertSee(__('auth-modal.partner_welcome.submit'))
            ->assertSee($partner->email)
            ->assertDontSee(__('auth-modal.reset.title'))
            ->assertDontSee(__('auth-modal.reset.submit'));
    }

    public function test_after_the_welcome_the_partner_is_sent_to_the_partner_login(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $token = Password::broker('partner_welcome')->createToken($partner);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $partner->email, 'welcome' => true])
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSee(__('auth-modal.partner_welcome.done_title'))
            ->assertSee(__('auth-modal.partner_welcome.done_cta'))
            ->assertDontSee(__('auth-modal.reset.done_title'))
            ->call('goToLogin')
            ->assertDispatched('modal-show', name: 'partner-login')
            ->assertNotDispatched('modal-show', name: 'login');
    }

    public function test_without_welcome_a_sixty_one_minute_old_token_is_refused_as_today(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Carbon::setTestNow(now()->addMinutes(61));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
            ->assertSet('welcome', false)
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('invalid', true);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_an_admin_reset_link_is_not_stretched_to_seven_days(): void
    {
        // Il link di AdminAuthService usa il broker di default e la stessa tabella.
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        $token = Password::createToken($admin);

        Carbon::setTestNow(now()->addMinutes(61));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $admin->email, 'welcome' => true])
            ->assertSet('welcome', false)
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('invalid', true);

        // Anche chiamando il service col broker di benvenuto, per un admin vale il default.
        $this->assertSame(
            Password::INVALID_TOKEN,
            app(PasswordResetService::class)->reset($admin->email, $token, 'nuova-password', PasswordResetService::WELCOME_BROKER),
        );
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_a_customer_reset_link_is_not_stretched_to_seven_days(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $token = Password::createToken($client);

        Carbon::setTestNow(now()->addMinutes(61));

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $client->email, 'welcome' => true])
            ->assertSet('welcome', false)
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('invalid', true);

        $this->assertTrue(Hash::check('password', $client->fresh()->password));
    }

    public function test_the_welcome_flag_cannot_be_turned_on_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(ResetPassword::class, ['token' => 'un-token', 'email' => 'a@example.com'])
            ->set('welcome', true);
    }
}
