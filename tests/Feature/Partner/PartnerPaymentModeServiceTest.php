<?php

namespace Tests\Feature\Partner;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PaymentModeException;
use App\Jobs\PublishAwaitingDrafts;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Regole del cambio di modalità (22/09/2026): passare a offline è sempre
 * permesso, tornare online richiede Stripe operativo. Senza profilo si
 * resta al comportamento di prima, cioè online.
 */
class PartnerPaymentModeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function modes(): PartnerPaymentModeService
    {
        return app(PartnerPaymentModeService::class);
    }

    public function test_un_partner_online_passa_in_struttura_senza_stripe(): void
    {
        $profile = PartnerProfile::factory()->create();

        $saved = $this->modes()->set($profile, false, 'https://www.hotelrosovino.it');

        $this->assertSame($profile->id, $saved->id);
        $this->assertDatabaseHas('partner_profiles', [
            'id' => $profile->id,
            'online_payment' => false,
            'payment_url' => 'https://www.hotelrosovino.it',
        ]);
    }

    public function test_un_partner_offline_senza_stripe_non_torna_online(): void
    {
        $profile = PartnerProfile::factory()->offline()->create();

        try {
            $this->modes()->set($profile, true, null);
            $this->fail('Attesa PaymentModeException per il passaggio a online senza Stripe.');
        } catch (PaymentModeException $exception) {
            $this->assertSame(__('partner.payment_mode.errors.stripe_required'), $exception->getMessage());
        }

        $this->assertFalse($profile->fresh()->online_payment);
    }

    public function test_con_onboarding_a_meta_non_si_torna_online(): void
    {
        // charges ma non payouts: incasserebbe senza poter essere bonificato.
        $profile = PartnerProfile::factory()->connected()->create([
            'online_payment' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $this->expectException(PaymentModeException::class);

        $this->modes()->set($profile, true, null);
    }

    public function test_un_partner_offline_con_stripe_torna_online(): void
    {
        $profile = PartnerProfile::factory()->connected()->create(['online_payment' => false]);

        $this->modes()->set($profile, true, null);

        $this->assertTrue($profile->fresh()->online_payment);
    }

    public function test_restare_online_senza_stripe_non_e_un_passaggio(): void
    {
        // Partner appena iscritto, o creato dall'admin: è già online, e salvare
        // il link non deve pretendere Stripe.
        $profile = PartnerProfile::factory()->create();

        $this->modes()->set($profile, true, 'https://www.hotelrosovino.it');

        $fresh = $profile->fresh();
        $this->assertTrue($fresh->online_payment);
        $this->assertSame('https://www.hotelrosovino.it', $fresh->payment_url);
    }

    public function test_il_link_vuoto_diventa_null_e_quello_pieno_si_ripulisce(): void
    {
        $profile = PartnerProfile::factory()->create(['payment_url' => 'https://vecchio.it']);

        $this->modes()->set($profile, false, '   ');
        $this->assertNull($profile->fresh()->payment_url);

        $this->modes()->set($profile, false, '  https://www.hotelrosovino.it  ');
        $this->assertSame('https://www.hotelrosovino.it', $profile->fresh()->payment_url);
    }

    /**
     * Il link finisce come href nelle pagine B2C e nelle mail: il service lo
     * garantisce http/https per ogni chiamante, non solo per il form del profilo.
     */
    public function test_il_service_rifiuta_un_link_che_non_e_web(): void
    {
        $profile = PartnerProfile::factory()->create(['payment_url' => 'https://vecchio.it']);

        foreach (['javascript:alert(1)', 'ftp://www.hotelrosovino.it', 'https://'.str_repeat('a', 250).'.it'] as $url) {
            try {
                $this->modes()->set($profile, false, $url);
                $this->fail("Atteso il rifiuto del link {$url}.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('paymentUrl', $exception->errors());
            }
        }

        $fresh = $profile->fresh();
        $this->assertTrue($fresh->online_payment);
        $this->assertSame('https://vecchio.it', $fresh->payment_url);
    }

    public function test_senza_profilo_la_modalita_e_online(): void
    {
        $client = User::factory()->create();

        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner(null));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner($client->id));
        $this->assertNull($this->modes()->profileFor($client->id));
        $this->assertNull($this->modes()->profileFor(null));
    }

    public function test_for_owner_legge_la_modalita_del_partner(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $online = User::factory()->stripeConnected()->create();

        $this->assertSame(OrderPaymentMode::OnSite, $this->modes()->forOwner($offline->id));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner($online->id));
        $this->assertTrue($this->modes()->profileFor($offline->id)->is($offline->partnerProfile));
    }

    public function test_for_purchasable_usa_il_titolare_del_prodotto(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $structure = Structure::factory()->create(['user_id' => $offline->id]);
        $orphan = Structure::factory()->create(['user_id' => null]);

        $this->assertSame(OrderPaymentMode::OnSite, $this->modes()->forPurchasable($structure));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forPurchasable($orphan));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forPurchasable(null));
    }

    public function test_la_modalita_si_legge_una_volta_per_richiesta(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $client = User::factory()->create();
        $modes = $this->modes();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $modes->forOwner($offline->id);
        $modes->forOwner($offline->id);
        $modes->profileFor($offline->id);
        // Anche l'assenza del profilo si ricorda.
        $modes->forOwner($client->id);
        $modes->forOwner($client->id);

        $this->assertCount(2, DB::getQueryLog());
    }

    public function test_dopo_il_salvataggio_la_memoria_e_aggiornata(): void
    {
        $partner = User::factory()->create();
        $profile = PartnerProfile::factory()->for($partner)->create();
        $modes = $this->modes();

        $this->assertSame(OrderPaymentMode::Online, $modes->forOwner($partner->id));

        $modes->set($profile, false, null);

        $this->assertSame(OrderPaymentMode::OnSite, $modes->forOwner($partner->id));
    }

    public function test_il_service_vive_quanto_una_richiesta(): void
    {
        $this->assertSame(app(PartnerPaymentModeService::class), app(PartnerPaymentModeService::class));

        $before = app(PartnerPaymentModeService::class);
        app()->forgetScopedInstances();

        $this->assertNotSame($before, app(PartnerPaymentModeService::class));
    }

    private function awaitingSmartboxOf(PartnerProfile $profile): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $profile->user_id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '120',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'publish_requested_at' => now(),
        ]);
    }

    public function test_passare_in_struttura_mette_in_coda_la_pubblicazione_delle_bozze_in_attesa(): void
    {
        Queue::fake();
        $profile = PartnerProfile::factory()->create();
        // Struttura e non smartbox: dal 27/09/2026 un cofanetto di chi incassa
        // in struttura non è pubblicabile, quindi non è più il caso generico.
        $this->awaitingStructureOf($profile);

        $this->modes()->set($profile, false, null);

        Queue::assertPushed(PublishAwaitingDrafts::class, fn (PublishAwaitingDrafts $job): bool => $job->partnerId === $profile->user_id);
    }

    /**
     * Il caso gemello, che prima non esisteva: chi passa al pagamento diretto e
     * ha in attesa SOLO smartbox non accoda niente. Il gate per famiglia le
     * scarterebbe comunque, una transazione per bozza, a ogni cambio di
     * modalità e a ogni giro del cron dei dieci minuti.
     */
    public function test_passare_in_struttura_con_sole_smartbox_in_attesa_non_mette_in_coda(): void
    {
        Queue::fake();
        $profile = PartnerProfile::factory()->create();
        $this->awaitingSmartboxOf($profile);

        $this->modes()->set($profile, false, null);

        Queue::assertNotPushed(PublishAwaitingDrafts::class);
    }

    public function test_senza_bozze_in_attesa_non_mette_in_coda_nulla(): void
    {
        Queue::fake();
        $profile = PartnerProfile::factory()->create();

        $this->modes()->set($profile, false, null);

        Queue::assertNotPushed(PublishAwaitingDrafts::class);
    }

    public function test_chi_resta_online_senza_stripe_non_mette_in_coda(): void
    {
        Queue::fake();
        $profile = PartnerProfile::factory()->create();
        $this->awaitingSmartboxOf($profile);

        $this->modes()->set($profile, true, 'https://www.hotelrosovino.it');

        Queue::assertNotPushed(PublishAwaitingDrafts::class);
    }

    /**
     * Bozza struttura in attesa, col minimo che `isPublishable` pretende per il
     * default della famiglia (nome italiano e `rooms`).
     */
    private function awaitingStructureOf(PartnerProfile $profile): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $profile->user_id,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel in attesa'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '80']],
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 11,
            'publish_requested_at' => now(),
        ]);
    }

    /**
     * Era una smartbox fino al 27/09/2026: passare al pagamento diretto non
     * sblocca più un cofanetto prepagato, quindi la regola («il cambio di
     * modalità pubblica ciò che era in attesa») si prova su una struttura.
     */
    public function test_passare_in_struttura_pubblica_le_bozze_in_attesa(): void
    {
        $profile = PartnerProfile::factory()->create();
        $draft = $this->awaitingStructureOf($profile);

        $this->modes()->set($profile, false, null);

        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
        $this->assertSame(1, Structure::withHidden()->where('structure_draft_id', $draft->id)->count());
    }

    /**
     * L'altra faccia: il cambio di modalità NON manda a catalogo una smartbox in
     * attesa, perché un cofanetto prepagato si vende solo con l'incasso online
     * (richiesta della cliente, 27/09/2026). La bozza resta in attesa, così
     * resta in "I miei servizi" e torna in vetrina da sé al ritorno online.
     */
    public function test_passare_in_struttura_non_pubblica_una_smartbox_in_attesa(): void
    {
        $profile = PartnerProfile::factory()->create();
        $draft = $this->awaitingSmartboxOf($profile);

        $this->modes()->set($profile, false, null);

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_DRAFT, $fresh->status);
        $this->assertTrue($fresh->isAwaitingPublication());
        $this->assertSame(0, SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->count());
    }

    /**
     * Reversibilità del ritiro, end-to-end e senza scorciatoie: il partner torna
     * all'incasso online da `set()` (che nessuno ha modificato) e la smartbox
     * ritirata dalla migrazione-dati torna in vetrina da sé, perché
     * SmartboxPublisher azzera `withheld_at` alla ripubblicazione.
     *
     * È il percorso che la cliente vedrà davvero: nessun comando da lanciare a
     * mano, nessuna seconda strada da tenere in pari.
     */
    public function test_il_ritorno_online_rimette_in_vetrina_la_smartbox_ritirata(): void
    {
        // Offline ma con Stripe operativo: è la sola condizione in cui si torna
        // online (canSwitchToOnline).
        $profile = PartnerProfile::factory()->connected()->create(['online_payment' => false]);
        $draft = $this->awaitingSmartboxOf($profile);

        $withheld = SmartboxPackage::factory()->create([
            'user_id' => $profile->user_id,
            'structure_draft_id' => $draft->id,
            'withheld_at' => now()->subDay(),
        ]);

        // Prima: fuori dal catalogo, visibile solo con withHidden().
        $this->assertNull(SmartboxPackage::find($withheld->id));

        $this->modes()->set($profile, true, null);

        $this->assertNull($withheld->fresh()->withheld_at);
        $this->assertTrue($withheld->fresh()->isVisibleInCatalog());
        // La riga è la stessa, non una seconda copia a catalogo.
        $this->assertSame(1, SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->count());
        $this->assertNotNull(SmartboxPackage::find($withheld->id));
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
        $this->assertNull($draft->fresh()->publish_requested_at);
    }

    /**
     * `forOwners()` esiste per le griglie: decidere card per card se il pulsante
     * carrello ha senso sarebbe una N+1 dentro una vista. Una query per tutti i
     * titolari, e il secondo giro non ne fa nessuna — nemmeno per il titolare
     * SENZA profilo, che è il caso che, non memorizzato, tornerebbe a
     * interrogare il database a ogni card.
     */
    public function test_for_owners_legge_tutti_i_titolari_in_una_query_sola(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $online = User::factory()->stripeConnected()->create();
        $client = User::factory()->create();
        $modes = $this->modes();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Con un duplicato e un null dentro, come arrivano da una pluck().
        $read = $modes->forOwners([$offline->id, $online->id, $client->id, $offline->id, null]);

        $this->assertCount(1, DB::getQueryLog());

        $this->assertSame([
            $offline->id => OrderPaymentMode::OnSite,
            $online->id => OrderPaymentMode::Online,
            // Senza profilo vale il comportamento di prima: online.
            $client->id => OrderPaymentMode::Online,
        ], $read);

        $modes->forOwners([$offline->id, $online->id, $client->id]);
        $modes->forOwner($client->id);
        $modes->profileFor($client->id);

        $this->assertCount(1, DB::getQueryLog());
    }

    // ── Difetto C8: il ritiro non ha il suo gemello ───────────────────────────
    //
    // `withheld_at` è scritta in tre soli punti: la migrazione dati una-tantum,
    // SmartboxPublisher (che la AZZERA) e le definizioni di schema. `set()` salva
    // `online_payment` e chiama solo `publishAwaitingDrafts()`: non tocca nessuna
    // riga di catalogo già pubblicata. Da quel momento una smartbox NUOVA non si
    // pubblica (`canPublishFamily('smartbox')` è false), ma quella vecchia resta
    // in /smartbox — e sulla sua scheda, al posto del pulsante, compare l'invito
    // «prenota col partner», che a un cofanetto prepagato da regalare non si
    // applica.

    public function test_passando_al_pagamento_diretto_le_smartbox_a_catalogo_vengono_ritirate(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $owner->id]);

        $this->modes()->set($owner->partnerProfile, false, null);

        $this->assertNotNull(
            SmartboxPackage::withHidden()->findOrFail($box->id)->withheld_at,
            'Il gemello del ritiro: se una smartbox nuova non si pubblica senza incasso online, '
            .'quella già in vetrina non può restarci.',
        );
    }

    /** La smartbox ritirata non deve più comparire sul sito. */
    public function test_una_smartbox_ritirata_esce_dalla_vetrina(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $owner->id]);

        $this->modes()->set($owner->partnerProfile, false, null);

        $this->assertNull(
            SmartboxPackage::query()->find($box->id),
            'Il global scope del catalogo nasconde ciò che è ritirato: finché withheld_at è nulla '
            .'il cofanetto resta acquistabile con una chiamata forgiata (vedi C7).',
        );
    }

    /** Le altre famiglie non si ritirano: strutture ed eventi si vendono anche in struttura. */
    public function test_il_passaggio_al_pagamento_diretto_non_ritira_strutture_ed_eventi(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $structure = Structure::factory()->create(['user_id' => $owner->id]);

        $this->modes()->set($owner->partnerProfile, false, null);

        $this->assertNull(Structure::withHidden()->findOrFail($structure->id)->withheld_at);
    }

    /** E tornando online il ritiro si annulla: è quello che SmartboxPublisher già fa. */
    public function test_tornando_online_la_smartbox_ritirata_torna_in_vetrina(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $box = SmartboxPackage::factory()->create(['user_id' => $owner->id]);

        $this->modes()->set($owner->partnerProfile->fresh(), false, null);
        $this->modes()->set($owner->partnerProfile->fresh(), true, null);

        $this->assertNull(
            SmartboxPackage::withHidden()->findOrFail($box->id)->withheld_at,
            'Il ritiro è reversibile: chi torna all\'incasso online ritrova il cofanetto in vetrina.',
        );
    }
}
