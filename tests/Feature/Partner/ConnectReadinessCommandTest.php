<?php

namespace Tests\Feature\Partner;

use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Censimento pre-live: quali prodotti non hanno un titolare (e quindi non sono
 * acquistabili) e quali partner venderebbero senza poter incassare.
 *
 * Il discrimine fra contenuto vero e mock è la bozza: i publisher scrivono
 * sempre structure_draft_id, i seeder mai. Una riga orfana CON bozza è roba
 * pubblicata davvero nella finestra in cui il wizard era aperto agli ospiti.
 */
class ConnectReadinessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_distingue_il_mock_dal_contenuto_vero_rimasto_senza_titolare(): void
    {
        // Mock del seeder: nessuna bozza dietro.
        Structure::factory()->create(['user_id' => null, 'structure_draft_id' => null]);

        // Pubblicato da un ospite: la bozza c'è e sa di chi è.
        $owner = User::factory()->create();
        $recoverable = Structure::factory()->create([
            'user_id' => null,
            'structure_draft_id' => $this->draftFor($owner)->id,
        ]);

        // Pubblicato da un ospite che non si è mai registrato: nessuno da assegnare.
        $orphanDraft = Structure::factory()->create([
            'user_id' => null,
            'structure_draft_id' => $this->draftFor(null)->id,
        ]);

        $this->artisan('animalamo:connect-readiness')
            ->expectsOutputToContain('structures')
            ->assertSuccessful();

        // Il report non cambia i dati: serve una seconda passata esplicita.
        $this->assertNull($recoverable->fresh()->user_id);
        $this->assertNull($orphanDraft->fresh()->user_id);
    }

    public function test_assign_from_drafts_recupera_solo_cio_che_ha_un_proprietario_certo(): void
    {
        $owner = User::factory()->create();
        $recoverable = Structure::factory()->create([
            'user_id' => null,
            'structure_draft_id' => $this->draftFor($owner)->id,
        ]);
        $orphanDraft = Event::factory()->create([
            'user_id' => null,
            'structure_draft_id' => $this->draftFor(null)->id,
        ]);
        $mock = SmartboxPackage::factory()->create(['user_id' => null, 'structure_draft_id' => null]);

        $this->artisan('animalamo:connect-readiness --assign-from-drafts')->assertSuccessful();

        $this->assertSame($owner->id, $recoverable->fresh()->user_id);
        // Senza un proprietario certo non si inventa: resta da decidere a mano.
        $this->assertNull($orphanDraft->fresh()->user_id);
        $this->assertNull($mock->fresh()->user_id);
    }

    public function test_segnala_i_partner_che_venderebbero_senza_poter_incassare(): void
    {
        $blocked = User::factory()->create(['first_name' => 'Bloccato', 'last_name' => 'Senzastripe']);
        PartnerProfile::factory()->for($blocked)->create(['business_name' => 'Hotel Senza Conto']);
        Structure::factory()->create(['user_id' => $blocked->id]);

        $ready = User::factory()->stripeConnected()->create();
        Structure::factory()->create(['user_id' => $ready->id]);

        $this->artisan('animalamo:connect-readiness')
            ->expectsOutputToContain('Hotel Senza Conto')
            ->assertSuccessful();
    }

    private function draftFor(?User $owner): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $owner?->id,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Bozza'],
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
        ]);
    }
}
