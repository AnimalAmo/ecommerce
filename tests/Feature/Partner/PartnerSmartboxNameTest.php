<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxName;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_title_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.smartbox.name'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_name.heading'))
            ->assertSee(__('partner.smartbox_name.step'))
            ->assertSee(__('partner.smartbox_name.field_label'))
            ->assertSee(__('partner.locale_it'))
            ->assertSee(__('partner.locale_en'))
            ->assertSee(__('partner.smartbox_name.next'));
    }

    public function test_next_requires_the_italian_title(): void
    {
        Livewire::test(SmartboxName::class)
            ->set('name.en', 'Only English')
            ->call('next')
            ->assertHasErrors('name.it');
    }

    public function test_next_saves_the_translations_and_advances(): void
    {
        Livewire::test(SmartboxName::class)
            ->set('name.it', 'Weekend di coccole')
            ->set('name.en', 'Cuddle Weekend')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.description'));

        $draft = StructureDraft::first();
        $this->assertSame('Weekend di coccole', $draft->getTranslation('name', 'it'));
        $this->assertSame('Cuddle Weekend', $draft->getTranslation('name', 'en'));
        $this->assertSame(2, $draft->current_step);
    }

    public function test_english_is_optional_and_falls_back_to_italian(): void
    {
        Livewire::test(SmartboxName::class)
            ->set('name.it', 'Weekend di coccole')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Weekend di coccole', $draft->getTranslation('name', 'en'));
        $this->assertSame(['it' => 'Weekend di coccole'], $draft->getTranslations('name'));
    }

    public function test_it_rehydrates_the_saved_translations(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => ['it' => 'Fuga romantica', 'en' => 'Romantic Getaway']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxName::class)
            ->assertSet('name.it', 'Fuga romantica')
            ->assertSet('name.en', 'Romantic Getaway');
    }
}
