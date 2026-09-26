<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `animalamo:stuck-drafts` risponde alla segnalazione del 29/09/2026: schede
 * compilate che non compaiono online. Il caso peggiore è la bozza pronta senza
 * `publish_requested_at` — la colonna è nata il 22/09 senza backfill, quindi
 * chi ha chiuso il wizard prima di allora è invisibile a tutto.
 */
class StuckDraftsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** Bozza di struttura con i dati minimi per andare a catalogo. */
    private function readyDraft(User $owner, array $attributes = []): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $owner->id,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel pronto'],
            'rooms' => [['name' => 'Camera doppia', 'guests' => 2]],
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 10,
            ...$attributes,
        ]);
    }

    public function test_a_ready_draft_without_the_signal_is_listed_and_fixed(): void
    {
        $draft = $this->readyDraft(User::factory()->stripeConnected()->create());

        $this->artisan('animalamo:stuck-drafts')
            ->expectsOutputToContain('Senza segnale')
            ->assertSuccessful();

        $this->assertNull($draft->fresh()->publish_requested_at);

        $this->artisan('animalamo:stuck-drafts', ['--fix' => true])->assertSuccessful();

        $this->assertNotNull($draft->fresh()->publish_requested_at);
    }

    public function test_fix_leaves_an_abandoned_draft_alone(): void
    {
        // A metà wizard: svegliarla la farebbe comparire in "I miei servizi"
        // col badge di attesa, e la rete di sicurezza la ritenterebbe ogni
        // dieci minuti senza mai riuscirci.
        $abandoned = $this->readyDraft(User::factory()->stripeConnected()->create(), ['current_step' => 3]);

        $this->artisan('animalamo:stuck-drafts', ['--fix' => true])->assertSuccessful();

        $this->assertNull($abandoned->fresh()->publish_requested_at);
    }

    public function test_fix_leaves_an_incomplete_draft_alone(): void
    {
        $incomplete = $this->readyDraft(User::factory()->stripeConnected()->create(), ['rooms' => null]);

        $this->artisan('animalamo:stuck-drafts', ['--fix' => true])->assertSuccessful();

        $this->assertNull($incomplete->fresh()->publish_requested_at);
    }

    public function test_a_draft_already_on_the_catalogue_is_not_reported(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $draft = $this->readyDraft($owner);
        Structure::factory()->create(['user_id' => $owner->id, 'structure_draft_id' => $draft->id]);

        $this->artisan('animalamo:stuck-drafts', ['--fix' => true])->assertSuccessful();

        $this->assertNull($draft->fresh()->publish_requested_at);
    }

    public function test_a_suspended_listing_still_counts_as_published(): void
    {
        // withHidden: una scheda sospesa o in moderazione esiste già a
        // catalogo, e riscriverle il segnale la farebbe ripubblicare.
        $owner = User::factory()->stripeConnected()->create();
        $draft = $this->readyDraft($owner);
        Structure::factory()->create([
            'user_id' => $owner->id,
            'structure_draft_id' => $draft->id,
            'suspended_at' => now(),
        ]);

        $this->artisan('animalamo:stuck-drafts', ['--fix' => true])->assertSuccessful();

        $this->assertNull($draft->fresh()->publish_requested_at);
    }

    public function test_a_waiting_draft_whose_partner_can_now_publish_is_reported(): void
    {
        $this->readyDraft(
            User::factory()->stripeConnected()->create(),
            ['publish_requested_at' => now(), 'current_step' => 11],
        );

        $this->artisan('animalamo:stuck-drafts')
            ->expectsOutputToContain('Pronte ma ferme')
            ->assertSuccessful();
    }
}
