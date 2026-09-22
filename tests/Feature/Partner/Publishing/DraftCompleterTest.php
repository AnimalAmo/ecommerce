<?php

namespace Tests\Feature\Partner\Publishing;

use App\Enums\DraftCompletion;
use App\Exceptions\DraftNotPublishableException;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftCompleter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Chiusura della bozza in un posto solo (spec §5.2). Prima un publish() nullo
 * lasciava la bozza `completed` senza riga a catalogo, e un partner non
 * pagabile perdeva il servizio: niente segnale, niente elenco, sessione sporca.
 */
class DraftCompleterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 10, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function completer(): DraftCompleter
    {
        return app(DraftCompleter::class);
    }

    /** Partner online con Stripe non collegato: pubblicherebbe un prodotto invendibile. */
    private function unpayablePartner(): User
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create();

        return $partner;
    }

    private function smartboxDraftOf(User $partner, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto di prova'],
            'price' => '120',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 11,
        ], $attributes));
    }

    private function packagesOf(StructureDraft $draft): int
    {
        return SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->count();
    }

    public function test_un_partner_pagabile_pubblica_e_chiude_la_bozza(): void
    {
        $draft = $this->smartboxDraftOf(User::factory()->stripeConnected()->create());

        $outcome = $this->completer()->complete($draft, 12);

        $this->assertSame(DraftCompletion::Published, $outcome);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->status);
        $this->assertSame(12, $draft->current_step);
        $this->assertNull($draft->publish_requested_at);
        $this->assertSame(1, $this->packagesOf($draft));
    }

    public function test_un_partner_offline_pubblica_senza_stripe(): void
    {
        $draft = $this->smartboxDraftOf(User::factory()->offlinePartner()->create());

        $this->assertSame(DraftCompletion::Published, $this->completer()->complete($draft, 12));
        $this->assertSame(1, $this->packagesOf($draft));
    }

    public function test_un_partner_non_pagabile_lascia_la_bozza_in_attesa(): void
    {
        $draft = $this->smartboxDraftOf($this->unpayablePartner());

        $outcome = $this->completer()->complete($draft, 12);

        $this->assertSame(DraftCompletion::AwaitingPayout, $outcome);

        // Anche l'istanza del chiamante si rilegge: dopo il rollback non deve
        // restare `completed` in memoria.
        $this->assertSame(StructureDraft::STATUS_DRAFT, $draft->status);

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_DRAFT, $fresh->status);
        $this->assertSame(12, $fresh->current_step);
        $this->assertTrue($fresh->publish_requested_at->equalTo(now()));
        $this->assertSame(0, $this->packagesOf($draft));
    }

    public function test_la_modifica_di_un_servizio_pubblicato_resta_completata_in_attesa(): void
    {
        $draft = $this->smartboxDraftOf($this->unpayablePartner(), [
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
        ]);

        $this->assertSame(DraftCompletion::AwaitingPayout, $this->completer()->complete($draft, 12));

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $fresh->status);
        $this->assertTrue($fresh->isAwaitingPublication());
    }

    public function test_una_bozza_incompleta_e_un_errore_e_non_resta_completata(): void
    {
        $draft = $this->smartboxDraftOf(User::factory()->stripeConnected()->create(), ['price' => null]);

        try {
            $this->completer()->complete($draft, 12);
            $this->fail('Attesa DraftNotPublishableException per una smartbox senza prezzo.');
        } catch (DraftNotPublishableException $exception) {
            $this->assertSame(__('partner.errors.draft_not_publishable'), $exception->getMessage());
            $this->assertSame($draft->id, $exception->draftId);
        }

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_DRAFT, $fresh->status);
        $this->assertSame(11, $fresh->current_step);
        $this->assertNull($fresh->publish_requested_at);
        $this->assertSame(0, $this->packagesOf($draft));
    }

    public function test_una_modifica_incompleta_di_un_servizio_pubblicato_resta_completata(): void
    {
        $draft = $this->smartboxDraftOf(User::factory()->stripeConnected()->create(), [
            'status' => StructureDraft::STATUS_COMPLETED,
            'price' => null,
        ]);

        $this->expectException(DraftNotPublishableException::class);

        try {
            $this->completer()->complete($draft, 12);
        } finally {
            $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
        }
    }

    public function test_la_pubblicazione_azzera_un_segnale_precedente(): void
    {
        $draft = $this->smartboxDraftOf(User::factory()->stripeConnected()->create(), [
            'publish_requested_at' => now()->subDay(),
        ]);

        $this->completer()->complete($draft, 12);

        $this->assertNull($draft->fresh()->publish_requested_at);
    }
}
