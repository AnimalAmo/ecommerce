<?php

namespace Tests\Feature\Profile;

use App\Livewire\ProfileSecurity;
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
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show')
            // I campi tornano vuoti (placeholder asterischi come da mock).
            ->assertSet('password', '')
            ->assertSet('passwordConfirm', '');

        $this->assertTrue(Hash::check('nuova-password-123', $user->fresh()->password));
    }

    public function test_password_mismatch_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(ProfileSecurity::class)
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
            ->set('password', 'nuova-password-123')
            ->set('passwordConfirm', 'nuova-password-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user->fresh());
    }
}
