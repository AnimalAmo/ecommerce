<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ProfileEvents;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Eventi a cui partecipo" di un utente senza prenotazioni: è la pagina del
 * primo cliente reale di animalamo.it. Mostrava a chiunque si registrasse un
 * evento inventato ("Festa Pet Friendly", Milano, "LUN, 30 MAG ALLE 15:30",
 * "Gratis") che nessuno aveva mai prenotato — hardcoded in una const PHP, quindi
 * fuori dalla portata di qualunque pulizia del database.
 */
class ProfileEventsEmptyTest extends TestCase
{
    use RefreshDatabase;

    /** Stringhe dell'evento mock: nessuna deve sopravvivere in pagina. */
    private const MOCK_STRINGS = [
        'Festa Pet Friendly',
        'LUN, 30 MAG ALLE 15:30',
        'Milano, Italia',
        'event-festa-pet-friendly.jpg',
    ];

    public function test_upcoming_tab_is_empty_and_says_so(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(ProfileEvents::class)
            ->assertSet('tab', 'programma');

        foreach (self::MOCK_STRINGS as $mock) {
            $component->assertDontSee($mock);
        }

        // Senza copy lo stato vuoto sarebbe un titolo, due tab e poi il nulla.
        $component->assertSee(__('profile.events_empty'));
    }

    public function test_past_tab_is_empty_and_says_so(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(ProfileEvents::class)
            ->set('tab', 'passati')
            // Sul passato non si promette nulla: non c'è storico da mostrare.
            ->assertSee(__('profile.no_results'));

        foreach (self::MOCK_STRINGS as $mock) {
            $component->assertDontSee($mock);
        }
    }

    /**
     * La pagina intera (non il solo componente): la sezione deve reggersi in
     * piedi anche a lista vuota, con la CTA su Animal Times — contenuto che
     * esiste davvero — invece del catalogo eventi, vuoto finché i partner non
     * pubblicano. route(), non url(): la locale la mette mcamara.
     */
    public function test_events_page_renders_with_the_empty_state_and_a_working_cta(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('profilo.eventi'))
            ->assertOk()
            ->assertSee(__('profile.interests_title'));

        foreach (self::MOCK_STRINGS as $mock) {
            $response->assertDontSee($mock);
        }

        $response->assertSee(__('profile.events_empty'))
            ->assertSee(__('profile.events_empty_cta'))
            ->assertSee('href="'.route('news').'"', false);
    }
}
