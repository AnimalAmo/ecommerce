<?php

namespace App\Services\Partner\Publishing;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Structure\StructureDraft;
use App\Models\Venue\Venue;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Famiglia attività/eventi (service_category 'attivita') → tabella events.
 */
class EventPublisher extends FamilyPublisher
{
    public function publish(StructureDraft $draft): Event
    {
        $current = Event::query()->firstWhere('structure_draft_id', $draft->id);
        $isEvent = $draft->type === 'eventi';
        // Senza tipo prezzo o importo l'evento è gratuito ("Partecipa", hasJoinCta).
        $isFree = $draft->price_type !== 'pagamento' || blank($draft->price_per_person);

        $event = Event::query()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'user_id' => $draft->user_id,
            'venue_id' => $this->venue($draft)?->id,
            'type' => $isEvent ? ProductType::Event : ProductType::Activity,
            'title' => $this->translations($draft, 'name'),
            'slug' => $this->slug($draft, $isEvent ? 'evento' : 'attivita'),
            'location' => $draft->locationLabel(),
            // Il wizard salva data e orario separati (orari solo per gli eventi).
            'starts_at' => $this->composeDateTime($draft->date_start, $draft->time_start, '00:00'),
            'ends_at' => $this->composeDateTime($draft->date_end ?? $draft->date_start, $draft->time_end, '23:59'),
            // Senza durata il detail attività farebbe fallback sul mock '3 giorni'.
            'duration_days' => $isEvent ? null : $this->durationDays($draft),
            // Capienza illimitata: nessun input wizard (audit finding 11, v2).
            'max_participants' => null,
            'price_cents' => $isFree ? null : $this->cents($draft->price_per_person),
            'is_free' => $isFree,
            'img' => $this->coverPhoto($draft),
            'hero_img' => $this->coverPhoto($draft),
            'description' => $this->translations($draft, 'description'),
            'position' => $current->position ?? ((int) Event::query()->max('position') + 1),
            'cancellation_policy_days' => $this->cancellationDays($draft),
        ]);

        $this->syncAmenities($event, [
            ...($draft->services ?? []),
            ...($draft->additional_services ?? []),
            ...($draft->animal_services ?? []),
        ]);

        return $event;
    }

    /**
     * Il punto di ritrovo del wizard (campo operativamente chiave) diventa il
     * Venue dell'evento: il detail lo mostra come nome/indirizzo del luogo.
     * Chiave = draft (non il nome): niente venue condivisi tra partner e le
     * correzioni di nome/indirizzo si propagano alla ri-pubblicazione.
     * Nessun map_img: la card mappa resta nascosta (guard nei blade).
     */
    private function venue(StructureDraft $draft): ?Venue
    {
        $name = $draft->getTranslation('meeting_point', 'it') ?: $draft->getTranslation('name', 'it');

        if (blank($name)) {
            return null;
        }

        $address = trim(implode(' ', array_filter([
            $draft->address,
            $draft->zip,
            $draft->city,
            filled($draft->province) ? '('.$draft->province.')' : null,
        ])));

        return Venue::query()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'name' => $name,
            'address' => $address !== '' ? $address : null,
        ]);
    }

    private function composeDateTime(?CarbonInterface $date, ?string $time, string $fallbackTime): ?Carbon
    {
        if ($date === null) {
            return null;
        }

        // Parse difensivo: il wizard valida H:i, ma righe legacy/importate no.
        $parts = explode(':', filled($time) ? $time : $fallbackTime);

        return Carbon::parse($date)->setTime((int) $parts[0], (int) ($parts[1] ?? 0));
    }

    private function durationDays(StructureDraft $draft): ?int
    {
        if ($draft->date_start === null || $draft->date_end === null) {
            return null;
        }

        return (int) $draft->date_start->diffInDays($draft->date_end) + 1;
    }
}
