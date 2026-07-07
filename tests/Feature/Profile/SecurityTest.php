<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfileSecurity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_persists(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show')
            // I campi tornano vuoti (placeholder asterischi come da mock).
            ->assertSet('currentPassword', '')
            ->assertSet('password', '')
            ->assertSet('passwordConfirm', '');

        $this->assertTrue(Hash::check('nuova-password-123', $user->fresh()->password));
    }

    public function test_password_mismatch_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'diversa-password-456')
            ->call('save')
            ->assertHasErrors(['passwordConfirm' => 'same']);

        // La password originale resta invariata.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_short_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'corta12')
            ->set('passwordConfirm', 'corta12')
            ->call('save')
            ->assertHasErrors(['password' => 'min']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_stays_authenticated_after_change(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user->fresh());
    }

    /**
     * Regressione security: senza la password attuale il cambio password è
     * bloccato (impedisce il takeover da una sessione rubata/condivisa).
     */
    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        // Password attuale mancante.
        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasErrors(['currentPassword' => 'required']);

        // Password attuale errata.
        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
            ->set('currentPassword', 'password-sbagliata')
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasErrors(['currentPassword' => 'current_password']);

        // In nessuno dei due casi la password è stata cambiata.
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
