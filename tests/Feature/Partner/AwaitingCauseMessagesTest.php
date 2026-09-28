<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Livewire\Partner\Smartbox\SmartboxPrice;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Difetti F2 e F3 (audit 27/09/2026, corretti il 28/09/2026): perché una
 * scheda non è online, detto al partner con la causa giusta. Una smartbox è
 * ferma per due cause diverse — la modalità di incasso (chi si fa pagare in
 * struttura) o Stripe da finire (chi è online) — e i messaggi le schiacciavano
 * in una. È il cuore della segnalazione della cliente: «alcune strutture hanno
 * collegato Stripe ma la scheda non viene pubblicata».
 *
 * Le sezioni «Difetto F2» di CompleteDraftOutcomeTest e «Difetto F3» di
 * PartnerDashboardAwaitingStripeTest provano il servizio nuovo e la
 * dashboard. Qui: le due varianti «modifiche» dell'avviso di fine wizard
 * (quattro casi, causa × modifica), e gli altri punti che leggono la causa —
 * la lista e il dettaglio di «I miei servizi», la dashboard quando in attesa
 * c'è solo una smartbox.
 *
 * Scritto dal tester il 28/09/2026.
 */
class AwaitingCauseMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    // ── L'avviso di fine wizard: causa × modifica ────────────────────────────

    /** Una smartbox già completata riaperta da chi incassa in struttura: l'avviso «modifiche» della modalità. */
    public function test_la_modifica_di_una_smartbox_di_chi_incassa_in_struttura_nomina_la_modalita(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $draft = $this->smartboxInSession($partner, ['status' => StructureDraft::STATUS_COMPLETED]);

        $this->closeSmartboxWizard();

        $this->assertSame(__('partner.publish.awaiting_payment_method_changes'), session('partner.notice'));
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
        $this->assertTrue($draft->fresh()->isAwaitingPublication());
    }

    /** La stessa modifica da chi è online e deve solo finire Stripe: l'avviso «modifiche» di Stripe. */
    public function test_la_modifica_di_una_smartbox_di_chi_manca_solo_stripe_nomina_stripe(): void
    {
        $partner = $this->actingAsOnlinePartnerWithoutStripe();
        $this->smartboxInSession($partner, ['status' => StructureDraft::STATUS_COMPLETED]);

        $this->closeSmartboxWizard();

        $this->assertSame(__('partner.publish.awaiting_stripe_changes'), session('partner.notice'));
    }

    /** Il testo che il partner legge in dashboard, per la smartbox nuova di chi incassa in struttura. */
    public function test_la_dashboard_mostra_l_avviso_della_modalita_e_non_quello_di_stripe(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $this->smartboxInSession($partner);

        $this->closeSmartboxWizard();

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.publish.awaiting_payment_method'))
            ->assertDontSee(__('partner.publish.awaiting_stripe'))
            ->assertDontSee(__('partner.dashboard.awaiting_stripe_cta'));
    }

    // ── «I miei servizi»: lista e dettaglio dicono la stessa cosa ────────────

    /** Il negativo della sezione F3: per chi incassa in struttura il badge resta quello della modalità. */
    public function test_la_lista_dice_serve_il_sistema_di_pagamento_a_chi_incassa_in_struttura(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $this->awaitingSmartboxOf($partner);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_payment_method'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_il_dettaglio_dice_serve_il_sistema_di_pagamento_a_chi_incassa_in_struttura(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $draft = $this->awaitingSmartboxOf($partner);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.my_services.awaiting_payment_method'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    /**
     * La lista, corretta da F3, dice «In attesa del collegamento Stripe» alla
     * smartbox di chi è online e deve solo finire Stripe. Il dettaglio della
     * stessa bozza sceglie il badge con la sua copia della regola vecchia
     * (`canPublishFamily('smartbox') !== true`, detail.blade.php:29-30) e dice
     * ancora «Serve il sistema di pagamento»: aperta la scheda, il partner
     * legge un'altra causa da quella della lista.
     *
     * Trovato dal tester il 28/09/2026 e chiuso lo stesso giorno: il dettaglio
     * legge ora la causa con needsOnlinePaymentFor(), come la lista.
     */
    public function test_il_dettaglio_dice_la_stessa_causa_della_lista_a_chi_manca_solo_stripe(): void
    {
        $partner = $this->actingAsOnlinePartnerWithoutStripe();
        $draft = $this->awaitingSmartboxOf($partner);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_stripe'));

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.awaiting_payment_method'));
    }

    // ── La dashboard quando in attesa c'è solo una smartbox ──────────────────

    /**
     * Partner online senza Stripe con in attesa SOLO una smartbox. Prima di F3
     * vedeva il banner smartbox (con il bottone verso il profilo pagamento), e
     * per lui non era falso: in AnimalAmo il sistema di pagamento è il conto
     * Stripe. La correzione di F3 ha tolto la smartbox dal banner smartbox, ma
     * il banner Stripe non la conta (esclude le smartbox di proposito), quindi
     * oggi la dashboard non dice niente: la smartbox è ferma, nessun avviso,
     * nessun bottone. È la stessa segnalazione della cliente — «ho collegato
     * Stripe e la scheda non va online» — vista dal lato di chi non l'ha
     * ancora collegato e non sa di doverlo fare.
     *
     * Regressione della prima correzione di F3, trovata dal tester il
     * 28/09/2026 e chiusa lo stesso giorno: le smartbox di un partner online
     * aspettano Stripe come il resto, e ora si contano nel banner Stripe (uno
     * solo, col conteggio di tutto ciò che aspetta). Il banner smartbox resta
     * a chi incassa in struttura.
     */
    public function test_a_chi_manca_solo_stripe_con_solo_una_smartbox_la_dashboard_dice_qualcosa(): void
    {
        $partner = $this->actingAsOnlinePartnerWithoutStripe();
        $this->awaitingSmartboxOf($partner);

        $html = $this->get(route('partner.dashboard'))->assertOk()->getContent();

        // La rotta del profilo è già nel menu dell'header: si cerca l'ancora col testo di uno dei due CTA.
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="'.preg_quote(route('partner.profile.payment'), '/').'"[^>]*>(?:(?!<\/a>).)*'
                .'(?:'.preg_quote(__('partner.dashboard.awaiting_stripe_cta'), '/')
                .'|'.preg_quote(__('partner.dashboard.smartbox_payment_cta'), '/').')/s',
            $html,
            'Una smartbox ferma senza nessun avviso in dashboard: il partner non sa che deve finire Stripe.',
        );
    }

    /**
     * La riga già a catalogo e ritirata (review del 28/09/2026). La migrazione
     * del 27/09 ha ritirato anche le smartbox di chi incassa online ma non ha
     * finito Stripe, segnandone la bozza. Per quel partner la lista diceva
     * «Serve il sistema di pagamento» dal ramo della riga ritirata, mentre
     * dettaglio e dashboard dicevano Stripe: la stessa divergenza di F3,
     * sopravvissuta quando la riga esiste.
     */
    public function test_una_smartbox_ritirata_di_chi_manca_solo_stripe_parla_di_stripe_anche_in_lista(): void
    {
        $partner = $this->actingAsOnlinePartnerWithoutStripe();
        $draft = $this->awaitingSmartboxOf($partner);
        $draft->update(['status' => StructureDraft::STATUS_COMPLETED]);
        SmartboxPackage::factory()->create([
            'user_id' => $partner->id,
            'structure_draft_id' => $draft->id,
            'withheld_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.awaiting_payment_method'));
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /** Online (default del profilo) ma senza Stripe. */
    private function actingAsOnlinePartnerWithoutStripe(): User
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        return $partner;
    }

    /** Smartbox pubblicabile in sessione, ferma allo step del prezzo. */
    private function smartboxInSession(User $partner, array $attributes = []): StructureDraft
    {
        $draft = StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 11,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto relax'],
            'price' => '99',
        ], $attributes));
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    private function closeSmartboxWizard(): void
    {
        Livewire::test(SmartboxPrice::class)
            ->set('price', '99')
            ->call('save')
            ->assertRedirect(route('partner.dashboard'));
    }

    /** Smartbox chiusa dal partner e ferma in attesa. */
    private function awaitingSmartboxOf(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '99',
            'publish_requested_at' => now(),
        ]);
    }
}
