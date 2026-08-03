<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\AuthModal;
use App\Livewire\Auth\ForgotPasswordModal;
use App\Livewire\Auth\PartnerLoginModal;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Profile\ProfileSecurity;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Il throttle per IP vive nella cache, che RefreshDatabase non tocca.
        RateLimiter::clear('password-reset|127.0.0.1');
    }

    public function test_the_forgot_password_modal_sends_the_reset_link(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        Livewire::test(ForgotPasswordModal::class)
            ->set('email', $user->email)
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSet('sentTo', $user->email);

        Mail::assertSent(ResetPasswordMail::class, fn (ResetPasswordMail $mail): bool => $mail->hasTo($user->email));

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_an_unknown_email_gets_the_same_confirmation_without_any_mail(): void
    {
        Mail::fake();

        // Nessuna differenza visibile rispetto al caso precedente: la modale
        // non deve dire a un ospite quali indirizzi hanno un account.
        Livewire::test(ForgotPasswordModal::class)
            ->set('email', 'nessuno@example.com')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSet('sentTo', 'nessuno@example.com');

        Mail::assertNothingSent();
    }

    public function test_the_reset_link_request_requires_a_valid_email(): void
    {
        Livewire::test(ForgotPasswordModal::class)
            ->call('send')
            ->assertHasErrors(['email' => 'required'])
            ->assertSet('sent', false);

        Livewire::test(ForgotPasswordModal::class)
            ->set('email', 'non-una-email')
            ->call('send')
            ->assertHasErrors(['email' => 'email'])
            ->assertSet('sent', false);
    }

    public function test_the_mail_links_to_the_localized_reset_page(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        Livewire::test(ForgotPasswordModal::class)
            ->set('email', $user->email)
            ->call('send');

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user): bool {
            return str_starts_with($mail->link, url('/reimposta-password/'))
                && str_contains($mail->link, 'email='.urlencode($user->email))
                && $mail->expiresInMinutes === 60;
        });
    }

    public function test_reset_link_requests_are_rate_limited_by_ip(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            Livewire::test(ForgotPasswordModal::class)
                ->set('email', "utente{$attempt}@example.com")
                ->call('send')
                ->assertHasNoErrors();
        }

        Livewire::test(ForgotPasswordModal::class)
            ->set('email', 'utente6@example.com')
            ->call('send')
            ->assertHasErrors(['email'])
            ->assertSet('sent', false);
    }

    public function test_both_login_modals_open_the_forgot_password_modal(): void
    {
        Livewire::test(AuthModal::class)
            ->set('form.email', 'giulia.rossi@gmail.com')
            ->call('openForgotPassword')
            ->assertDispatched('open-forgot-password', email: 'giulia.rossi@gmail.com', origin: 'login');

        Livewire::test(PartnerLoginModal::class)
            ->set('form.email', 'partner@example.com')
            ->call('openForgotPassword')
            ->assertDispatched('open-forgot-password', email: 'partner@example.com', origin: 'partner-login');
    }

    public function test_the_profile_entry_point_does_not_offer_to_reopen_the_login_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->call('openForgotPassword')
            ->assertDispatched('open-forgot-password', email: $user->email, origin: 'none');

        // 'none' non è una modale: nella conferma il pulsante che altrove
        // riporta al login diventa un semplice "Chiudi".
        Mail::fake();

        Livewire::test(ForgotPasswordModal::class)
            ->call('open', $user->email, 'none')
            ->assertSet('origin', '')
            ->call('send')
            ->assertSet('sent', true)
            ->assertSee(__('auth-modal.close'))
            ->assertDontSee(__('auth-modal.forgot.back_to_login'));
    }

    public function test_the_reset_page_renders_from_the_email_link(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee(__('auth-modal.reset.title'))
            ->assertSee($user->email);
    }

    public function test_a_valid_token_sets_the_new_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('done', true)
            ->assertSet('invalid', false);

        $this->assertTrue(Hash::check('nuova-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_a_used_token_cannot_be_replayed(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('done', true);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
            ->set('password', 'password-di-un-altro')
            ->set('passwordConfirm', 'password-di-un-altro')
            ->call('save')
            ->assertSet('invalid', true)
            ->assertSet('done', false)
            ->assertSee(__('auth-modal.reset.invalid_title'));

        // La prima reimpostazione resta quella valida.
        $this->assertTrue(Hash::check('nuova-password', $user->fresh()->password));
    }

    public function test_a_forged_token_leaves_the_password_untouched(): void
    {
        $user = User::factory()->create();

        Livewire::test(ResetPassword::class, ['token' => 'token-inventato', 'email' => $user->email])
            ->set('password', 'nuova-password')
            ->set('passwordConfirm', 'nuova-password')
            ->call('save')
            ->assertSet('invalid', true);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_a_link_without_the_email_is_treated_as_invalid(): void
    {
        Livewire::test(ResetPassword::class, ['token' => 'un-token', 'email' => ''])
            ->assertSet('invalid', true)
            ->assertSee(__('auth-modal.reset.invalid_title'))
            ->assertDontSee(__('auth-modal.reset.submit'));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token, 'email' => $user->email])
            ->set('password', 'corta')
            ->set('passwordConfirm', 'diversa')
            ->call('save')
            ->assertHasErrors(['password' => 'min', 'passwordConfirm' => 'same'])
            ->assertSet('done', false);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
