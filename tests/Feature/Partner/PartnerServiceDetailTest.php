<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerServiceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $userId, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'type' => 'hotel',
            'service_category' => 'struttura',
            'name' => 'Hotel Brescia',
            'city' => 'Darfo Boario Terme',
            'province' => 'BS',
            'description' => 'Hotel pet-friendly.',
        ], $attributes));
    }

    public function test_owner_sees_the_service_detail_sections(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee('Hotel Brescia')
            ->assertSee(__('partner.services.section_type'))
            ->assertSee(__('partner.services.section_rooms'))
            ->assertSee(__('partner.services.section_payment'))
            ->assertSee('Hotel pet-friendly.');
    }

    // ── Difetto F7: il dettaglio non mostra nessuno dei campi nuovi ───────────
    //
    // `rows()` compone dieci righe letterali e non contiene le colonne nate dalle
    // risposte della cliente del 26-27/09/2026: il partner non può rileggere
    // quello che ha salvato senza ripercorrere gli undici step.

    /** Evento pubblicato con tutti i campi nuovi compilati. */
    private function eventWithTheNewFields(int $userId): StructureDraft
    {
        return $this->service($userId, [
            'type' => 'eventi',
            'service_category' => 'attivita',
            'name' => ['it' => 'Sagra del cane'],
            'event_categories' => ['fiere_mercatini'],
            'event_categories_other' => ['it' => 'Sagra paesana di quartiere'],
            'booking_requirement' => 'obbligatoria',
            'recurrence' => 'ricorrente',
            // Valori deliberatamente distintivi: '30' o '10:00' ricomparirebbero
            // nelle classi CSS della pagina e farebbero passare il test per caso.
            'max_participants' => 137,
            'date_start' => '2026-12-01',
            'date_end' => '2026-12-02',
            'time_start' => '10:15',
            'time_end' => '18:45',
        ]);
    }

    public function test_il_dettaglio_di_un_evento_mostra_tipologia_prenotazione_ricorrenza_e_posti(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->eventWithTheNewFields($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.event_category.fiere_mercatini'))
            ->assertSee('Sagra paesana di quartiere')
            ->assertSee(__('partner.booking_requirement.obbligatoria'))
            ->assertSee(__('partner.event_recurrence.ricorrente'))
            // I posti dichiarati: il valore, non l'etichetta.
            ->assertSeeText('137');
    }

    public function test_il_dettaglio_di_un_evento_mostra_le_date_e_gli_orari(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->eventWithTheNewFields($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSeeText('10:15')
            ->assertSeeText('18:45');
    }

    public function test_il_dettaglio_di_unattivita_mostra_categorie_professionali_e_zona(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'type' => 'attivita',
            'service_category' => 'attivita',
            'name' => ['it' => 'Educazione cinofila Bau'],
            'activity_categories' => ['educatore_cinofilo'],
            'activity_categories_other' => ['it' => 'Anche riabilitazione comportamentale'],
            'operating_area' => ['it' => 'Milano e provincia'],
            'detailed_description' => ['it' => 'Lavoro su appuntamento, sempre in piccoli gruppi.'],
        ]);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.activity_category.educatore_cinofilo'))
            ->assertSee('Anche riabilitazione comportamentale')
            ->assertSee('Milano e provincia')
            // La descrizione dettagliata è obbligatoria in questo ramo: il
            // partner deve almeno poterla rileggere (vedi anche W1).
            ->assertSee('Lavoro su appuntamento, sempre in piccoli gruppi.');
    }

    /**
     * La riga «Stanze» viene comunque calcolata per un evento e cade su «Non
     * previsto»: una domanda che a un evento non è mai stata fatta.
     */
    public function test_il_dettaglio_di_un_evento_non_chiede_conto_delle_stanze(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->eventWithTheNewFields($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertDontSee(__('partner.services.section_rooms'));
    }

    public function test_option_keys_render_as_localized_labels(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'services' => ['wifi', 'piscina'],
            'rules' => ['vietato_fumare'],
            'animal_services' => ['area_animali'],
        ]);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.hotel_services.svc_wifi'))       // "Wi-fi gratuito", non "wifi"
            ->assertSee(__('partner.hotel_services.svc_pool'))
            ->assertSee(__('partner.hotel_services.rule_no_smoking'))
            ->assertSee(__('partner.hotel_animal_services.opt_area'))
            ->assertSee(__('partner.structure_type.hotel'));
    }

    public function test_another_users_service_is_forbidden(): void
    {
        $this->actingAsActivePartner();
        $othersDraft = $this->service(User::factory()->create()->id);

        $this->get(route('partner.services.show', $othersDraft))->assertForbidden();
    }

    public function test_a_non_completed_draft_is_forbidden(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, ['status' => StructureDraft::STATUS_DRAFT]);

        $this->get(route('partner.services.show', $draft))->assertForbidden();
    }

    public function test_owner_sees_a_draft_awaiting_stripe_with_the_badge(): void
    {
        app()->setLocale('it');
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee('Hotel Brescia')
            ->assertSee(__('partner.my_services.awaiting_stripe'));
    }

    public function test_another_users_draft_awaiting_stripe_is_forbidden(): void
    {
        $this->actingAsActivePartner();
        $draft = $this->service(User::factory()->create()->id, [
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);

        $this->get(route('partner.services.show', $draft))->assertForbidden();
    }
}
