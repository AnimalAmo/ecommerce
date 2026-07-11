<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityName;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_name_field(): void
    {
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
}
