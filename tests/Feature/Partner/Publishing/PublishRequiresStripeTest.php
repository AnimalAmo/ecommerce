<?php

namespace Tests\Feature\Partner\Publishing;

use App\Exceptions\PartnerNotPayableException;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Decisione della cliente: chi si fa pagare online non pubblica senza
 * verifiche bancarie complete. Con i direct charges non è una regola
 * amministrativa ma una condizione tecnica: senza account connesso il checkout
 * non degrada a commissione zero, esplode. Dal 22/09/2026 chi si fa pagare
 * direttamente (in struttura o sul suo sito) pubblica senza Stripe.
 */
class PublishRequiresStripeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    public function test_un_partner_senza_stripe_non_pubblica(): void
    {
        $draft = $this->smartboxDraftFor(PartnerProfile::factory());

        try {
            app(DraftPublisher::class)->publish($draft);
            $this->fail('Attesa PartnerNotPayableException per partner senza onboarding Stripe.');
        } catch (PartnerNotPayableException $exception) {
            $this->assertSame(__('partner.errors.stripe_onboarding_required'), $exception->getMessage());
        }

        $this->assertSame(0, SmartboxPackage::where('structure_draft_id', $draft->id)->count());
    }

    public function test_un_partner_con_onboarding_a_meta_non_pubblica(): void
    {
        // charges_enabled ma non payouts_enabled: incasserebbe senza poter
        // essere bonificato, cioè soldi fermi sul suo saldo Stripe.
        $draft = $this->smartboxDraftFor(PartnerProfile::factory()->connected()->state([
            'stripe_payouts_enabled' => false,
        ]));

        $this->expectException(PartnerNotPayableException::class);

        app(DraftPublisher::class)->publish($draft);
    }

    public function test_un_partner_collegato_pubblica(): void
    {
        $draft = $this->smartboxDraftFor(PartnerProfile::factory()->connected());

        $published = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(SmartboxPackage::class, $published);
    }

    /**
     * La regola del 22/09/2026 vale ancora per tutto ciò che non è un cofanetto
     * prepagato. Era una smartbox fino al 27/09/2026, quando la cliente ha
     * chiesto di escluderle: una bozza smartbox qui proverebbe l'eccezione, non
     * la regola, e il nome del test diventerebbe bugiardo.
     */
    public function test_un_partner_offline_pubblica_una_struttura_senza_stripe(): void
    {
        $draft = $this->structureDraftFor(PartnerProfile::factory()->offline());

        $published = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Structure::class, $published);
        $this->assertSame($draft->user_id, $published->user_id);
    }

    /**
     * L'eccezione della famiglia smartbox (richiesta della cliente, 27/09/2026):
     * un cofanetto prepagato si vende solo con l'incasso online. Lo stesso
     * partner, nello stesso test, pubblica la sua struttura: il gate è per
     * bozza, non una porta chiusa sul partner.
     */
    public function test_una_smartbox_di_un_partner_offline_non_si_pubblica_ma_la_sua_struttura_si(): void
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->offline()->for($partner)->create();

        $smartbox = $this->smartboxDraft($partner);

        try {
            app(DraftPublisher::class)->publish($smartbox);
            $this->fail('Attesa PartnerNotPayableException per la smartbox di un partner che incassa fuori.');
        } catch (PartnerNotPayableException $exception) {
            // Messaggio suo: «completa il collegamento Stripe» manderebbe a
            // cercare un onboarding a metà che non c'è.
            $this->assertSame(__('partner.errors.smartbox_requires_online_payment'), $exception->getMessage());
        }

        $this->assertSame(0, SmartboxPackage::withHidden()->where('structure_draft_id', $smartbox->id)->count());

        $structure = $this->structureDraft($partner);
        $this->assertInstanceOf(Structure::class, app(DraftPublisher::class)->publish($structure));
    }

    public function test_un_partner_offline_pubblica_un_evento_gratuito_senza_stripe(): void
    {
        // Spec §2.2: gli eventi gratuiti seguono la stessa regola. La bozza ha solo
        // ciò che isPublishable chiede alla famiglia attività: nome it e data di inizio.
        $partner = User::factory()->create();
        PartnerProfile::factory()->offline()->for($partner)->create();

        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Passeggiata a 6 zampe', 'en' => 'Six-legged walk'],
            'date_start' => '2026-10-10',
            'price_type' => 'gratuito',
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);

        $published = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Event::class, $published);
        $this->assertSame($partner->id, $published->user_id);
        $this->assertTrue($published->is_free);
        $this->assertNull($published->price_cents);
    }

    public function test_un_partner_senza_profilo_non_pubblica(): void
    {
        $draft = $this->smartboxDraft(User::factory()->create());

        $this->expectException(PartnerNotPayableException::class);

        app(DraftPublisher::class)->publish($draft);
    }

    /** Bozza smartbox completa, intestata al partner del profilo dato. */
    private function smartboxDraftFor(mixed $profileFactory): StructureDraft
    {
        $partner = User::factory()->create();
        $profileFactory->for($partner)->create();

        return $this->smartboxDraft($partner);
    }

    /** Bozza struttura completa, intestata al partner del profilo dato. */
    private function structureDraftFor(mixed $profileFactory): StructureDraft
    {
        $partner = User::factory()->create();
        $profileFactory->for($partner)->create();

        return $this->structureDraft($partner);
    }

    private function smartboxDraft(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto di prova', 'en' => 'Test box'],
            'price' => '120',
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);
    }

    /**
     * Bozza struttura col minimo che `isPublishable` pretende per il default
     * della famiglia: nome italiano e `rooms` valorizzato.
     */
    private function structureDraft(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel di prova', 'en' => 'Test hotel'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '80']],
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);
    }
}
