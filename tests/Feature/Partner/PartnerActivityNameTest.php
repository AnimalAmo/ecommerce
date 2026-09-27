<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityName;
use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Step 2 "Nome". Dal 27/09/2026 (risposte della cliente) questo step porta
 * anche le DUE liste di tipologie a scelta multipla: le categorie professionali
 * se il ramo è 'attivita', le tipologie di evento se è 'eventi'. Stanno qui e
 * non nello step del tipo perché chi entra dalle card "Servizio professionale"
 * ed "Evento" salta lo step 1.
 */
class PartnerActivityNameTest extends TestCase
{
    use RefreshDatabase;

    /** Bozza del ramo richiesto, messa in sessione (ospite: `user_id` nullo). */
    private function draftInSession(string $type, array $attributes = []): StructureDraft
    {
        $draft = StructureDraft::create(array_merge([
            'status' => 'draft',
            'current_step' => 2,
            'type' => $type,
        ], $attributes));
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /** Prima voce del gruppo, dalla stessa mappa che disegna le caselle. */
    private function firstLabel(string $group): string
    {
        return (string) array_values(ServiceOptionLabels::options($group))[0];
    }

    public function test_page_renders_the_name_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.name'))
            ->assertOk()
            ->assertSee(__('partner.activity_name.heading'))
            ->assertSee(__('partner.activity_name.step'))
            ->assertSee(__('partner.activity_name.field_label'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
            ->assertSee(__('partner.activity_name.next'));
    }

    public function test_next_requires_the_italian_name(): void
    {
        Livewire::test(ActivityName::class)
            ->set('name.en', 'Only English')
            ->call('next')
            ->assertHasErrors('name.it');
    }

    public function test_next_saves_the_translations_and_advances(): void
    {
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Passeggiata coi cani')
            ->set('name.en', 'Dog walking tour')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.location'));

        $draft = StructureDraft::first();
        $this->assertSame('Passeggiata coi cani', $draft->getTranslation('name', 'it'));
        $this->assertSame('Dog walking tour', $draft->getTranslation('name', 'en'));
        $this->assertSame(2, $draft->current_step);
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Passeggiata coi cani')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Passeggiata coi cani', $draft->getTranslation('name', 'en'));
        $this->assertSame(['it' => 'Passeggiata coi cani'], $draft->getTranslations('name'));
    }

    public function test_it_rehydrates_the_saved_translations(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => ['it' => 'Gita al lago', 'en' => 'Lake trip']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityName::class)
            ->assertSet('name.it', 'Gita al lago')
            ->assertSet('name.en', 'Lake trip');
    }

    /**
     * Il ramo professionale mostra le otto categorie e NON le tipologie di
     * evento: «Fiere / Mercatini / Manifestazioni» sotto un toelettatore non
     * vuol dire niente, e spuntata finirebbe in una colonna che il publisher
     * non porta a catalogo su quel ramo.
     */
    public function test_the_activity_branch_shows_the_professional_categories(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->draftInSession('attivita', ['user_id' => $partner->id]);

        $this->get(route('partner.activity.name'))
            ->assertOk()
            ->assertSee(__('partner.activity_name.field_categories'))
            ->assertSee($this->firstLabel('activity_category'))
            ->assertDontSee($this->firstLabel('event_category'));
    }

    public function test_the_event_branch_shows_the_event_types(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->draftInSession('eventi', ['user_id' => $partner->id]);

        $this->get(route('partner.activity.name'))
            ->assertOk()
            ->assertSee($this->firstLabel('event_category'))
            ->assertDontSee($this->firstLabel('activity_category'));
    }

    /**
     * Il testo libero di "Altro" si rivela spuntando la casella, senza submit:
     * per questo la casella è `wire:model.live`. Senza `.live` il campo
     * comparirebbe solo dopo un errore di validazione.
     */
    public function test_the_free_text_of_other_appears_without_a_submit(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityName::class)
            ->assertDontSee(__('partner.activity_name.field_categories_other'))
            ->set('categories', ['altro'])
            ->assertSee(__('partner.activity_name.field_categories_other'));
    }

    public function test_next_saves_the_categories_in_the_activity_column(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Maneggio del Sole')
            ->set('categories', ['maneggio', 'fattoria_didattica'])
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame(['maneggio', 'fattoria_didattica'], $draft->activity_categories);
        // La colonna del ramo abbandonato non viene sfiorata da questo step.
        $this->assertNull($draft->event_categories);
    }

    public function test_next_saves_the_categories_in_the_event_column(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Sagra a sei zampe')
            ->set('categories', ['fiere_mercatini'])
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame(['fiere_mercatini'], $draft->event_categories);
        $this->assertNull($draft->activity_categories);
    }

    public function test_next_saves_the_free_text_of_other_in_the_branch_column(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Pensione per conigli')
            ->set('categories', ['altro'])
            ->set('categoriesOther.it', 'Pensione per conigli')
            ->set('categoriesOther.en', 'Rabbit boarding')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertSame('Pensione per conigli', $draft->getTranslation('activity_categories_other', 'it'));
        $this->assertSame('Rabbit boarding', $draft->getTranslation('activity_categories_other', 'en'));
        $this->assertSame([], $draft->getTranslations('event_categories_other'));
    }

    /**
     * La whitelist sta su `categories.*`, non su `categories`: messa sul campo
     * confronterebbe un array con delle stringhe e rifiuterebbe qualunque
     * selezione. La property è client-settable, quindi lo slug forgiato arriva.
     */
    public function test_next_rejects_an_invented_category(): void
    {
        $this->draftInSession('attivita');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Passeggiata coi cani')
            ->set('categories', ['ippopotamo_sitter'])
            ->call('next')
            ->assertHasErrors(['categories.0' => 'in']);

        $this->assertNull(StructureDraft::first()->activity_categories);
    }

    /**
     * Le due liste non hanno uno slug in comune: uno slug del ramo professionale
     * su una bozza di evento è rifiutato, non salvato nella colonna sbagliata.
     */
    public function test_next_rejects_a_category_from_the_other_branch(): void
    {
        $this->draftInSession('eventi');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Sagra a sei zampe')
            ->set('categories', ['toelettatore'])
            ->call('next')
            ->assertHasErrors(['categories.0' => 'in']);

        $this->assertNull(StructureDraft::first()->event_categories);
    }

    /**
     * Le tipologie sono FACOLTATIVE, e non è un dettaglio: obbligatorie
     * renderebbero non risalvabile ogni bozza già aperta, che ha le colonne NULL
     * e nessun modo di saperlo. Questa è quella bozza.
     */
    public function test_an_existing_draft_with_null_categories_stays_savable(): void
    {
        $draft = $this->draftInSession('attivita', ['name' => ['it' => 'Gita al lago']]);

        $this->assertNull($draft->activity_categories);

        Livewire::test(ActivityName::class)
            ->assertSet('categories', [])
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.location'));

        $this->assertSame([], $draft->refresh()->activity_categories);
    }

    // ── Difetto F6: il testo di «Altro» sopravvive alla deselezione ───────────
    //
    // Il campo è disegnato SOLO dentro `@if (in_array('altro', $categories))`,
    // ma `next()` scrive la colonna senza guardare se lo slug è ancora
    // selezionato: il testo resta a bozza, il publisher lo copia e i due blade
    // lo stampano gated solo su `filled(...)`. Dal wizard si può svuotare solo
    // rispuntando «Altro», cancellandolo e ritogliendo la spunta.

    public function test_togliere_altro_svuota_anche_il_suo_testo_libero(): void
    {
        $draft = $this->draftInSession('attivita');

        // Primo salvataggio: «Altro» spuntato, col suo testo.
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Pensione per gatti')
            ->set('categories', ['altro'])
            ->set('categoriesOther.it', 'Pensione per gatti a domicilio')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame('Pensione per gatti a domicilio', $draft->fresh()->getTranslation('activity_categories_other', 'it'));

        // Si riapre lo step, si toglie «Altro» e si spunta un'altra casella. Il
        // campo di testo non è più disegnato, quindi il partner non lo vede più.
        Livewire::test(ActivityName::class)
            ->set('categories', ['toelettatore'])
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame(['toelettatore'], $draft->activity_categories);
        $this->assertTrue(
            blank($draft->getTranslation('activity_categories_other', 'it')),
            'Senza «Altro» selezionato il suo testo libero non ha più un posto: la scheda stamperebbe '
            .'«Tipologia: Toelettatore» con la sotto-riga grigia di un testo che il partner ha rinunciato a dare.',
        );
    }

    /** Lo stesso sul ramo evento, dove la colonna gemella è `event_categories_other`. */
    public function test_togliere_altro_svuota_il_testo_libero_anche_sugli_eventi(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Sagra del cane')
            ->set('categories', ['altro'])
            ->set('categoriesOther.it', 'Sagra paesana')
            ->call('next')
            ->assertHasNoErrors();

        Livewire::test(ActivityName::class)
            ->set('categories', ['fiere_mercatini'])
            ->call('next')
            ->assertHasNoErrors();

        $this->assertTrue(blank($draft->fresh()->getTranslation('event_categories_other', 'it')));
    }

    // ── Difetto W5: una traduzione inglese salvata non si può più togliere ─────
    //
    // `array_filter(..., filled)` fa CADERE la chiave del locale svuotato, e
    // `setTranslations()` itera solo le chiavi che riceve: il locale assente non
    // viene rimosso. Nel wizard non esiste una sola chiamata a
    // `forgetTranslation()`/`replaceTranslations()`, che il lato contenuti usa
    // invece regolarmente. L'unica via d'uscita — svuotare TUTTE le lingue — è
    // chiusa dal `required` sull'italiano.

    public function test_svuotare_la_traduzione_inglese_del_nome_la_rimuove(): void
    {
        $draft = $this->draftInSession('eventi');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Sagra del cane')
            ->set('name.en', 'Dog Fair')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame('Dog Fair', $draft->fresh()->getTranslation('name', 'en', false));

        // Il partner riapre lo step, corregge l'italiano e SVUOTA il tab EN.
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Sagra del cane 2026')
            ->set('name.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $draft->refresh();

        $this->assertSame('Sagra del cane 2026', $draft->getTranslation('name', 'it'));
        $this->assertTrue(
            blank($draft->getTranslation('name', 'en', false)),
            'Svuotato il tab EN, il visitatore su /en deve tornare al fallback italiano: '
            .'oggi FamilyPublisher riporta a catalogo il vecchio «Dog Fair» per sempre.',
        );
    }

    /** Lo stesso sul testo libero di «Altro», che passa dallo stesso array_filter. */
    public function test_svuotare_la_traduzione_inglese_del_testo_libero_la_rimuove(): void
    {
        $draft = $this->draftInSession('attivita');

        Livewire::test(ActivityName::class)
            ->set('name.it', 'Pensione per conigli')
            ->set('categories', ['altro'])
            ->set('categoriesOther.it', 'Pensione per conigli')
            ->set('categoriesOther.en', 'Rabbit boarding')
            ->call('next')
            ->assertHasNoErrors();

        Livewire::test(ActivityName::class)
            ->set('categoriesOther.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertTrue(blank($draft->fresh()->getTranslation('activity_categories_other', 'en', false)));
    }

    public function test_it_rehydrates_the_saved_categories_of_the_branch(): void
    {
        $this->draftInSession('eventi', [
            'event_categories' => ['sportivi', 'altro'],
            'event_categories_other' => ['it' => 'Gara di agility'],
            // Residuo dell'altro ramo: non deve comparire nelle caselle.
            'activity_categories' => ['toelettatore'],
        ]);

        Livewire::test(ActivityName::class)
            ->assertSet('isEvent', true)
            ->assertSet('categories', ['sportivi', 'altro'])
            ->assertSet('categoriesOther.it', 'Gara di agility');
    }
}
