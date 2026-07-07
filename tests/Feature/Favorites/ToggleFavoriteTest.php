<?php

namespace Tests\Feature\Favorites;

use App\Livewire\Events;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToggleFavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_toggle_opens_the_login_modal_and_creates_no_row(): void
    {
        $event = Event::whereNotNull('position')->orderBy('position')->firstOrFail();

        Livewire::test(Events::class)
            ->call('toggleFavorite', 'event', $event->id)
            ->assertOk()
            ->assertDispatched('modal-show', name: 'login');

        // Restano solo i 6 preferiti seedati di Giulia.
        $this->assertSame(6, Favorite::count());
    }

    public function test_authenticated_toggle_creates_then_deletes_the_row(): void
    {
        $user = User::factory()->create();
        $event = Event::whereNotNull('position')->orderBy('position')->firstOrFail();

        Livewire::actingAs($user)->test(Events::class)
            ->call('toggleFavorite', 'event', $event->id)
            ->assertOk()
            ->assertNotDispatched('modal-show');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => 'event',
            'favoritable_id' => $event->id,
        ]);

        Livewire::actingAs($user)->test(Events::class)
            ->call('toggleFavorite', 'event', $event->id)
            ->assertOk();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_type' => 'event',
            'favoritable_id' => $event->id,
        ]);
    }

    public function test_invalid_morph_alias_is_rejected(): void
    {
        $user = User::factory()->create();

        // Né alias fuori whitelist né class-string: 400 e nessuna riga.
        Livewire::actingAs($user)->test(Events::class)
            ->call('toggleFavorite', 'venue', 1)
            ->assertStatus(400);

        Livewire::actingAs($user)->test(Events::class)
            ->call('toggleFavorite', Event::class, 1)
            ->assertStatus(400);

        $this->assertSame(0, Favorite::where('user_id', $user->id)->count());
    }

    public function test_listing_hearts_hydrate_from_persisted_favorites(): void
    {
        // Ospite: tutti i cuori inattivi.
        $this->get('/eventi')
            ->assertOk()
            ->assertDontSee('{ fav: true }', false);

        // Giulia ha 3 preferiti evento seedati: cuori attivi già al primo render.
        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();

        $this->actingAs($giulia)->get('/eventi')
            ->assertOk()
            ->assertSee('{ fav: true }', false);
    }
}
