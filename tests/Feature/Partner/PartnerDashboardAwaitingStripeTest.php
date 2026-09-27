<?php

namespace Tests\Feature\Partner;

use App\Models\Partner\PartnerProfile;
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

    // ── Difetto F3: due banner per la stessa causa ────────────────────────────
    //
    // `canPublishFamily('smartbox')` è `requiresOnlinePayment() && canBePaid()`:
    // un solo booleano per due cause. `smartboxAwaitingCount()` lo usa così com'è,
    // mentre `awaitingCount()` usa `canPublish()`, quindi un partner online al
    // quale manca soltanto Stripe vede CONTEMPORANEAMENTE il banner Stripe e il
    // banner smartbox — due diagnosi dello stesso fatto sulla stessa pagina.
    // DraftPublisher e il pannello admin separano le due cause con
    // `! requiresOnlinePayment()`; questi due punti no.

    /** Smartbox chiusa dal partner e ferma in attesa. */
    private function awaitingSmartboxOf(int $userId): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '99',
            'publish_requested_at' => now(),
        ]);
    }

    public function test_a_chi_manca_solo_stripe_la_smartbox_non_aggiunge_un_secondo_banner(): void
    {
        // online_payment resta true (default del profilo): manca solo Stripe.
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        $this->assertTrue($partner->partnerProfile->requiresOnlinePayment());
        $this->assertFalse($partner->partnerProfile->canBePaid());

        $this->awaitingSmartboxOf($partner->id);
        // Una struttura in attesa: è lei che giustifica il banner Stripe.
        $this->awaitingOf($partner->id);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('partner.dashboard.awaiting_stripe_banner', 1, ['count' => 1]))
            ->assertDontSee(
                trans_choice('partner.dashboard.smartbox_payment_banner', 1, ['count' => 1]),
            );
    }

    /**
     * Il negativo: per chi incassa in struttura il banner smartbox è la sola
     * diagnosi giusta, e quello dello Stripe non deve comparire.
     */
    public function test_a_chi_incassa_in_struttura_resta_il_solo_banner_della_smartbox(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->offline()->for($partner)->create();

        $this->awaitingSmartboxOf($partner->id);

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('partner.dashboard.smartbox_payment_banner', 1, ['count' => 1]))
            ->assertDontSee(trans_choice('partner.dashboard.awaiting_stripe_banner', 1, ['count' => 1]));
    }

    /**
     * Il badge di «I miei servizi» per la stessa bozza: a chi è online e manca
     * solo Stripe, `DraftPublicationState` dice «Serve il sistema di pagamento»
     * quando la diagnosi giusta è il collegamento Stripe.
     */
    public function test_il_badge_della_smartbox_di_chi_manca_solo_stripe_parla_di_stripe(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        $this->awaitingSmartboxOf($partner->id);

        $this->get(route('partner.services'))
            ->assertOk()
            ->assertSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.awaiting_payment_method'));
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
