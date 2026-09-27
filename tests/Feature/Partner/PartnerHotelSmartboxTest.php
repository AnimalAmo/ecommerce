<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelSmartbox;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\Publishing\DraftPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelSmartboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_consent_and_types(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.smartbox'))
            ->assertOk()
            ->assertSee(__('partner.hotel_smartbox.heading'))
            ->assertSee(__('partner.hotel_smartbox.step'))
            ->assertSee(__('partner.hotel_smartbox.yes'))
            ->assertSee(__('partner.hotel_smartbox.types_heading'))
            ->assertSee(__('partner.hotel_smartbox.type_wellness'))
            ->assertSee(__('partner.hotel_smartbox.next'));
    }

    public function test_choosing_no_hides_the_type_selection(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->assertSet('consent', 'si')
            ->assertSee(__('partner.hotel_smartbox.types_heading'))
            ->set('consent', 'no')
            ->assertDontSee(__('partner.hotel_smartbox.types_heading'));
    }

    public function test_rejects_an_invalid_consent(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->set('consent', 'forse')
            ->call('next')
            ->assertHasErrors('consent');
    }

    public function test_accepts_consent_with_types(): void
    {
        Livewire::test(HotelSmartbox::class)
            ->set('consent', 'si')
            ->set('types', ['tutta', 'benessere'])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.photos'));

        $this->assertDatabaseHas('structure_drafts', ['smartbox_consent' => 'si', 'current_step' => 9]);
    }

    public function test_it_rehydrates_the_saved_consent(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 9, 'smartbox_consent' => 'no']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelSmartbox::class)->assertSet('consent', 'no');
    }

    // ── Difetto W6: lo step è interamente inerte ──────────────────────────────
    //
    // `smartbox_consent` e `smartbox_types` compaiono solo negli step (wizard e
    // pannello), nei fillable/cast della bozza, in ServiceOptionLabels e in
    // `ActivityType::clearedFields()`. NESSUN publisher le legge:
    // `StructurePublisher::publish()` non le nomina e `syncAmenities()` usa
    // services/additional/animal. La scelta delle strutture di un cofanetto si fa
    // dall'altro lato, su SmartboxStructures, quindi l'adesione dichiarata non
    // viene mai onorata — e non compare nemmeno in `PartnerServiceDetail::rows()`.
    // Undici risposte che nessuno legge sono undici occasioni di sbagliare.

    /** Struttura completata che ha dichiarato l'adesione e le sue tipologie. */
    private function structureThatJoinedSmartboxes(int $userId): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
            'smartbox_consent' => 'si',
            'smartbox_types' => ['benessere'],
        ]);
    }

    public function test_ladesione_dichiarata_e_almeno_rileggibile_dal_partner(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->structureThatJoinedSmartboxes($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.hotel_smartbox.type_wellness'));
    }

    public function test_ladesione_dichiarata_arriva_alla_riga_di_catalogo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->structureThatJoinedSmartboxes($partner->id);

        $structure = app(DraftPublisher::class)->publish($draft);

        $this->assertNotNull($structure, 'La fixture non è arrivata a catalogo.');
        $this->assertTrue(
            $structure->smartbox_consent === 'si' || $structure->amenities()->where('slug', 'benessere')->exists(),
            'O l\'adesione dichiarata viene onorata da qualche parte (colonna a catalogo o selezione del '
            .'cofanetto), o lo step va tolto: oggi il partner risponde e la risposta non esiste più.',
        );
    }
}
