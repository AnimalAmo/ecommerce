<?php

namespace Tests\Feature\Favorites;

use App\Livewire\Favorites;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FavoritesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $giulia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();
    }

    public function test_demo_user_sees_the_six_seeded_favorites_with_the_card_fields(): void
    {
        $this->actingAs($this->giulia)->get('/preferiti')
            ->assertOk()
            // Eventi → riga data da Format::eventTime.
            ->assertSee('Puppy Yoga')
            ->assertSee('LUN, 30 MAG ALLE 15:30')
            ->assertSee('Pomeriggio di addestramento')
            ->assertSee('SAB, 25 MAG ALLE 15:00')
            // Attività → riga durata da duration_days.
            ->assertSee('Vacanza di relax in montagna')
            ->assertSee('DURATA DI 5 GIORNI')
            // Struttura e servizio → riga rating (colonna rating, come il listing).
            ->assertSee('Hotel Brescia')
            ->assertSee('4,5')
            ->assertSee('Dog sitting')
            // Smartbox → riga durata = validità del cofanetto, riga pin = audience.
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee('VALIDO PER 1 ANNO')
            ->assertSee('Coppia')
            // Prezzi: eventi a prezzo pieno, strutture/smartbox da price_from (0 nel mock).
            ->assertSee("A partire da 25\u{A0}€")
            ->assertSee("A partire da 0,00\u{A0}€")
            // Il vecchio stato vuoto non compare.
            ->assertDontSee('Aggiungi nuove avventure nei preferiti');
    }

    public function test_type_filter_narrows_the_grid_and_tutte_resets_it(): void
    {
        Livewire::actingAs($this->giulia)->test(Favorites::class)
            ->call('setTypeFilter', 'event')
            ->assertSee('Puppy Yoga')
            ->assertDontSee('Hotel Brescia')
            ->assertDontSee('Vacanza di relax in montagna')
            ->call('setTypeFilter', null)
            ->assertSee('Hotel Brescia')
            // Tipologia valida ma assente tra i preferiti → render decade su "Tutte"
            // (il clamp sta in render(), così copre anche la rimozione dell'ultima card).
            ->call('setTypeFilter', 'stay')
            ->assertSee('Hotel Brescia')
            ->assertSee('Puppy Yoga')
            // Valore non valido → filtro azzerato subito.
            ->call('setTypeFilter', 'not-a-type')
            ->assertSet('typeFilter', null)
            ->assertSee('Hotel Brescia');
    }

    public function test_removing_the_last_card_of_the_filtered_type_falls_back_to_all(): void
    {
        // L'unico preferito smartbox è la card "Benessere" (wellness).
        $component = Livewire::actingAs($this->giulia)->test(Favorites::class)
            ->call('setTypeFilter', 'wellness');

        // Rimosso l'ultimo preferito della tipologia filtrata: la pagina torna a
        // mostrare tutto invece di restare su un "Nessun risultato" con filtro fantasma.
        $this->giulia->favorites()
            ->where('favoritable_type', 'smartbox_package')
            ->get()
            ->each(fn ($favorite) => $component->call('removeFavorite', $favorite->id));

        $component->assertSee('Hotel Brescia')
            ->assertDontSee('Nessun risultato');
    }

    public function test_remove_favorite_deletes_only_the_own_row(): void
    {
        $favorite = $this->giulia->favorites()
            ->where('favoritable_type', 'event')
            ->orderBy('id')
            ->firstOrFail();

        $other = User::factory()->create();
        $foreign = $other->favorites()->create([
            'favoritable_type' => $favorite->favoritable_type,
            'favoritable_id' => $favorite->favoritable_id,
        ]);

        Livewire::actingAs($this->giulia)->test(Favorites::class)
            ->call('removeFavorite', $favorite->id)
            ->assertOk();

        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
        $this->assertSame(5, $this->giulia->favorites()->count());

        // La riga di un altro utente non è cancellabile.
        Livewire::actingAs($this->giulia)->test(Favorites::class)
            ->call('removeFavorite', $foreign->id);

        $this->assertDatabaseHas('favorites', ['id' => $foreign->id]);
    }

    public function test_guest_sees_the_empty_state(): void
    {
        $this->get('/preferiti')
            ->assertOk()
            ->assertSee('Aggiungi nuove avventure nei preferiti')
            ->assertDontSee('Hotel Brescia');
    }

    public function test_user_without_favorites_sees_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get('/preferiti')
            ->assertOk()
            ->assertSee('Aggiungi nuove avventure nei preferiti');
    }
}
