<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfileSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_security_sections(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile.security'))
            ->assertOk()
            ->assertSee(__('partner.profile.security_heading'))
            ->assertSee(__('partner.profile.new_password'))
            ->assertSee(__('partner.profile.privacy_settings'))
            ->assertSee(__('partner.profile.delete_account'));
    }

    public function test_change_requires_the_current_password(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileSecurity::class)
            ->set('currentPassword', 'wrong-password')
            ->set('password', 'new-password')
            ->set('passwordConfirm', 'new-password')
            ->call('save')
            ->assertHasErrors('currentPassword');
    }

    public function test_confirmation_must_match(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'new-password')
            ->set('passwordConfirm', 'different')
            ->call('save')
            ->assertHasErrors('passwordConfirm');
    }

    public function test_it_changes_the_password(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfileSecurity::class)
            ->set('currentPassword', 'password')
            ->set('password', 'new-password')
            ->set('passwordConfirm', 'new-password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $partner->fresh()->password));
    }
}
