<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\AwaitingDraftPublisher;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Riprende le bozze chiuse quando il partner non poteva ancora essere pagato.
 * Due account.updated ravvicinati, il comando schedulato e il cambio di
 * modalità possono arrivare insieme: ogni bozza si pubblica una volta sola.
 */
class AwaitingDraftPublisherTest extends TestCase
{
    use RefreshDatabase;

    private function publisher(): AwaitingDraftPublisher
    {
        return app(AwaitingDraftPublisher::class);
    }

    private function unpayablePartner(): User
    {
        $partner = User::factory()->create();
        PartnerProfile::factory()->for($partner)->create();

        return $partner;
    }

    private function awaitingSmartboxOf(User $partner, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '120',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'publish_requested_at' => now()->subHour(),
        ], $attributes));
    }

    private function packagesOf(StructureDraft $draft): int
    {
        return SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->count();
    }

    public function test_pubblica_le_bozze_in_attesa_di_un_partner_ora_pagabile(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $first = $this->awaitingSmartboxOf($partner);
        $second = $this->awaitingSmartboxOf($partner, ['name' => ['it' => 'Secondo cofanetto']]);

        $this->assertSame(2, $this->publisher()->publishFor($partner->id));

        foreach ([$first, $second] as $draft) {
            $fresh = $draft->fresh();
            $this->assertSame(StructureDraft::STATUS_COMPLETED, $fresh->status);
            $this->assertNull($fresh->publish_requested_at);
            $this->assertSame(1, $this->packagesOf($draft));
        }
    }

    public function test_un_partner_passato_offline_pubblica_le_bozze_in_attesa(): void
    {
        $partner = User::factory()->offlinePartner()->create();
        $draft = $this->awaitingSmartboxOf($partner);

        $this->assertSame(1, $this->publisher()->publishFor($partner->id));
        $this->assertSame(1, $this->packagesOf($draft));
    }

    public function test_un_partner_ancora_non_pagabile_resta_in_attesa(): void
    {
        $partner = $this->unpayablePartner();
        $draft = $this->awaitingSmartboxOf($partner);
        $requestedAt = $draft->publish_requested_at;

        $this->assertSame(0, $this->publisher()->publishFor($partner->id));

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_DRAFT, $fresh->status);
        // Il controllo è prima della chiusura: il segnale non si riscrive a ogni giro.
        $this->assertTrue($fresh->publish_requested_at->equalTo($requestedAt));
        $this->assertSame(0, $this->packagesOf($draft));
    }

    public function test_un_partner_disattivato_non_pubblica_anche_se_pagabile(): void
    {
        // Il wizard è dietro il middleware `partner`, il job e il comando no:
        // un partner disattivato (o anonimizzato) dopo aver parcheggiato la
        // bozza non deve finire a catalogo al primo account.updated.
        $partner = User::factory()->stripeConnected()->inactive()->create();
        $draft = $this->awaitingSmartboxOf($partner);

        $this->assertSame(0, $this->publisher()->publishFor($partner->id));

        $this->assertTrue($draft->fresh()->isAwaitingPublication());
        $this->assertSame(StructureDraft::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertSame(0, $this->packagesOf($draft));
    }

    public function test_tocca_solo_le_bozze_in_attesa_del_partner(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $other = User::factory()->stripeConnected()->create();

        $mine = $this->awaitingSmartboxOf($partner);
        $plain = $this->awaitingSmartboxOf($partner, ['publish_requested_at' => null]);
        $theirs = $this->awaitingSmartboxOf($other);

        $this->assertSame(1, $this->publisher()->publishFor($partner->id));

        $this->assertSame(1, $this->packagesOf($mine));
        $this->assertSame(StructureDraft::STATUS_DRAFT, $plain->fresh()->status);
        $this->assertSame(0, $this->packagesOf($plain));
        $this->assertTrue($theirs->fresh()->isAwaitingPublication());
        $this->assertSame(0, $this->packagesOf($theirs));
    }

    public function test_e_idempotente_se_gira_due_volte(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $draft = $this->awaitingSmartboxOf($partner);

        $this->assertSame(1, $this->publisher()->publishFor($partner->id));
        $this->assertSame(0, $this->publisher()->publishFor($partner->id));

        $this->assertSame(1, $this->packagesOf($draft));
    }

    public function test_la_modifica_in_attesa_di_un_servizio_pubblicato_aggiorna_la_stessa_riga(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $draft = $this->awaitingSmartboxOf($partner, [
            'status' => StructureDraft::STATUS_COMPLETED,
            'publish_requested_at' => null,
        ]);
        app(DraftPublisher::class)->publish($draft);
        $draft->update(['price' => '150', 'publish_requested_at' => now()]);

        $this->assertSame(1, $this->publisher()->publishFor($partner->id));

        $package = SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->sole();
        $this->assertSame(15000, $package->price_cents);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
    }

    public function test_una_bozza_in_attesa_non_pubblicabile_resta_in_attesa_e_si_logga_una_volta(): void
    {
        Log::spy();
        $partner = User::factory()->stripeConnected()->create();
        $draft = $this->awaitingSmartboxOf($partner, ['price' => null]);

        // Due giri del comando: il log non si ripete ogni dieci minuti.
        $this->assertSame(0, $this->publisher()->publishFor($partner->id));
        $this->assertSame(0, $this->publisher()->publishFor($partner->id));

        $this->assertTrue($draft->fresh()->isAwaitingPublication());
        $this->assertSame(StructureDraft::STATUS_DRAFT, $draft->fresh()->status);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Bozza in attesa di Stripe non pubblicabile', Mockery::on(
                fn (array $context): bool => $context['structure_draft_id'] === $draft->id,
            ));
    }
}
