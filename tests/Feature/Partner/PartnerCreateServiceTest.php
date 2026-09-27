<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\CreateService;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerCreateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_four_service_types(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.service.create'))
            ->assertOk()
            ->assertSee(__('partner.create_service.heading'))
            ->assertSee(__('partner.create_service.helper'))
            ->assertSee(__('partner.create_service.struttura_title'))
            ->assertSee(__('partner.create_service.attivita_title'))
            ->assertSee(__('partner.create_service.servizi_title'))
            ->assertSee(__('partner.create_service.smartbox_title'))
            ->assertSee(__('partner.create_service.next'));
    }

    public function test_next_requires_a_service(): void
    {
        Livewire::test(CreateService::class)
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_next_rejects_an_unknown_service(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'inesistente')
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_struttura_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'struttura')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'struttura']);
    }

    public function test_attivita_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'attivita']);
    }

    public function test_smartbox_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'smartbox')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'smartbox']);
    }

    public function test_servizi_goes_to_the_activity_flow_and_never_shows_the_structure_categories(): void
    {
        // Fino al 29/09/2026 "Servizi" percorreva gli step della struttura
        // ricettiva: un toelettatore o un dog sitter si vedeva chiedere hotel,
        // B&B, agriturismo o casa vacanza. La cliente ha chiesto il contrario.
        Livewire::test(CreateService::class)
            ->set('service', 'servizi')
            ->call('next')
            ->assertHasNoErrors()
            // Salta anche la scelta Attività/Evento: chi clicca "Servizi" l'ha già fatta.
            ->assertRedirect(route('partner.activity.name'));

        $this->assertDatabaseHas('structure_drafts', [
            // 'attivita' e non 'servizi': family() manda 'servizi' su
            // StructurePublisher, e una bozza compilata col wizard attività
            // pubblicata come Struttura sarebbe rotta.
            'service_category' => 'attivita',
            'type' => 'attivita',
        ]);
    }

    /**
     * Dopo una pubblicazione in attesa di Stripe l'id poteva restare in
     * sessione: "Crea servizio" riapriva quella bozza e next() ne riscriveva
     * la categoria, trasformandola nel servizio successivo.
     */
    public function test_crea_servizio_non_riprende_una_bozza_in_attesa(): void
    {
        $partner = $this->actingAsActivePartner();
        $waiting = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'publish_requested_at' => now(),
        ]);
        session(['structure_draft_id' => $waiting->id]);

        Livewire::test(CreateService::class)
            ->assertSet('service', '')
            ->set('service', 'attivita')
            ->call('next')
            ->assertRedirect(route('partner.activity.type'));

        $this->assertSame('smartbox', $waiting->fresh()->service_category);
        $this->assertNotSame($waiting->id, session('structure_draft_id'));
        $this->assertDatabaseHas('structure_drafts', ['user_id' => $partner->id, 'service_category' => 'attivita']);
    }

    /** Dal menu "Crea servizio" durante una modifica: il servizio pubblicato non si riscrive. */
    public function test_crea_servizio_non_riprende_un_servizio_in_modifica(): void
    {
        $partner = $this->actingAsActivePartner();
        $published = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
        ]);
        session(['structure_draft_id' => $published->id]);

        Livewire::test(CreateService::class)
            ->assertSet('service', '')
            ->set('service', 'smartbox')
            ->call('next');

        $this->assertSame('struttura', $published->fresh()->service_category);
        $this->assertNotSame($published->id, session('structure_draft_id'));
    }

    /**
     * Refresh della pagina o "Indietro" dallo step del tipo: si riprende la
     * bozza appena iniziata, con la scelta fatta, senza una riga per visita.
     */
    public function test_una_bozza_appena_iniziata_si_riprende_senza_righe_nuove(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(CreateService::class);
        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next');

        Livewire::test(CreateService::class)->assertSet('service', 'attivita');

        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }

    // ── Difetto W2: «Indietro» dal primo step orfana la bozza in corso ────────
    //
    // `serviceChoiceBackUrl()` manda su `partner.service.create` ogni bozza che
    // non è COMPLETED e non è in attesa. `CreateService::mount()` vede
    // `current_step > 0`, scollega la bozza dalla sessione e ne crea una NUOVA:
    // la vecchia resta con nome, categorie e indirizzo, `draft` e
    // `publish_requested_at` nullo, e `scopeListableFor` — da cui passano TUTTI
    // gli ingressi partner — pretende COMPLETED oppure il segnale. Nessuna
    // schermata la può riaprire.

    /** Bozza in corso, avanzata oltre lo step 0, col lavoro già fatto sopra. */
    private function draftInProgress(int $userId, array $attributes = []): StructureDraft
    {
        $draft = StructureDraft::create(array_merge([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 3,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Educazione cinofila Bau'],
            'activity_categories' => ['educatore'],
            'address' => 'Via Roma 1',
            'city' => 'Milano',
        ], $attributes));

        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    public function test_tornare_alla_scelta_del_servizio_non_perde_la_bozza_in_corso(): void
    {
        $partner = $this->actingAsActivePartner();
        $inProgress = $this->draftInProgress($partner->id);

        // Il partner arriva qui dal pulsante "Indietro" dello step del tipo.
        Livewire::test(CreateService::class);

        $this->assertSame(
            $inProgress->id,
            session('structure_draft_id'),
            'La bozza compilata non deve essere scollegata dalla sessione senza darle un\'altra porta: '
            .'nome, categorie e indirizzo diventano irraggiungibili da qualunque schermata partner.',
        );
        $this->assertSame(
            1,
            StructureDraft::query()->where('user_id', $partner->id)->count(),
            'Nessuna riga nuova: la bozza in corso si riprende, non si abbandona.',
        );
    }

    /**
     * L'altra faccia dello stesso difetto: se anche si accetta di lasciarla
     * indietro, la bozza orfana deve restare raggiungibile da «I miei servizi».
     * Oggi `scopeListableFor` la esclude e non esiste nessun ingresso.
     */
    public function test_una_bozza_orfanata_resta_raggiungibile_dal_partner(): void
    {
        $partner = $this->actingAsActivePartner();
        $orphan = $this->draftInProgress($partner->id);

        Livewire::test(CreateService::class);

        $this->assertTrue(
            StructureDraft::query()->listableFor($partner->id)->whereKey($orphan->id)->exists(),
            'Una bozza con nome, categorie e indirizzo compilati non può sparire da ogni schermata partner.',
        );
    }

    /**
     * Le card «Servizio professionale» ed «Evento» salvano già
     * `current_step = 1`, quindi per loro basta UN «Indietro» perché la bozza
     * venga orfanata: è il percorso più corto al difetto.
     */
    public function test_un_solo_indietro_dalla_card_servizio_professionale_non_perde_la_bozza(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(CreateService::class)
            ->set('service', 'servizi')
            ->call('next')
            ->assertRedirect(route('partner.activity.name'));

        $created = StructureDraft::query()->where('user_id', $partner->id)->sole();
        $this->assertSame(1, $created->current_step);

        // «Indietro» dallo step del nome riporta alla scelta delle card.
        Livewire::test(CreateService::class);

        $this->assertSame($created->id, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }

    /**
     * Il sintomo che il partner vede: il radio si presenta vuoto.
     * `registrationChoice()` trova già una bozza con `service_category` non
     * nulla — proprio l'orfana appena creata — e torna '', quindi nemmeno la
     * preselezione lo salva.
     */
    public function test_tornando_indietro_il_radio_mostra_ancora_la_card_scelta(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->draftInProgress($partner->id, ['service_category' => 'attivita']);

        Livewire::test(CreateService::class)->assertSet('service', 'attivita');
    }

    // ── Difetto W8: la preselezione dall'iscrizione non ha un solo test ───────
    //
    // `registration_service` compare in cinque punti, tutti in app/, models e
    // migrazione: zero in tests/, zero nelle factory, zero nelle viste. Un
    // refactor che invertisse `$alreadyChosen` o rinominasse 'eventi' passerebbe
    // con la suite verde.

    /** Partner la cui iscrizione ha registrato la tipologia scelta allo step 2. */
    private function actingAsPartnerRegisteredAs(?string $choice): User
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create(['registration_service' => $choice]);

        return $partner;
    }

    public function test_la_card_scelta_in_iscrizione_arriva_preselezionata(): void
    {
        $this->actingAsPartnerRegisteredAs('eventi');

        Livewire::test(CreateService::class)->assertSet('service', 'eventi');
    }

    public function test_ogni_slug_dell_iscrizione_corrisponde_a_una_card(): void
    {
        // Gli slug validati in iscrizione ('in:struttura,attivita,servizi,eventi')
        // devono essere chiavi di questa pagina: rinominarne uno da un lato
        // spegnerebbe la preselezione in silenzio.
        foreach (['struttura', 'attivita', 'servizi', 'eventi'] as $slug) {
            // Un partner nuovo per slug: `registrationChoice()` guarda le bozze
            // di CHI è loggato, quindi ognuno parte pulito.
            session()->forget('structure_draft_id');
            $this->actingAsPartnerRegisteredAs($slug);

            Livewire::test(CreateService::class)->assertSet(
                'service',
                $slug,
                "Lo slug '{$slug}' dell'iscrizione deve corrispondere a una card di «Crea servizio».",
            );
        }
    }

    public function test_un_partner_iscritto_prima_della_colonna_non_preseleziona_nulla(): void
    {
        // Colonna NULL (partner storici, partner creati dall'admin): il radio
        // resta muto senza errori, non con un valore inventato.
        $this->actingAsPartnerRegisteredAs(null);

        Livewire::test(CreateService::class)->assertSet('service', '');
    }

    public function test_un_valore_fuori_elenco_non_preseleziona_nulla(): void
    {
        $this->actingAsPartnerRegisteredAs('evento');

        Livewire::test(CreateService::class)
            ->assertSet('service', '')
            ->assertHasNoErrors();
    }

    public function test_la_preselezione_si_spegne_dopo_la_prima_scelta(): void
    {
        $partner = $this->actingAsPartnerRegisteredAs('struttura');

        // Primo ingresso: la card dell'iscrizione è preselezionata, ma il partner
        // sceglie un'altra cosa.
        Livewire::test(CreateService::class)
            ->assertSet('service', 'struttura')
            ->set('service', 'smartbox')
            ->call('next')
            ->assertRedirect(route('partner.smartbox.type'));

        // Dal giro dopo vince la scelta del funnel: la bozza ha già una
        // service_category, quindi non si preseleziona più niente.
        session()->forget('structure_draft_id');

        Livewire::test(CreateService::class)->assertSet('service', '');

        // E nel profilo resta scritto ciò che ha scelto iscrivendosi.
        $this->assertSame('struttura', $partner->partnerProfile->fresh()->registration_service);
    }
}
