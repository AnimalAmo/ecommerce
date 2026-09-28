<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\DeleteServiceModal;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerMyServicesTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $userId, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'name' => 'Hotel Brescia',
            'city' => 'Darfo Boario Terme',
            'province' => 'BS',
            // Pubblicabile: senza stanze il badge direbbe "mancano dei dati",
            // che e' vero ma non e' cio' che queste prove vogliono verificare.
            'rooms' => [['name' => 'Camera doppia', 'guests' => 2]],
        ], $attributes));
    }

    public function test_it_lists_only_the_partners_completed_services(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, ['name' => 'Hotel Brescia']);
        $this->service($partner->id, ['name' => 'Puppy Yoga', 'status' => StructureDraft::STATUS_DRAFT]); // draft: hidden
        $this->service(User::factory()->create()->id, ['name' => 'Altrui Resort']);                       // altro utente: hidden

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel Brescia')
            ->assertSee('Darfo Boario Terme (BS), Italia')
            ->assertSee(__('partner.services.view_details'))
            ->assertDontSee('Puppy Yoga')
            ->assertDontSee('Altrui Resort');
    }

    public function test_it_lists_the_drafts_awaiting_stripe_with_a_badge(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio in attesa',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);
        $this->service($partner->id, ['name' => 'Hotel Brescia']);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Rifugio in attesa')
            ->assertSee('Hotel Brescia')
            ->assertSeeHtmlInOrder(['Rifugio in attesa', e(__('partner.my_services.awaiting_stripe'))]);
    }

    public function test_a_waiting_draft_missing_data_says_so_instead_of_blaming_stripe(): void
    {
        // La diagnosi falsa che ha generato la segnalazione del 29/09/2026:
        // qualunque bozza ferma leggeva "in attesa del collegamento Stripe".
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio senza stanze',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
            'rooms' => null,
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.incomplete'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_a_ready_draft_of_a_payable_partner_says_it_is_going_live(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $this->service($partner->id, [
            'name' => 'Rifugio pronto',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.publishing'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_a_listing_waiting_for_approval_says_so(): void
    {
        // Moderazione accesa: la scheda e' a catalogo ma invisibile, e prima
        // l'area partner non aveva modo di dirlo.
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $draft = $this->service($partner->id, ['name' => 'Hotel in moderazione']);
        Structure::factory()->create([
            'user_id' => $partner->id,
            'structure_draft_id' => $draft->id,
            'approval_status' => Structure::APPROVAL_PENDING,
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_approval'));
    }

    public function test_a_published_listing_carries_no_badge(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsPayablePartner();
        $draft = $this->service($partner->id, ['name' => 'Hotel online']);
        Structure::factory()->create(['user_id' => $partner->id, 'structure_draft_id' => $draft->id]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel online')
            ->assertDontSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.publishing'))
            ->assertDontSee(__('partner.my_services.awaiting_approval'));
    }

    /**
     * Bozza smartbox di un partner che incassa fuori dalla piattaforma: il badge
     * dice qual è la cosa da fare, non «in attesa del collegamento Stripe» —
     * che per chi non userà mai Stripe è la stessa diagnosi falsa che ha
     * generato la segnalazione del 29/09/2026.
     */
    public function test_una_smartbox_ferma_dice_che_serve_il_sistema_di_pagamento(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->offline()->for($partner)->create();

        $this->smartboxService($partner->id, [
            'name' => 'Cofanetto fermo',
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee(__('partner.my_services.awaiting_payment_method'))
            ->assertSee(__('partner.my_services.awaiting_payment_method_hint'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'))
            ->assertDontSee(__('partner.my_services.publishing'));
    }

    /**
     * Smartbox RITIRATA (`withheld_at`): la riga a catalogo c'è, quindi senza il
     * ramo dedicato il badge direbbe «Sospesa» — cioè accuserebbe l'admin di un
     * "togli struttura" che nessuno ha fatto. È la ragione per cui quel ramo sta
     * prima del controllo sulla riga.
     */
    public function test_una_smartbox_ritirata_non_risulta_sospesa(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->offline()->for($partner)->create();

        $draft = $this->smartboxService($partner->id, ['name' => 'Cofanetto ritirato']);

        SmartboxPackage::factory()->create([
            'user_id' => $partner->id,
            'structure_draft_id' => $draft->id,
            'withheld_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Cofanetto ritirato')
            ->assertSee(__('partner.my_services.awaiting_payment_method'))
            ->assertDontSee(__('partner.my_services.suspended'))
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    /** Bozza smartbox completa: `price` è ciò che `isPublishable` pretende per la famiglia. */
    private function smartboxService(int $userId, array $attributes = []): StructureDraft
    {
        return $this->service($userId, array_merge([
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'price' => '120',
            'current_step' => 12,
            'rooms' => null,
        ], $attributes));
    }

    public function test_the_badge_is_only_on_drafts_awaiting_stripe(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $this->service($partner->id, ['name' => 'Hotel Brescia']);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Hotel Brescia')
            ->assertDontSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_edit_reopens_a_draft_awaiting_stripe(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'status' => StructureDraft::STATUS_DRAFT,
            'service_category' => 'smartbox',
            'publish_requested_at' => now(),
        ]);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.smartbox.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    public function test_empty_state_when_no_services(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerMyServices::class)->assertSee(__('partner.services.empty'));
    }

    public function test_page_is_reachable_by_an_active_partner(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.services'))->assertOk()->assertSee(__('partner.services.heading'));
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('partner.services'))->assertRedirect(route('home'));
    }

    public function test_edit_loads_the_draft_and_returns_to_the_structure_flow(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, ['type' => 'hotel']);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.structure.type'));

        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    public function test_edit_routes_each_family_to_its_own_flow(): void
    {
        $partner = $this->actingAsActivePartner();
        $activity = $this->service($partner->id, ['service_category' => 'attivita', 'type' => 'attivita', 'current_step' => 10]);
        $smartbox = $this->service($partner->id, ['service_category' => 'smartbox', 'type' => 'soggiorno', 'current_step' => 12]);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $activity->id)
            ->assertRedirect(route('partner.activity.type'));

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $smartbox->id)
            ->assertRedirect(route('partner.smartbox.type'));
    }

    public function test_edit_ignores_services_of_other_partners(): void
    {
        $this->actingAsActivePartner();
        $other = User::factory()->create();
        $foreign = $this->service($other->id);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $foreign->id)
            ->assertNoRedirect();

        $this->assertNull(session('structure_draft_id'));
    }

    // ── Difetto W2: le bozze in corso ora compaiono, e si riprendono ──────────
    //
    // Prima una bozza a metà wizard scollegata dalla sessione restava a
    // database con nome e indirizzo e nessuna schermata la riapriva (caso reale:
    // Agriturismo Metina, 27/09/2026). `listableFor` — la scope di lista,
    // modifica, eliminazione e dettaglio — ora le include dallo step del nome,
    // e «Riprendi» riporta al primo step non ancora salvato.

    /** Bozza a metà wizard: `draft`, senza segnale, oltre lo step del nome. */
    private function inProgress(int $userId, array $attributes = []): StructureDraft
    {
        return $this->service($userId, array_merge([
            'name' => 'Agriturismo Metina',
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 4,
            'rooms' => null,
        ], $attributes));
    }

    /**
     * Si riparte dal primo step non salvato, per famiglia: `current_step` è lo
     * step più avanzato raggiunto, quindi la ripresa è lo step successivo. Le
     * rotte attese sono scritte qui a mano, non chieste al model: la prova è
     * sul posto in cui il partner atterra.
     */
    public function test_riprendi_porta_al_primo_step_non_salvato_e_rimette_la_sessione(): void
    {
        $partner = $this->actingAsActivePartner();

        $cases = [
            'partner.structure.hotel.cancellation' => ['service_category' => 'struttura', 'current_step' => 5],
            'partner.activity.description' => ['service_category' => 'attivita', 'type' => 'attivita', 'current_step' => 3],
            'partner.smartbox.description' => ['service_category' => 'smartbox', 'type' => 'soggiorno', 'current_step' => 2],
        ];

        foreach ($cases as $route => $attributes) {
            session()->forget('structure_draft_id');
            $draft = $this->inProgress($partner->id, $attributes);

            Livewire::test(PartnerMyServices::class)
                ->call('resume', $draft->id)
                ->assertRedirect(route($route));

            $this->assertSame($draft->id, session('structure_draft_id'), "La ripresa verso {$route} deve rimettere la bozza in sessione.");
        }
    }

    /**
     * L'ultimo step dell'attività scrive 10, e la chiusura a 11 la scrive
     * DraftCompleter: una bozza ferma a 10 si riprende dallo step 10 stesso, non
     * da una rotta inesistente.
     */
    public function test_riprendi_all_ultimo_step_resta_dentro_il_wizard(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->inProgress($partner->id, ['service_category' => 'attivita', 'type' => 'attivita', 'current_step' => 10]);

        Livewire::test(PartnerMyServices::class)
            ->call('resume', $draft->id)
            ->assertRedirect(route('partner.activity.cancellation'));
    }

    public function test_riprendi_ignora_le_bozze_di_un_altro_partner(): void
    {
        $this->actingAsActivePartner();
        $foreign = $this->inProgress(User::factory()->create()->id);

        Livewire::test(PartnerMyServices::class)
            ->call('resume', $foreign->id)
            ->assertNoRedirect();

        $this->assertNull(session('structure_draft_id'));
    }

    /** «Riprendi» è solo delle bozze in corso: un servizio completato si apre con edit(). */
    public function test_riprendi_non_apre_un_servizio_completato(): void
    {
        $partner = $this->actingAsActivePartner();
        $completed = $this->service($partner->id);

        Livewire::test(PartnerMyServices::class)
            ->call('resume', $completed->id)
            ->assertNoRedirect();

        $this->assertNull(session('structure_draft_id'));
    }

    public function test_una_bozza_in_corso_compare_con_badge_e_riprendi(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $draft = $this->inProgress($partner->id);
        $completed = $this->service($partner->id, ['name' => 'Hotel Brescia']);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Agriturismo Metina')
            ->assertSee(__('partner.my_services.draft'))
            ->assertSee(__('partner.my_services.draft_hint'))
            ->assertSee(__('partner.my_services.resume'))
            ->assertSeeHtml('wire:click="resume('.$draft->id.')"')
            // Il servizio completato nella stessa lista non si "riprende".
            ->assertSee('Hotel Brescia')
            ->assertDontSeeHtml('wire:click="resume('.$completed->id.')"');
    }

    /**
     * Sotto lo step del nome la bozza porta solo card e tipologia: ogni visita a
     * «Crea servizio» ne apre una, ed elencarle riempirebbe la lista di righe
     * senza nome. Il nome qui è messo apposta: si prova la soglia dello step,
     * non l'assenza del nome.
     */
    public function test_una_bozza_appena_nata_non_compare_in_lista(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->inProgress($partner->id, ['name' => 'Nata allo step zero', 'current_step' => 0]);
        $this->inProgress($partner->id, ['name' => 'Nata allo step uno', 'current_step' => 1]);
        $this->inProgress($partner->id, ['name' => 'Arrivata al nome', 'current_step' => 2]);

        Livewire::test(PartnerMyServices::class)
            ->assertDontSee('Nata allo step zero')
            ->assertDontSee('Nata allo step uno')
            ->assertSee('Arrivata al nome');
    }

    /**
     * Lo step di chiusura è di famiglia: 11 per struttura e attività, 12 per la
     * smartbox, che ha una sezione in più. La scope lo calcola in SQL (case
     * when), il badge in PHP (isInProgress): una smartbox a 11 è ancora a metà,
     * una struttura a 11 senza segnale è una chiusura tentata e annullata.
     */
    public function test_lo_step_di_chiusura_dipende_dalla_famiglia(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $smartbox = $this->inProgress($partner->id, [
            'name' => 'Cofanetto a metà',
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'current_step' => 11,
        ]);
        $this->inProgress($partner->id, ['name' => 'Struttura chiusa e annullata', 'current_step' => 11]);

        Livewire::test(PartnerMyServices::class)
            ->assertSee('Cofanetto a metà')
            ->assertSeeHtml('wire:click="resume('.$smartbox->id.')"')
            ->assertDontSee('Struttura chiusa e annullata');

        Livewire::test(PartnerMyServices::class)
            ->call('resume', $smartbox->id)
            ->assertRedirect(route('partner.smartbox.price'));
    }

    /** Il dettaglio usa la stessa scope della lista: una bozza elencata si apre, senza 403. */
    public function test_una_bozza_in_corso_si_apre_nel_dettaglio(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->inProgress($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee('Agriturismo Metina');
    }

    /** Stessa scope anche per l'eliminazione: una bozza in corso si toglie, senza riga a catalogo da ritirare. */
    public function test_una_bozza_in_corso_si_elimina(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->inProgress($partner->id);

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $draft->id)
            ->assertSet('serviceId', $draft->id)
            ->call('delete')
            ->assertDispatched('service-deleted');

        $this->assertDatabaseMissing('structure_drafts', ['id' => $draft->id]);
    }

    /** Una bozza allo step 0/1 non è elencata, quindi non si elimina da qui con un id riscritto. */
    public function test_una_bozza_appena_nata_non_si_elimina_da_qui(): void
    {
        $partner = $this->actingAsActivePartner();
        $fresh = $this->inProgress($partner->id, ['current_step' => 1]);

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $fresh->id)
            ->assertSet('serviceId', null)
            ->set('serviceId', $fresh->id)
            ->call('delete');

        $this->assertDatabaseHas('structure_drafts', ['id' => $fresh->id]);
    }
}
