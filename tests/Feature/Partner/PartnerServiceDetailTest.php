<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\PartnerServiceDetail;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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

    // ── Giro del tester, 28/09/2026: il dettaglio di ogni famiglia ───────────
    //
    // Le prove F7 qui sopra guardano un evento e un'attività. Qui le righe di
    // ogni famiglia (lette da rows(), non cercando parole nella pagina, che ha
    // un header e un menu), i valori che le prove non vedevano, e le bozze a
    // metà wizard, che la lista elenca e il dettaglio apre (W2).

    /** @return array<string, array<string, mixed>> righe dell'accordion per etichetta */
    private function rowsOf(StructureDraft $draft): array
    {
        $rows = Livewire::test(PartnerServiceDetail::class, ['draft' => $draft])->instance()->rows();

        return array_column($rows, null, 'label');
    }

    /** Il testo di una riga più le sue righe sotto, in una stringa sola. */
    private function rowText(array $rows, string $label): string
    {
        $this->assertArrayHasKey($label, $rows, "Manca la riga «{$label}».");

        return trim(($rows[$label]['text'] ?? '').' '.implode(' | ', $rows[$label]['details'] ?? []));
    }

    public function test_le_righe_di_un_evento_sono_quelle_del_suo_wizard(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->eventWithTheNewFields($partner->id));

        $this->assertArrayHasKey(__('partner.services.section_service_type'), $rows);
        $this->assertArrayHasKey(__('partner.activity_name.field_categories_event'), $rows);
        $this->assertArrayHasKey(__('partner.activity_info.heading'), $rows);
        $this->assertArrayHasKey(__('partner.services.section_cost'), $rows);
        // Domande che il wizard di un evento non fa.
        $this->assertArrayNotHasKey(__('partner.services.section_type'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_rooms'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_payment'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_smartbox'), $rows);
        // La dettagliata la chiede solo il ramo attività, e il publisher la toglie agli eventi.
        $this->assertArrayNotHasKey(__('partner.services.section_detailed_description'), $rows);
    }

    public function test_il_dettaglio_di_un_evento_mostra_punto_dincontro_date_e_costo(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->eventWithTheNewFields($partner->id);
        $draft->update([
            'meeting_point' => ['it' => 'Parco Sempione'],
            'price_type' => 'pagamento',
            'price_per_person' => '25',
        ]);
        $rows = $this->rowsOf($draft->fresh());

        $this->assertStringContainsString(
            __('partner.activity_location.meeting_point').': Parco Sempione',
            $this->rowText($rows, __('partner.services.section_location')),
        );
        $info = $this->rowText($rows, __('partner.activity_info.heading'));
        $this->assertStringContainsString(__('partner.activity_info.date_start').': 01/12/2026', $info);
        $this->assertStringContainsString(__('partner.activity_info.date_end').': 02/12/2026', $info);
        $this->assertStringContainsString(__('partner.activity_info.max_participants').': 137', $info);
        $cost = $this->rowText($rows, __('partner.services.section_cost'));
        $this->assertStringContainsString(__('partner.activity_cost.opt_paid'), $cost);
        $this->assertStringContainsString('€25', $cost);
    }

    /** Un'attività non mostra ciò che il publisher le toglie: ricorrenza e tipologie di evento rimaste da un cambio di ramo. */
    public function test_unattivita_non_mostra_i_residui_del_ramo_evento(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->service($partner->id, [
            'type' => 'attivita',
            'service_category' => 'attivita',
            'name' => ['it' => 'Educazione cinofila Bau'],
            'activity_categories' => ['educatore_cinofilo'],
            'operating_area' => ['it' => 'Milano e provincia'],
            'recurrence' => 'ricorrente',
            'event_categories' => ['fiere_mercatini'],
            'price_type' => 'gratuito',
        ]));

        $this->assertArrayHasKey(__('partner.activity_name.field_categories'), $rows);
        $this->assertArrayNotHasKey(__('partner.activity_name.field_categories_event'), $rows);
        $info = $this->rowText($rows, __('partner.activity_info.heading'));
        $this->assertStringNotContainsString(__('partner.event_recurrence.ricorrente'), $info);
        // All'attività i posti non li chiede il wizard: nessuna riga, nemmeno «Nessun limite».
        $this->assertStringNotContainsString(__('partner.activity_info.max_participants'), $info);
        $this->assertStringContainsString(
            __('partner.activity_location.operating_area').': Milano e provincia',
            $this->rowText($rows, __('partner.services.section_location')),
        );
        $this->assertSame(__('partner.activity_cost.opt_free'), $this->rowText($rows, __('partner.services.section_cost')));
    }

    /** Posti vuoti su un evento: «Nessun limite» solo dopo lo step 5, prima è una domanda non ancora fatta. */
    public function test_i_posti_vuoti_di_un_evento_dicono_nessun_limite_solo_dopo_lo_step_che_li_chiede(): void
    {
        $partner = $this->actingAsActivePartner();
        $answered = $this->eventWithTheNewFields($partner->id);
        $answered->update(['max_participants' => null]);
        $halfway = $this->service($partner->id, [
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 3,
            'type' => 'eventi',
            'service_category' => 'attivita',
            'name' => ['it' => 'Sagra a metà'],
        ]);

        $this->assertStringContainsString(
            __('partner.activity_info.max_participants').': '.__('partner.services.max_participants_unlimited'),
            $this->rowText($this->rowsOf($answered->fresh()), __('partner.activity_info.heading')),
        );
        $this->assertStringNotContainsString(
            __('partner.services.max_participants_unlimited'),
            $this->rowText($this->rowsOf($halfway), __('partner.activity_info.heading')),
        );
    }

    public function test_le_righe_di_una_smartbox_sono_quelle_del_suo_wizard(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->service($partner->id, [
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Weekend a sei zampe'],
            'current_step' => 12,
        ]));

        $this->assertSame(__('partner.smartbox_type.soggiorno'), $this->rowText($rows, __('partner.services.section_service_type')));
        $this->assertArrayNotHasKey(__('partner.services.section_type'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_location'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_rooms'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_payment'), $rows);
        $this->assertArrayNotHasKey(__('partner.services.section_smartbox'), $rows);
    }

    /** L'hotel tiene le sue righe, compresa l'adesione alle smartbox (W6, parte rileggibile). */
    public function test_le_righe_di_un_hotel_comprendono_stanze_pagamento_e_adesione(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->service($partner->id, [
            'smartbox_consent' => 'si',
            'smartbox_types' => ['benessere', 'avventura'],
            'iban' => 'IT60X0542811101000000123456',
        ]));

        $this->assertSame(__('partner.structure_type.hotel'), $this->rowText($rows, __('partner.services.section_type')));
        $this->assertArrayHasKey(__('partner.services.section_rooms'), $rows);
        $this->assertStringContainsString('IT60X0542811101000000123456', $this->rowText($rows, __('partner.services.section_payment')));
        $smartbox = $this->rowText($rows, __('partner.services.section_smartbox'));
        $this->assertStringStartsWith(__('partner.hotel_smartbox.yes'), $smartbox);
        $this->assertStringContainsString(__('partner.hotel_smartbox.type_wellness'), $smartbox);
        $this->assertStringContainsString(__('partner.hotel_smartbox.type_adventure'), $smartbox);
    }

    /** Adesione negata: le tipologie rimaste salvate non si mostrano, sarebbe un sì che il partner non ha dato. */
    public function test_con_ladesione_negata_le_tipologie_non_compaiono(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->service($partner->id, [
            'smartbox_consent' => 'no',
            'smartbox_types' => ['benessere'],
        ]));

        $this->assertSame(__('partner.hotel_smartbox.no'), $this->rowText($rows, __('partner.services.section_smartbox')));
    }

    /** Le bozze storiche `servizi` sono strutture (familyOf): hanno le righe della struttura. */
    public function test_una_bozza_storica_servizi_ha_le_righe_della_struttura(): void
    {
        $partner = $this->actingAsActivePartner();
        $rows = $this->rowsOf($this->service($partner->id, ['service_category' => 'servizi']));

        $this->assertArrayHasKey(__('partner.services.section_type'), $rows);
        $this->assertArrayHasKey(__('partner.services.section_rooms'), $rows);
        $this->assertArrayHasKey(__('partner.services.section_payment'), $rows);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function halfwayDrafts(): array
    {
        return [
            'hotel' => ['struttura', 'hotel'],
            'servizio professionale' => ['attivita', 'attivita'],
            'evento' => ['attivita', 'eventi'],
            'smartbox' => ['smartbox', 'soggiorno'],
        ];
    }

    /**
     * Bozza in corso con solo il nome: la lista la elenca (W2) e il dettaglio
     * la apre. Ogni colonna è vuota, e la pagina deve reggere e dire «Non
     * specificato», non esplodere su un null.
     */
    #[DataProvider('halfwayDrafts')]
    public function test_il_dettaglio_di_una_bozza_a_meta_si_apre_in_ogni_famiglia(string $category, string $type): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => StructureDraft::STARTED_STEP,
            'service_category' => $category,
            'type' => $type,
            'name' => ['it' => 'Servizio a metà'],
        ]);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee('Servizio a metà')
            ->assertSee(__('partner.services.not_provided'));
    }
}
