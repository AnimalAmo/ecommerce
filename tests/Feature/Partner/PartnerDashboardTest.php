<?php

namespace Tests\Feature\Partner;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_welcome_stats_and_nav(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.dashboard.welcome', ['name' => 'Susanna']))
            ->assertSee(__('partner.dashboard.cta'))
            ->assertSee(__('partner.dashboard.stat_sold'))
            ->assertSee(__('partner.dashboard.stat_cancelled'))
            ->assertSee(__('partner.dashboard.stat_saved'))
            ->assertSee('112')
            ->assertSee(__('partner.nav_bookings'));
    }
}
