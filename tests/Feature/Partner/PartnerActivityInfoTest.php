<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityInfo;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerActivityInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_date_fields(): void
    {
        $this->get(route('partner.activity.info'))
            ->assertOk()
            ->assertSee(__('partner.activity_info.heading'))
            ->assertSee(__('partner.activity_info.step'))
            ->assertSee(__('partner.activity_info.date_start'))
            ->assertSee(__('partner.activity_info.date_end'))
            ->assertSee(__('partner.activity_info.next'));
    }

    public function test_activity_needs_only_the_dates(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'type' => 'attivita']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityInfo::class)
            ->assertSet('form.isEvent', false)
            ->assertDontSee(__('partner.activity_info.time_start'))
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-03')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.included'));

        $this->assertDatabaseHas('structure_drafts', ['current_step' => 5]);
    }

    public function test_event_also_requires_the_times(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 5, 'type' => 'eventi']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityInfo::class)
            ->assertSet('form.isEvent', true)
            ->assertSee(__('partner.activity_info.time_start'))
            ->set('form.dateStart', '2026-08-01')
            ->set('form.dateEnd', '2026-08-01')
            ->call('next')
            ->assertHasErrors('form.timeStart')
            ->set('form.timeStart', '10:00')
            ->set('form.timeEnd', '18:00')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('structure_drafts', ['time_start' => '10:00', 'time_end' => '18:00']);
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        Livewire::test(ActivityInfo::class)
            ->set('form.dateStart', '2026-08-05')
            ->set('form.dateEnd', '2026-08-01')
            ->call('next')
            ->assertHasErrors('form.dateEnd');
    }
}
