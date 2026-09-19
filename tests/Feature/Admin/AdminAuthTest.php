<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attributes = []): User
    {
        Role::findOrCreate('superadmin', 'web');

        $user = User::factory()->create(array_merge([
            'email' => 'silvia@animalamo.it',
            'password' => 'Corretta123!',
            'is_active' => true,
        ], $attributes));
        $user->assignRole('superadmin');

        return $user;
    }

    public function test_the_login_page_renders_the_design_copy(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Area di amministrazione')
            ->assertSee('Accedi con le credenziali che ti abbiamo assegnato.')
            ->assertSee('Hai dimenticato la password?');
    }

    public function test_an_admin_logs_in_and_lands_on_the_panel(): void
    {
        $admin = $this->admin();

        Livewire::test(Login::class)
            ->set('email', 'silvia@animalamo.it')
            ->set('password', 'Corretta123!')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.home'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_wrong_password_shows_the_single_error_message(): void
    {
        $this->admin();

        Livewire::test(Login::class)
            ->set('email', 'silvia@animalamo.it')
            ->set('password', 'sbagliata')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Email o password non corretti.');

        $this->assertGuest();
    }

    public function test_a_customer_with_the_right_password_gets_the_same_error(): void
    {
        User::factory()->create(['email' => 'cliente@example.com', 'password' => 'Corretta123!', 'is_active' => true]);

        Livewire::test(Login::class)
            ->set('email', 'cliente@example.com')
            ->set('password', 'Corretta123!')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_five_failures_lock_the_account_even_with_the_right_password(): void
    {
        $this->admin();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'silvia@animalamo.it')
                ->set('password', 'sbagliata')
                ->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', 'silvia@animalamo.it')
            ->set('password', 'Corretta123!')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Troppi tentativi');

        $this->assertGuest();
    }

    public function test_the_reset_link_is_mailed_to_an_admin_and_points_to_the_panel(): void
    {
        Mail::fake();
        $this->admin();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'silvia@animalamo.it')
            ->call('send')
            ->assertSet('sent', true)
            ->assertSee('Controlla la posta');

        Mail::assertSent(ResetPasswordMail::class, fn (ResetPasswordMail $mail) => str_contains($mail->link, '/admin/reimposta-password/'));
    }

    public function test_no_mail_leaves_for_a_non_admin_but_the_answer_is_the_same(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'cliente@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'cliente@example.com')
            ->call('send')
            ->assertSet('sent', true);

        Mail::assertNothingSent();
    }

    public function test_a_valid_token_resets_the_password(): void
    {
        $admin = $this->admin();
        $token = Password::createToken($admin);

        Livewire::withQueryParams(['email' => $admin->email])
            ->test(ResetPassword::class, ['token' => $token])
            ->assertSet('state', 'form')
            ->set('password', 'NuovaPassword1')
            ->set('password_confirmation', 'NuovaPassword1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('state', 'done')
            ->assertSee('Password aggiornata');

        $this->assertTrue(Hash::check('NuovaPassword1', $admin->fresh()->password));
    }

    public function test_the_password_rules_of_the_design_are_enforced(): void
    {
        $admin = $this->admin();
        $token = Password::createToken($admin);

        Livewire::withQueryParams(['email' => $admin->email])
            ->test(ResetPassword::class, ['token' => $token])
            ->set('password', 'tuttominuscolo')
            ->set('password_confirmation', 'tuttominuscolo')
            ->call('save')
            ->assertHasErrors('password')
            ->assertSet('state', 'form');
    }

    public function test_an_invalid_token_shows_the_expired_link_screen(): void
    {
        $admin = $this->admin();

        Livewire::withQueryParams(['email' => $admin->email])
            ->test(ResetPassword::class, ['token' => 'non-valido'])
            ->assertSet('state', 'invalid')
            ->assertSee('Link non più valido');
    }

    public function test_the_command_promotes_an_existing_account(): void
    {
        Mail::fake();
        Role::findOrCreate('superadmin', 'web');
        $user = User::factory()->create(['email' => 'silvia@animalamo.it', 'is_active' => true]);

        $this->artisan('animalamo:make-superadmin', ['email' => 'silvia@animalamo.it'])->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('superadmin'));
        Mail::assertSent(ResetPasswordMail::class);
    }

    public function test_the_command_creates_a_missing_account(): void
    {
        Mail::fake();
        Role::findOrCreate('superadmin', 'web');

        $this->artisan('animalamo:make-superadmin', ['email' => 'nuova@animalamo.it', '--no-mail' => true])->assertSuccessful();

        $user = User::query()->where('email', 'nuova@animalamo.it')->firstOrFail();
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertTrue($user->is_active);
        Mail::assertNothingSent();
    }
}
