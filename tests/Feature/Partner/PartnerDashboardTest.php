<?php

namespace Tests\Feature\Partner;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_welcome_stats_and_nav(): void
    {
        $partner = $this->actingAsActivePartner(['first_name' => 'Giulia', 'last_name' => 'Bianchi']);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.dashboard.welcome', ['name' => $partner->first_name]))
            ->assertSee(__('partner.dashboard.cta'))
            ->assertSee(__('partner.dashboard.stat_sold'))
            ->assertSee(__('partner.dashboard.stat_cancelled'))
            ->assertSee(__('partner.dashboard.stat_saved'))
            ->assertSee('112')
            ->assertSee(__('partner.nav_bookings'));
    }

    /**
     * Il nome nel saluto è quello di CHI è loggato: due partner diversi vedono
     * due nomi diversi. Il placeholder del mockup ("Susanna") restava lo stesso
     * per tutti, e a fine iscrizione ogni partner si vedeva salutare così.
     */
    public function test_welcome_greets_the_authenticated_partner_by_name(): void
    {
        $partner = $this->actingAsActivePartner(['first_name' => 'Marco', 'last_name' => 'Verdi']);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.dashboard.welcome', ['name' => 'Marco']))
            ->assertDontSee('Susanna');

        $this->assertSame('Marco', $partner->first_name);
    }
}
