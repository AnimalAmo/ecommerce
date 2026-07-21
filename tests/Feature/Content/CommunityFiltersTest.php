<?php

namespace Tests\Feature\Content;

use App\Livewire\Content\Community;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // I post arrivano dal DB: senza seed la lista è vuota e non c'è niente da filtrare.
        $this->seed(CommunitySeeder::class);
    }

    public function test_filter_chips_narrow_the_post_list(): void
    {
        Livewire::test(Community::class)
            ->call('addFilter', 'Avventura')
            ->assertSet('activeFilters', ['Avventura'])
            // Il campione "Domanda" esce di lista, resta solo quello taggato Avventura.
            ->assertSee('Avventura')
            ->assertDontSee('Domanda')
            ->call('removeFilter', 'Avventura')
            ->assertSet('activeFilters', [])
            ->assertSee('Domanda');
    }

    public function test_unknown_filters_are_ignored(): void
    {
        Livewire::test(Community::class)
            ->call('addFilter', 'Inesistente')
            ->assertSet('activeFilters', []);
    }

    public function test_mobile_filter_panel_binding_keeps_only_known_tags(): void
    {
        // Il pannello "Filtri community" scrive l'array via wire:model: va ripulito in ingresso.
        Livewire::test(Community::class)
            ->set('activeFilters', ['Benessere', 'Inesistente', 'Avventura'])
            ->assertSet('activeFilters', ['Avventura', 'Benessere']);
    }

    public function test_mobile_screen_shows_the_post_cards_and_the_see_more_button(): void
    {
        Livewire::test(Community::class)
            ->assertSee(__('community.title_mobile'))
            ->assertSee(__('community.see_more'))
            ->assertSee(__('community.filter_by_type'));
    }
}
