<?php

namespace Tests\Feature\Partner;

use App\Jobs\PublishAwaitingDrafts;
use App\Livewire\Admin\People\UserShow;
use App\Livewire\Commerce\Cart;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Livewire\Partner\Profile\PartnerProfilePayment;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Cart\CartManager;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Difetto C8 (audit 27/09/2026, corretto il 28/09/2026): il ritiro delle
 * smartbox quando il partner passa al pagamento diretto, e il ritorno in
 * vetrina quando torna online. I quattro test della sezione «Difetto C8» di
 * PartnerPaymentModeServiceTest provano il service; qui le strade vere da cui
 * la modalità cambia (profilo partner, pannello admin), quello che NON deve
 * muoversi (eventi, altri partner, chi resta online, la sospensione dell'admin)
 * e quello che il ritiro trascina con sé: il carrello di chi aveva il
 * cofanetto, la dashboard e «I miei servizi» del partner.
 *
 * Scritto dal tester il 28/09/2026.
 */
class SmartboxWithholdingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    // ── Da dove la modalità cambia ───────────────────────────────────────────

    public function test_dal_profilo_del_partner_il_pagamento_diretto_ritira_le_sue_smartbox(): void
    {
        $partner = $this->actingAsPayablePartner();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);

        Livewire::test(PartnerProfilePayment::class)
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'https://www.hotelrosovino.it')
            ->call('savePaymentMode')
            ->assertHasNoErrors();

        $this->assertTrue(SmartboxPackage::withHidden()->findOrFail($box->id)->isWithheld());
        $this->assertNull(SmartboxPackage::query()->find($box->id));
    }

    public function test_dal_pannello_admin_il_pagamento_diretto_ritira_le_smartbox_del_partner(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        Role::findOrCreate('partner', 'web');
        $partner->assignRole('partner');
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);

        $this->actingAsSuperadmin();

        Livewire::test(UserShow::class, ['user' => $partner])
            ->call('editPaymentMode')
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'https://lecorti.example/prenota')
            ->call('setPaymentMode')
            ->assertHasNoErrors();

        $this->assertTrue(SmartboxPackage::withHidden()->findOrFail($box->id)->isWithheld());
    }

    // ── Cosa NON si muove ────────────────────────────────────────────────────

    /** La sezione C8 prova le strutture; gli eventi mancavano. */
    public function test_il_pagamento_diretto_non_ritira_eventi_e_attivita(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $event = Event::factory()->create(['user_id' => $partner->id]);
        $activity = Event::factory()->activity(2)->create(['user_id' => $partner->id]);

        $this->modes()->set($partner->partnerProfile, false, null);

        $this->assertNotNull(Event::query()->find($event->id), 'Un evento si vende anche in struttura: resta in vetrina.');
        $this->assertNotNull(Event::query()->find($activity->id));
    }

    public function test_il_pagamento_diretto_non_tocca_le_smartbox_degli_altri_partner(): void
    {
        $leaving = User::factory()->stripeConnected()->create();
        $other = User::factory()->stripeConnected()->create();
        $mine = SmartboxPackage::factory()->create(['user_id' => $leaving->id]);
        $theirs = SmartboxPackage::factory()->create(['user_id' => $other->id]);

        $this->modes()->set($leaving->partnerProfile, false, null);

        $this->assertTrue(SmartboxPackage::withHidden()->findOrFail($mine->id)->isWithheld());
        $this->assertNotNull(SmartboxPackage::query()->find($theirs->id));
    }

    /**
     * Chi resta online e salva solo il link (o non ha ancora finito Stripe) non
     * perde le sue smartbox: il ritiro è della modalità, non di Stripe.
     */
    public function test_chi_resta_online_senza_stripe_non_perde_le_smartbox(): void
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);

        $this->modes()->set($partner->partnerProfile, true, 'https://example.com/prenota');

        $this->assertNull(SmartboxPackage::withHidden()->findOrFail($box->id)->withheld_at);
    }

    /** Un secondo salvataggio in pagamento diretto (per esempio del solo link) non sposta la data del ritiro. */
    public function test_risalvare_il_pagamento_diretto_non_riscrive_la_data_del_ritiro(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 28, 9, 0, 0));
        $partner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id]);

        $this->modes()->set($partner->partnerProfile->fresh(), false, null);

        Carbon::setTestNow(Carbon::create(2026, 9, 29, 9, 0, 0));
        $this->modes()->set($partner->partnerProfile->fresh(), false, 'https://example.com/nuovo');

        $this->assertSame(
            '2026-09-28 09:00:00',
            SmartboxPackage::withHidden()->findOrFail($box->id)->withheld_at->format('Y-m-d H:i:s'),
        );

        Carbon::setTestNow();
    }

    /**
     * Ritiro e sospensione sono due colonne. Tornare online annulla il ritiro,
     * non la sospensione dell'admin: il cofanetto sospeso resta fuori, e il
     * pannello continua a dirlo «Sospesa».
     */
    public function test_tornare_online_non_riattiva_una_smartbox_sospesa_dall_admin(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id, 'suspended_at' => now()]);

        $this->modes()->set($partner->partnerProfile->fresh(), false, null);
        $this->assertTrue(SmartboxPackage::withHidden()->findOrFail($box->id)->isWithheld());

        $this->modes()->set($partner->partnerProfile->fresh(), true, null);

        $fresh = SmartboxPackage::withHidden()->findOrFail($box->id);
        $this->assertFalse($fresh->isWithheld());
        $this->assertTrue($fresh->isSuspended());
        $this->assertNull(SmartboxPackage::query()->find($box->id));
        $this->assertSame(CatalogAdmin::STATUS_SUSPENDED, app(CatalogAdmin::class)->status($fresh));
    }

    // ── Il ritorno in vetrina ────────────────────────────────────────────────

    /**
     * Col cofanetto nato dal wizard: tornando online torna in vetrina subito,
     * non al giro del job (qui la coda è finta e il job non gira).
     *
     * E il job di ripubblicazione NON parte (review del 28/09/2026): il ritiro
     * non segna più la bozza, quindi non c'è niente in attesa da ripubblicare.
     * Prima il job ripubblicava la bozza, e una modifica riaperta e lasciata a
     * metà nel wizard sarebbe andata online così.
     */
    public function test_tornando_online_la_smartbox_con_bozza_torna_subito_senza_ripubblicare_la_bozza(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $draft = $this->smartboxDraftOf($partner);
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id, 'structure_draft_id' => $draft->id]);

        $this->modes()->set($partner->partnerProfile->fresh(), false, null);

        Queue::fake();
        $this->modes()->set($partner->partnerProfile->fresh(), true, null);

        $this->assertNotNull(SmartboxPackage::query()->find($box->id));
        Queue::assertNotPushed(PublishAwaitingDrafts::class);
    }

    // ── Cosa il ritiro trascina con sé ───────────────────────────────────────

    /**
     * Il ritiro è un update di massa (niente eventi del modello): la riga nel
     * carrello di un cliente resta finché il carrello non si rilegge, e lì esce
     * con l'avviso del ritiro (difetto C9). Senza, il cofanetto restava in
     * carrello di un partner che non incassa più online.
     */
    public function test_la_smartbox_ritirata_esce_dal_carrello_del_cliente_con_l_avviso(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        $partner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $partner->id, 'price_cents' => 21500]);

        $customer = User::factory()->create();
        $this->actingAs($customer);
        app(CartManager::class)->addItem('smartbox_package', $box->id, ['animals' => ['cane' => 1]], false);

        $this->modes()->set($partner->partnerProfile->fresh(), false, null);

        Livewire::test(Cart::class)
            ->assertSee(__('cart.notice.withheld', ['title' => $box->title]));

        $this->assertDatabaseCount('cart_items', 0);
        Carbon::setTestNow();
    }

    /**
     * Dopo il ritiro il partner lo legge in dashboard e in «I miei servizi»:
     * senza, passava al pagamento diretto e il suo cofanetto spariva dalla
     * vetrina senza una riga di spiegazione. La dashboard conta la riga
     * ritirata, non un segnale sulla bozza (review del 28/09/2026: il segnale
     * avrebbe ripubblicato al ritorno online anche una modifica a metà).
     */
    public function test_dopo_il_ritiro_il_partner_lo_legge_in_dashboard_e_in_i_miei_servizi(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->smartboxDraftOf($partner);
        SmartboxPackage::factory()->create(['user_id' => $partner->id, 'structure_draft_id' => $draft->id]);

        // Lo stesso oggetto che la guard tiene per l'utente loggato: nel test le
        // richieste successive non ricaricano il profilo, in produzione sì.
        $this->modes()->set($partner->partnerProfile, false, null);

        $this->assertFalse($draft->fresh()->isAwaitingPublication(), 'Il ritiro non segna la bozza.');

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('partner.dashboard.smartbox_payment_banner', 1, ['count' => 1]))
            ->assertDontSee(__('partner.dashboard.awaiting_stripe_cta'));

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_payment_method'))
            ->assertDontSee(__('partner.my_services.suspended'));
    }

    /** Strutture ed eventi del partner non ricevono il segnale: non sono fermi. */
    public function test_il_segnale_va_solo_alle_bozze_delle_smartbox_ritirate(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $hotelDraft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel pubblicato'],
        ]);
        Structure::factory()->create(['user_id' => $partner->id, 'structure_draft_id' => $hotelDraft->id]);

        $this->modes()->set($partner->partnerProfile->fresh(), false, null);

        $this->assertFalse($hotelDraft->fresh()->isAwaitingPublication());
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * Il danno che il segnale sulla bozza avrebbe fatto (review del
     * 28/09/2026): il partner riapre la smartbox pubblicata, cambia il nome
     * nel wizard e abbandona a metà; poi passa al pagamento diretto e torna
     * online. La scheda torna in vetrina com'era: la modifica lasciata a metà
     * non va online, e la bozza non si ritrova col segnale.
     */
    public function test_tornando_online_una_modifica_lasciata_a_meta_non_va_in_vetrina(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->smartboxDraftOf($partner);
        $box = SmartboxPackage::factory()->create([
            'user_id' => $partner->id,
            'structure_draft_id' => $draft->id,
            'title' => ['it' => 'Cofanetto relax'],
        ]);

        // Modifica riaperta da «I miei servizi» e lasciata a metà: saveStep()
        // scrive sulla stessa bozza completata, senza ripubblicare.
        $draft->update(['name' => ['it' => 'Nome a metà, mai confermato'], 'current_step' => 3]);

        $this->modes()->set($partner->partnerProfile, false, null);
        $this->modes()->set($partner->partnerProfile->fresh(), true, null);

        $fresh = SmartboxPackage::withHidden()->findOrFail($box->id);
        $this->assertNull($fresh->withheld_at, 'Tornato online e pagabile, il cofanetto torna in vetrina.');
        $this->assertSame('Cofanetto relax', $fresh->getTranslation('title', 'it'));
        $this->assertFalse($draft->fresh()->isAwaitingPublication());
    }

    private function modes(): PartnerPaymentModeService
    {
        return app(PartnerPaymentModeService::class);
    }

    /** Bozza smartbox completata e pubblicabile (`price` è ciò che isPublishable pretende). */
    private function smartboxDraftOf(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto relax'],
            'price' => '99',
        ]);
    }
}
