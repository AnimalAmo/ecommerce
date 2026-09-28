<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityDescription;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_description_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.description'))
            ->assertOk()
            ->assertSee(__('partner.activity_description.heading'))
            ->assertSee(__('partner.activity_description.step'))
            ->assertSee(__('partner.activity_description.section'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
            ->assertSee(__('partner.activity_description.chars'))
            ->assertSee(__('partner.activity_description.next'));
    }

    public function test_event_needs_only_the_short_description(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'eventi']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->assertSet('isActivity', false)
            ->assertDontSee(__('partner.activity_description.detailed_label'))
            ->set('description.it', 'Un evento cinofilo imperdibile.')
            ->set('description.en', 'An unmissable dog event.')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.info'));

        $draft->refresh();
        $this->assertSame('Un evento cinofilo imperdibile.', $draft->getTranslation('description', 'it'));
        $this->assertSame('An unmissable dog event.', $draft->getTranslation('description', 'en'));
        $this->assertSame(4, $draft->current_step);
    }

    public function test_activity_also_requires_the_detailed_description(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'attivita']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->assertSet('isActivity', true)
            ->assertSee(__('partner.activity_description.detailed_label'))
            ->set('description.it', 'Passeggiata guidata.')
            ->call('next')
            ->assertHasErrors('detailedDescription.it')
            ->set('detailedDescription.it', 'Percorso di 3 ore tra i boschi con soste.')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame('Percorso di 3 ore tra i boschi con soste.', $draft->getTranslation('detailed_description', 'it'));
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 4, 'type' => 'eventi']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->set('description.it', 'Un evento cinofilo imperdibile.')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Un evento cinofilo imperdibile.', $draft->getTranslation('description', 'en'));
        $this->assertSame(['it' => 'Un evento cinofilo imperdibile.'], $draft->getTranslations('description'));
    }

    public function test_rejects_a_description_over_200_chars(): void
    {
        Livewire::test(ActivityDescription::class)
            ->set('description.it', str_repeat('a', 201))
            ->call('next')
            ->assertHasErrors('description.it');
    }

    // ── Giro del tester, 28/09/2026: W5 sulle due descrizioni ────────────────
    //
    // Una traduzione inglese salvata non si toglieva più: `array_filter` faceva
    // cadere la chiave e spatie non rimuove i locale assenti. La correzione è
    // stata provata dalla lane solo sul nome; qui le due descrizioni, e che
    // l'italiano resti.

    public function test_svuotare_linglese_delle_due_descrizioni_lo_toglie_e_litaliano_resta(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 4,
            'type' => 'attivita',
            'description' => ['it' => 'Passeggiata guidata.', 'en' => 'Guided walk.'],
            'detailed_description' => ['it' => 'Tre ore nei boschi.', 'en' => 'Three hours in the woods.'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            // La fixture arriva davvero allo step (altrimenti la prova sarebbe verde a vuoto).
            ->assertSet('description.en', 'Guided walk.')
            ->assertSet('detailedDescription.en', 'Three hours in the woods.')
            ->set('description.en', '')
            ->set('detailedDescription.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame(['it' => 'Passeggiata guidata.'], $draft->getTranslations('description'));
        $this->assertSame(['it' => 'Tre ore nei boschi.'], $draft->getTranslations('detailed_description'));
        // Su /en il visitatore legge il ripiego italiano.
        $this->assertSame('Passeggiata guidata.', $draft->getTranslation('description', 'en'));
    }

    /** Sull'evento la dettagliata non si chiede: lo step non la tocca, nemmeno la sua traduzione. */
    public function test_levento_non_tocca_la_descrizione_dettagliata(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 4,
            'type' => 'eventi',
            'description' => ['it' => 'Sagra.', 'en' => 'Fair.'],
            'detailed_description' => ['it' => 'Residuo del ramo attività.'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityDescription::class)
            ->set('description.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame(['it' => 'Sagra.'], $draft->getTranslations('description'));
        $this->assertSame(['it' => 'Residuo del ramo attività.'], $draft->getTranslations('detailed_description'));
    }
}
