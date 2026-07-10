<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelTitle;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_name_field(): void
    {
        $this->get(route('partner.structure.hotel.title'))
            ->assertOk()
            ->assertSee(__('partner.hotel_title.heading'))
            ->assertSee(__('partner.hotel_title.step'))
            ->assertSee(__('partner.hotel_title.field_label'))
            ->assertSee(__('partner.hotel_title.next'));
    }

    public function test_next_requires_a_name(): void
    {
        Livewire::test(HotelTitle::class)
            ->call('next')
            ->assertHasErrors('name');
    }

    public function test_next_saves_the_name_and_advances(): void
    {
        Livewire::test(HotelTitle::class)
            ->set('name', 'Hotel Bau Resort')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.hotel.location'));

        $this->assertDatabaseHas('structure_drafts', ['name' => 'Hotel Bau Resort', 'current_step' => 2]);
    }

    public function test_it_rehydrates_the_saved_name(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 2, 'name' => 'Agriturismo Rex']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelTitle::class)->assertSet('name', 'Agriturismo Rex');
    }
}
