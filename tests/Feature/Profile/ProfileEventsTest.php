<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfileEvents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_upcoming_tab_lists_the_events(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ProfileEvents::class)
            ->assertSet('tab', 'programma')
            ->assertSee('Festa Pet Friendly');
    }

    public function test_past_tab_has_no_events(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ProfileEvents::class)
            ->set('tab', 'passati')
            ->assertDontSee('Festa Pet Friendly')
            ->assertSee(__('profile.no_results'));
    }

    public function test_arbitrary_tab_falls_back_to_programma(): void
    {
        // Le tab sono bindate con wire:model: un valore fuori whitelist non deve
        // arrivare a EVENTS[$tab] (chiave inesistente), ma tornare a "In programma".
        Livewire::actingAs(User::factory()->create())
            ->withQueryParams(['tab' => 'xxx'])
            ->test(ProfileEvents::class)
            ->assertSet('tab', 'programma')
            ->assertSee('Festa Pet Friendly');
    }
}
