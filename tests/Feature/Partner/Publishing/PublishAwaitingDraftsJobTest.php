<?php

namespace Tests\Feature\Partner\Publishing;

use App\Jobs\PublishAwaitingDrafts;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Il job si prova in due modi. Con Queue::fake si verifica che la dispatch
 * sia davvero unica per partner: il lucchetto si prende in PendingDispatch,
 * anche con la coda finta. Separatamente si verifica che, quando gira, pubblichi.
 */
class PublishAwaitingDraftsJobTest extends TestCase
{
    use RefreshDatabase;

    private function awaitingSmartboxOf(User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '120',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'publish_requested_at' => now(),
        ]);
    }

    public function test_e_un_job_unico_per_partner_che_parte_dopo_il_commit(): void
    {
        $job = new PublishAwaitingDrafts(42);

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame('42', $job->uniqueId());
        $this->assertTrue($job->afterCommit);
    }

    public function test_due_dispatch_per_lo_stesso_partner_ne_accodano_una(): void
    {
        // Due account.updated ravvicinati: una pubblicazione in coda per
        // partner, non una per evento. Un altro partner ha la sua.
        Queue::fake();
        $partner = User::factory()->stripeConnected()->create();
        $other = User::factory()->stripeConnected()->create();
        $draft = $this->awaitingSmartboxOf($partner);

        PublishAwaitingDrafts::dispatch($partner->id);
        PublishAwaitingDrafts::dispatch($partner->id);
        PublishAwaitingDrafts::dispatch($other->id);

        Queue::assertPushedTimes(PublishAwaitingDrafts::class, 2);
        Queue::assertPushed(PublishAwaitingDrafts::class, fn (PublishAwaitingDrafts $job): bool => $job->partnerId === $partner->id);
        Queue::assertPushed(PublishAwaitingDrafts::class, fn (PublishAwaitingDrafts $job): bool => $job->partnerId === $other->id);
        $this->assertTrue($draft->fresh()->isAwaitingPublication());
    }

    public function test_quando_gira_pubblica_le_bozze_in_attesa_del_partner(): void
    {
        $partner = User::factory()->stripeConnected()->create();
        $draft = $this->awaitingSmartboxOf($partner);

        PublishAwaitingDrafts::dispatch($partner->id);

        $this->assertSame(StructureDraft::STATUS_COMPLETED, $draft->fresh()->status);
        $this->assertSame(1, SmartboxPackage::withHidden()->where('structure_draft_id', $draft->id)->count());
    }
}
