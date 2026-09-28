<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

    // ── Difetto F8: i tre gruppi non sono disgiunti ───────────────────────────
    //
    // Sono tre filtri indipendenti sullo stesso insieme. Il secondo guarda solo
    // `canPublishFamily(...) === true`, il terzo fa `reject(isPublishable)`: una
    // bozza in attesa e INCOMPLETA di un partner pagabile compare sotto entrambi.
    // Chi legge segue il docblock, lancia `animalamo:publish-awaiting-drafts`, non
    // vede pubblicare niente e conclude che manca `schedule:run` — mentre la causa
    // è scritta due righe più sotto.

    public function test_una_bozza_in_attesa_e_incompleta_non_finisce_fra_le_pronte_ma_ferme(): void
    {
        // Partner pagabile, bozza in attesa a cui mancano le stanze: non andrà a
        // catalogo qualunque cosa faccia il cron.
        $this->readyDraft(
            User::factory()->stripeConnected()->create(),
            ['publish_requested_at' => now(), 'current_step' => 11, 'rooms' => null],
        );

        $this->artisan('animalamo:stuck-drafts')
            // Il gruppo che la riguarda.
            ->expectsOutputToContain('In attesa ma incomplete')
            // E il secondo gruppo deve dirsi vuoto: se la elenca, manda a
            // incolpare il cron di una bozza che nessun cron può pubblicare.
            ->expectsOutputToContain('Nessuna: niente di pubblicabile è rimasto indietro.')
            ->assertSuccessful();
    }

    /** Il negativo: completa e in attesa, il secondo gruppo la elenca davvero. */
    public function test_una_bozza_in_attesa_e_completa_resta_fra_le_pronte_ma_ferme(): void
    {
        $this->readyDraft(
            User::factory()->stripeConnected()->create(),
            ['publish_requested_at' => now(), 'current_step' => 11],
        );

        $this->artisan('animalamo:stuck-drafts')
            ->expectsOutputToContain('Pronte ma ferme')
            ->expectsOutputToContain('Nessuna: le bozze in attesa hanno tutte i dati per andare a catalogo.')
            ->assertSuccessful();
    }

    // ── Giro del tester, 28/09/2026: F8, in quale gruppo finisce la bozza ────
    //
    // Il negativo qui sopra era verde anche a vuoto sulla sua prima metà:
    // `report()` stampa SEMPRE il titolo del gruppo, quindi «Pronte ma ferme»
    // compare in ogni output, vuoto o no. Qui si guarda dove finisce l'email del
    // partner, fra il titolo del suo gruppo e quello del successivo.

    private const GROUP_1 = 'Senza segnale';

    private const GROUP_2 = 'Pronte ma ferme';

    private const GROUP_3 = 'In attesa ma incomplete';

    /** Output del comando, letto una volta: Artisan::output() svuota il buffer. */
    private string $stuckOutput = '';

    /** Il gruppo in cui compare la riga di `$email`, o null se non compare. */
    private function groupOf(string $email): ?string
    {
        Artisan::call('animalamo:stuck-drafts');
        $output = $this->stuckOutput = Artisan::output();

        $position = strpos($output, $email);

        if ($position === false) {
            return null;
        }

        $group = null;
        foreach ([self::GROUP_1, self::GROUP_2, self::GROUP_3] as $title) {
            $start = strpos($output, $title);
            if ($start !== false && $start < $position) {
                $group = $title;
            }
        }

        return $group;
    }

    /** @return array<string, mixed> bozza in attesa (col segnale) arrivata in fondo al wizard */
    private function waiting(array $attributes = []): array
    {
        return ['publish_requested_at' => now(), 'current_step' => 11, ...$attributes];
    }

    public function test_completa_e_di_un_partner_pagabile_sta_solo_fra_le_pronte_ma_ferme(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $this->readyDraft($owner, $this->waiting());

        $this->assertSame(self::GROUP_2, $this->groupOf($owner->email));
    }

    public function test_incompleta_di_un_partner_pagabile_sta_solo_fra_le_incomplete(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $this->readyDraft($owner, $this->waiting(['rooms' => null]));

        $this->assertSame(self::GROUP_3, $this->groupOf($owner->email));
        $this->assertSame(1, substr_count($this->stuckOutput, $owner->email), 'Una bozza compare in un gruppo solo.');
    }

    public function test_incompleta_di_un_partner_non_pagabile_sta_fra_le_incomplete(): void
    {
        $owner = User::factory()->create();
        $this->readyDraft($owner, $this->waiting(['rooms' => null]));

        $this->assertSame(self::GROUP_3, $this->groupOf($owner->email));
    }

    /** Il docblock lo promette: completa, col segnale, partner non ancora pagabile = aspetta come deve, nessun gruppo. */
    public function test_completa_di_un_partner_non_ancora_pagabile_non_compare_in_nessun_gruppo(): void
    {
        $owner = User::factory()->create();
        $this->readyDraft($owner, $this->waiting());

        $this->assertNull($this->groupOf($owner->email));
    }

    /** Smartbox senza prezzo: stesso criterio di DraftPublisher, famiglia diversa. */
    public function test_una_smartbox_senza_prezzo_di_un_partner_pagabile_sta_fra_le_incomplete(): void
    {
        $owner = User::factory()->stripeConnected()->create();
        $this->readyDraft($owner, $this->waiting([
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'current_step' => 12,
            'price' => null,
        ]));

        $this->assertSame(self::GROUP_3, $this->groupOf($owner->email));
    }
}
