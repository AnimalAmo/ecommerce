<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxDescription;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_two_description_fields(): void
    {
        $this->get(route('partner.smartbox.description'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_description.heading'))
            ->assertSee(__('partner.smartbox_description.step'))
            ->assertSee(__('partner.smartbox_description.section'))
            ->assertSee(__('partner.smartbox_description.detailed_label'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'));
    }

    public function test_next_requires_both_italian_descriptions(): void
    {
        Livewire::test(SmartboxDescription::class)
            ->set('description.en', 'Only English')
            ->set('detailedDescription.en', 'Only English detailed')
            ->call('next')
            ->assertHasErrors(['description.it', 'detailedDescription.it']);
    }

    public function test_next_saves_the_translations_and_advances(): void
    {
        Livewire::test(SmartboxDescription::class)
            ->set('description.it', 'Un weekend di coccole per te e il tuo cane.')
            ->set('description.en', 'A pampering weekend for you and your dog.')
            ->set('detailedDescription.it', 'Due notti in una struttura pet-friendly con colazione.')
            ->set('detailedDescription.en', 'Two nights in a pet-friendly property with breakfast.')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.duration'));

        $draft = StructureDraft::first();
        $this->assertSame('Un weekend di coccole per te e il tuo cane.', $draft->getTranslation('description', 'it'));
        $this->assertSame('A pampering weekend for you and your dog.', $draft->getTranslation('description', 'en'));
        $this->assertSame('Due notti in una struttura pet-friendly con colazione.', $draft->getTranslation('detailed_description', 'it'));
        $this->assertSame('Two nights in a pet-friendly property with breakfast.', $draft->getTranslation('detailed_description', 'en'));
        $this->assertSame(3, $draft->current_step);
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        Livewire::test(SmartboxDescription::class)
            ->set('description.it', 'Un weekend di coccole per te e il tuo cane.')
            ->set('detailedDescription.it', 'Due notti in una struttura pet-friendly con colazione.')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Un weekend di coccole per te e il tuo cane.', $draft->getTranslation('description', 'en'));
        $this->assertSame(['it' => 'Un weekend di coccole per te e il tuo cane.'], $draft->getTranslations('description'));
        $this->assertSame(['it' => 'Due notti in una struttura pet-friendly con colazione.'], $draft->getTranslations('detailed_description'));
    }

    public function test_it_rehydrates_the_saved_translations(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 3,
            'description' => ['it' => 'Breve', 'en' => 'Short'],
            'detailed_description' => ['it' => 'Dettagliata', 'en' => 'Detailed'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxDescription::class)
            ->assertSet('description.it', 'Breve')
            ->assertSet('description.en', 'Short')
            ->assertSet('detailedDescription.it', 'Dettagliata')
            ->assertSet('detailedDescription.en', 'Detailed');
    }
}
