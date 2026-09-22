<?php

namespace Tests\Feature\Partner;

use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rete di sicurezza della pubblicazione automatica: un worker non è garantito
 * in produzione e un account.updated può non arrivare. Gira in sincrono.
 */
class PublishAwaitingDraftsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function awaitingSmartboxOf(?User $partner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $partner?->id,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto in attesa'],
            'price' => '120',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'publish_requested_at' => now(),
        ]);
    }

    public function test_pubblica_le_bozze_dei_partner_che_ora_possono_pubblicare(): void
    {
        $payable = $this->awaitingSmartboxOf(User::factory()->stripeConnected()->create());
        $offline = $this->awaitingSmartboxOf(User::factory()->offlinePartner()->create());

        $unpayablePartner = User::factory()->create();
        PartnerProfile::factory()->for($unpayablePartner)->create();
        $unpayable = $this->awaitingSmartboxOf($unpayablePartner);

        // Pagabile ma disattivato: il comando non passa dal middleware `partner`.
        $inactive = $this->awaitingSmartboxOf(User::factory()->stripeConnected()->inactive()->create());

        // Bozza della finestra da ospite: senza proprietario non ha chi la venda.
        $orphan = $this->awaitingSmartboxOf(null);

        $this->artisan('animalamo:publish-awaiting-drafts')
            ->expectsOutputToContain('Bozze pubblicate: 2.')
            ->assertSuccessful();

        $this->assertSame(StructureDraft::STATUS_COMPLETED, $payable->fresh()->status);
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $offline->fresh()->status);
        $this->assertTrue($unpayable->fresh()->isAwaitingPublication());
        $this->assertTrue($inactive->fresh()->isAwaitingPublication());
        $this->assertSame(StructureDraft::STATUS_DRAFT, $inactive->fresh()->status);
        $this->assertTrue($orphan->fresh()->isAwaitingPublication());
    }

    public function test_senza_bozze_in_attesa_non_pubblica_nulla(): void
    {
        $this->artisan('animalamo:publish-awaiting-drafts')
            ->expectsOutputToContain('Bozze pubblicate: 0.')
            ->assertSuccessful();
    }

    public function test_e_schedulato_ogni_dieci_minuti_senza_sovrapposizioni(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, 'animalamo:publish-awaiting-drafts'));

        $this->assertNotNull($event, 'Il comando non è schedulato.');
        $this->assertSame('*/10 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        // Il lucchetto di default dura un giorno: un comando ucciso a metà
        // spegnerebbe la rete di sicurezza fino al giorno dopo.
        $this->assertSame(10, $event->expiresAt);
    }
}
