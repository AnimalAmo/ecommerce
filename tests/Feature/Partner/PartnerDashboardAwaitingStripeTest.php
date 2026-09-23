<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Senza questo il partner finiva il wizard, tornava in dashboard e il
 * servizio non c'era da nessuna parte: né a catalogo né in "I miei servizi".
 */
class PartnerDashboardAwaitingStripeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function awaitingOf(int $userId): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 11,
            'service_category' => 'struttura',
            'name' => ['it' => 'Hotel in attesa'],
            'publish_requested_at' => now(),
        ]);
    }

    public function test_il_banner_conta_i_servizi_in_attesa_e_porta_al_profilo_pagamento(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->awaitingOf($partner->id);
        $this->awaitingOf($partner->id);
        $this->awaitingOf(User::factory()->create()->id);

        $html = $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('partner.dashboard.awaiting_stripe_banner', 2, ['count' => 2]))
            ->getContent();

        // La rotta del profilo è già nel menu dell'header: si cerca l'ancora col testo del CTA.
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('partner.profile.payment'), '/').'"[^>]*>(?:(?!<\/a>).)*'
                .preg_quote(__('partner.dashboard.awaiting_stripe_cta'), '/').'/s',
            $html,
        );
    }

    public function test_senza_servizi_in_attesa_il_banner_non_c_e(): void
    {
        $this->actingAsActivePartner();
        $this->awaitingOf(User::factory()->create()->id);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertDontSee(__('partner.dashboard.awaiting_stripe_cta'));
    }

    public function test_chi_puo_gia_pubblicare_non_vede_collega_stripe(): void
    {
        // Una bozza in attesa di un partner già pagabile è una bozza non
        // pubblicabile: "Collega Stripe" gli chiederebbe qualcosa che ha già fatto.
        $partner = $this->actingAsPayablePartner();
        $this->awaitingOf($partner->id);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertDontSee(__('partner.dashboard.awaiting_stripe_cta'));
    }

    public function test_l_avviso_di_fine_wizard_si_vede_una_volta(): void
    {
        $this->actingAsActivePartner();
        session()->flash('partner.notice', __('partner.publish.awaiting_stripe'));
        // Chiude la richiesta che ha lasciato il flash (Livewire, in produzione,
        // passa da StartSession). Senza, lo store di test è lo stesso oggetto e
        // il flash invecchia solo al save della prima GET: si vedrebbe anche nella seconda.
        session()->ageFlashData();

        $this->get(route('partner.dashboard'))->assertOk()->assertSee(__('partner.publish.awaiting_stripe'));
        $this->get(route('partner.dashboard'))->assertOk()->assertDontSee(__('partner.publish.awaiting_stripe'));
    }
}
