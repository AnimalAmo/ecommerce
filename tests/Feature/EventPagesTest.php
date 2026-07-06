<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_events_grid_shows_the_twelve_cards_with_derived_labels(): void
    {
        $this->get('/eventi')
            ->assertOk()
            ->assertSee('Brunch Pet Friendly')
            ->assertSee('OGGI ALLE 13:30')
            ->assertSee('LUN, 8 GEN ALLE 19:30')
            ->assertSee("25\u{A0}€ a persona")
            ->assertSee('Gratis')
            ->assertSee('Durata di 5 giorni')
            ->assertSee('Partecipa')
            ->assertSee('Aggiungi al carrello')
            ->assertSee("A partire da 0,00\u{A0}€");
    }

    public function test_paid_event_detail_derives_dates_and_price(): void
    {
        $this->get('/eventi/brunch-pet-friendly')
            ->assertOk()
            ->assertSee('Oggi alle ore 13:30')
            ->assertSee('Oggi dalle 13:30 alle 16:30')
            ->assertSee("25\u{A0}€ a persona")
            ->assertSee('Dario Boario Terme (BS), Italia')
            ->assertSee('Cascina Brescia')
            ->assertSee('Aggiungi al carrello');
    }

    public function test_free_event_detail_shows_gratis_and_partecipa(): void
    {
        $this->get('/eventi/festa-pet-friendly')
            ->assertOk()
            ->assertSee('Gratis')
            ->assertSee('Partecipa')
            ->assertSee('8 Gen')
            ->assertSee('Lunedì 8 Gennaio alle ore 19:30')
            ->assertSee('Lunedì 8 Gennaio dalle ore 19:30 alle 21:30')
            ->assertDontSee('Aggiungi al carrello');
    }

    public function test_activity_slug_redirects_to_the_activity_page(): void
    {
        $this->get('/eventi/weekend-escursioni')
            ->assertRedirect('/eventi/attivita/weekend-escursioni');
    }

    public function test_activity_detail_shows_duration_meeting_point_and_totals(): void
    {
        $this->get('/eventi/attivita/weekend-escursioni')
            ->assertOk()
            ->assertSee('3 gg')
            ->assertSee('Durata di 3 giorni, due notti')
            ->assertSee('Ritrovo: Hotel Miramare, Viareggio, Italia')
            ->assertSee('Via Roma 63, 30057, Viareggio, Italia')
            ->assertSee("118\u{A0}€ a persona")
            ->assertSee("118\u{A0}€ per 2 persone")
            ->assertSee("236\u{A0}€");
    }

    public function test_five_day_activity_uses_the_numeric_duration_label(): void
    {
        $this->get('/eventi/attivita/vacanza-montagna')
            ->assertOk()
            ->assertSee('5 gg')
            ->assertSee('Durata di 5 giorni, 4 notti');
    }

    public function test_event_detail_404_for_unknown_slug(): void
    {
        $this->get('/eventi/sagra-inesistente')->assertNotFound();
    }
}
