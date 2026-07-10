<?php

namespace Tests\Feature\Partner;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee(__('partner.profile.password'))
            ->assertSee(__('partner.profile.reset_password'))
            ->assertSee(__('partner.profile.privacy_settings'))
            ->assertSee(__('partner.profile.delete_account'));
    }
}
