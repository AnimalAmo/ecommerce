<?php

namespace Tests\Feature;

use App\Models\Event\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Le schede evento e attività non devono mostrare discussioni: i due thread
 * firmati Andrea, Giulia e Sofia erano una costante PHP (mock XD), non dati.
 * Su animalamo.it sarebbero comparsi sopra il contenuto reale del partner,
 * con risposte attribuite a lui, e nessuna pulizia del database li toccava.
 * La funzione discussioni arriverà con lo step contenuti/social.
 */
class EventDiscussionTest extends TestCase
{
    use RefreshDatabase;

    /** Firme del mock rimosso: autori e frasi dei due thread inventati. */
    private const INVENTED = [
        'Andrea',
        'Giulia',
        'Sofia',
        'Ciao, è possibile avere del cibo vegetariano?',
        'sono l’organizzatrice',
        'il Brunch comprende diversi menu',
    ];

    /**
     * Copy della tab rimossa, in chiaro e non via __(): le chiavi non esistono più,
     * quindi __('events.tab_discussion') tornerebbe la chiave e l'asserzione passerebbe
     * da sola senza dimostrare nulla.
     */
    private const REMOVED_COPY = [
        'Discussione',
        'Fai una domanda',
        'Attività recenti',
        'Rispondi',
        'Scrivi qualcosa',
    ];

    public function test_event_detail_shows_no_invented_discussion(): void
    {
        $event = $this->partnerEvent();

        $response = $this->get(route('eventi.detail', ['event' => $event->slug]))->assertOk();

        $this->assertNoInventedThread($response);

        // La pagina regge senza la tab: intestazione di sezione, contenuti reali
        // e FAQ del partner (che vivevano SOLO nella tab Discussione) raggiungibili.
        $response
            ->assertSee(__('events.tab_info'))
            ->assertSee(__('events.general_info'))
            ->assertSee(__('events.included'))
            ->assertSee(__('events.add_to_cart'))
            ->assertSee(__('events.faq'))
            ->assertSee('Posso portare due cani?')
            ->assertSee('Descrizione scritta dal partner.');
    }

    public function test_activity_detail_shows_no_invented_discussion(): void
    {
        $activity = $this->partnerActivity();

        $response = $this->get(route('eventi.activity', ['activity' => $activity->slug]))->assertOk();

        $this->assertNoInventedThread($response);

        $response
            ->assertSee(__('events.tab_info'))
            ->assertSee(__('events.general_info'))
            ->assertSee(__('events.included'))
            // Box prenotazione a destra: la colonna non si è persa con la tab.
            ->assertSee(__('events.checkin'))
            ->assertSee(__('events.faq'))
            ->assertSee('Quanti animali posso portare?')
            ->assertSee('Attività scritta dal partner.');
    }

    public function test_the_discussion_deep_link_no_longer_opens_a_thread_page(): void
    {
        // ?tab=discussione era deep-linkabile (#[Url] su $tab): un vecchio link
        // condiviso non deve far riapparire i thread inventati.
        $event = $this->partnerEvent();
        $activity = $this->partnerActivity();

        $this->assertNoInventedThread(
            $this->get(route('eventi.detail', ['event' => $event->slug]).'?tab=discussione')->assertOk()
        );

        $this->assertNoInventedThread(
            $this->get(route('eventi.activity', ['activity' => $activity->slug]).'?tab=discussione')->assertOk()
        );
    }

    private function assertNoInventedThread(TestResponse $response): void
    {
        foreach ([...self::INVENTED, ...self::REMOVED_COPY] as $text) {
            $response->assertDontSee($text);
        }
    }

    /** Evento a pagamento con contenuti espliciti: niente testo faker che possa contenere per caso un nome del mock. */
    private function partnerEvent(): Event
    {
        $event = Event::factory()->create([
            'title' => 'Evento del partner',
            'slug' => 'evento-del-partner',
            'location' => 'Brescia, Italia',
            'description' => 'Descrizione scritta dal partner.',
            'time_note' => 'Nota orario del partner.',
            'venue_note' => 'Nota luogo del partner.',
        ]);

        // FAQ reale del partner: prima di questa modifica era visibile solo nella tab Discussione.
        $event->faqs()->create([
            'question' => 'Posso portare due cani?',
            'answer' => 'Sì, entrambi sono benvenuti.',
            'position' => 1,
        ]);

        return $event;
    }

    private function partnerActivity(): Event
    {
        $activity = Event::factory()->activity(3)->create([
            'title' => 'Attività del partner',
            'slug' => 'attivita-del-partner',
            'location' => 'Viareggio, Italia',
            'description' => 'Attività scritta dal partner.',
            'time_note' => 'Nota orario del partner.',
            'venue_note' => 'Nota luogo del partner.',
        ]);

        $activity->faqs()->create([
            'question' => 'Quanti animali posso portare?',
            'answer' => 'Fino a due per prenotazione.',
            'position' => 1,
        ]);

        return $activity;
    }
}
