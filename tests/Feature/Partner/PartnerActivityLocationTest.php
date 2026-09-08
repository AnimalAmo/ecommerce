<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityLocation;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_location_fields(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.activity.location'))
            ->assertOk()
            ->assertSee(__('partner.activity_location.heading'))
            ->assertSee(__('partner.activity_location.step'))
            ->assertSee(__('partner.activity_location.address'))
            ->assertSee(__('partner.activity_location.meeting_point'))
            ->assertSee(__('partner.activity_location.next'));
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(ActivityLocation::class)
            ->call('next')
            ->assertHasErrors(['form.address', 'form.city', 'form.province', 'form.zip', 'form.meetingPoint.it']);
    }

    public function test_next_saves_the_location(): void
    {
        Livewire::test(ActivityLocation::class)
            ->set('form.address', 'Via Lago 5')
            ->set('form.city', 'Garda')
            ->set('form.province', 'VR')
            ->set('form.zip', '37016')
            ->set('form.meetingPoint.it', 'Ingresso del parco')
            ->set('form.meetingPoint.en', 'Park entrance')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.description'));

        $this->assertDatabaseHas('structure_drafts', [
            'city' => 'Garda',
            'current_step' => 3,
        ]);

        $draft = StructureDraft::first();
        $this->assertSame('Ingresso del parco', $draft->getTranslation('meeting_point', 'it'));
        $this->assertSame('Park entrance', $draft->getTranslation('meeting_point', 'en'));
    }

    public function test_the_english_meeting_point_is_optional(): void
    {
        Livewire::test(ActivityLocation::class)
            ->set('form.address', 'Via Lago 5')
            ->set('form.city', 'Garda')
            ->set('form.province', 'VR')
            ->set('form.zip', '37016')
            ->set('form.meetingPoint.it', 'Ingresso del parco')
            ->call('next')
            ->assertHasNoErrors();

        $draft = StructureDraft::first();
        // Nessuna traduzione EN salvata: fallback sull'italiano.
        $this->assertSame('Ingresso del parco', $draft->getTranslation('meeting_point', 'en'));
        $this->assertSame(['it' => 'Ingresso del parco'], $draft->getTranslations('meeting_point'));
    }

    public function test_it_rehydrates_the_saved_meeting_point(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 3, 'meeting_point' => ['it' => 'Piazza centrale', 'en' => 'Main square']]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityLocation::class)
            ->assertSet('form.meetingPoint.it', 'Piazza centrale')
            ->assertSet('form.meetingPoint.en', 'Main square');
    }
}
