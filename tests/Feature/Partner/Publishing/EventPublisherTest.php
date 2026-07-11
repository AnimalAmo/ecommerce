<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\Event\Event;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Models\Venue\Venue;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AmenitySeeder::class);
    }

    private function activityDraft(array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'eventi',
            'name' => ['it' => 'Aperitivo a 6 zampe', 'en' => 'Six-legged happy hour'],
            'description' => ['it' => 'Un aperitivo con i vostri amici pelosi.'],
            'meeting_point' => ['it' => 'Piazza Duomo'],
            'address' => 'Piazza Duomo 1',
            'city' => 'Milano',
            'province' => 'MI',
            'zip' => '20121',
            'date_start' => '2026-08-01',
            'date_end' => '2026-08-01',
            'time_start' => '10:00',
            'time_end' => '18:00',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'cancellation_when' => '1',
            'services' => ['wifi'],
            'additional_services' => ['colazione'],
            'animal_services' => ['veterinario'],
            'photos' => ['structure-photos/evento.jpg'],
        ], $attributes));
    }

    public function test_publish_creates_a_paid_event_with_composed_datetimes(): void
    {
        $draft = $this->activityDraft();

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('event', $event->type->value);
        $this->assertSame('aperitivo-a-6-zampe-'.$draft->id, $event->slug);
        $this->assertSame('Milano (MI), Italia', $event->location);
        // Data + orario del wizard ricomposti in datetime.
        $this->assertSame('2026-08-01 10:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-08-01 18:00', $event->ends_at->format('Y-m-d H:i'));
        $this->assertSame(2500, $event->price_cents);
        $this->assertFalse($event->is_free);
        $this->assertFalse($event->hasJoinCta());
        // Capienza illimitata: nessun input wizard (v2).
        $this->assertNull($event->max_participants);
        $this->assertSame(1, $event->cancellation_policy_days);
        $this->assertSame('Aperitivo a 6 zampe', $event->getTranslation('title', 'it'));
        $this->assertSame('Six-legged happy hour', $event->getTranslation('title', 'en'));
    }

    public function test_publish_creates_the_venue_from_the_meeting_point(): void
    {
        $draft = $this->activityDraft();

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertNotNull($event->venue);
        $this->assertSame('Piazza Duomo', $event->venue->name);
        $this->assertSame('Piazza Duomo 1 20121 Milano (MI)', $event->venue->address);
        $this->assertNull($event->venue->map_img);

        // Ri-pubblicare riusa lo stesso venue e propaga le correzioni di indirizzo.
        $draft->address = 'Via Nuova 9';
        $draft->save();
        app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame(1, Venue::count());
        $this->assertSame(1, Event::count());
        $this->assertSame('Via Nuova 9 20121 Milano (MI)', $event->venue->fresh()->address);
    }

    public function test_partners_with_the_same_meeting_point_get_separate_venues(): void
    {
        // Venue scopato sul draft: niente indirizzi altrui in pagina.
        app(DraftPublisher::class)->publish($this->activityDraft());
        app(DraftPublisher::class)->publish($this->activityDraft(['address' => 'Corso Italia 5', 'city' => 'Firenze', 'province' => 'FI', 'zip' => '50121']));

        $this->assertSame(2, Venue::count());
    }

    public function test_publish_skips_activity_drafts_without_dates(): void
    {
        // Un evento senza starts_at renderebbe una card cliccabile con detail rotto.
        $this->assertNull(app(DraftPublisher::class)->publish($this->activityDraft(['date_start' => null, 'date_end' => null])));
        $this->assertSame(0, Event::count());
    }

    public function test_publish_creates_a_free_activity_with_duration(): void
    {
        $draft = $this->activityDraft([
            'type' => 'attivita',
            'date_start' => '2026-09-04',
            'date_end' => '2026-09-06',
            'time_start' => null,
            'time_end' => null,
            'price_type' => 'gratuito',
            'price_per_person' => null,
        ]);

        $event = app(DraftPublisher::class)->publish($draft);

        $this->assertSame('activity', $event->type->value);
        // Senza durata il detail attività farebbe fallback sul mock '3 giorni'.
        $this->assertSame(3, $event->duration_days);
        $this->assertSame('2026-09-04 00:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-06 23:59', $event->ends_at->format('Y-m-d H:i'));
        $this->assertNull($event->price_cents);
        $this->assertTrue($event->is_free);
        $this->assertTrue($event->hasJoinCta());
    }
}
