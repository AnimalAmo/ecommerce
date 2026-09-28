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
        $current = Event::withHidden()->firstWhere('structure_draft_id', $draft->id);
        $isEvent = $draft->type === 'eventi';
        // Senza tipo prezzo o importo l'evento è gratuito ("Partecipa", hasJoinCta).
        $isFree = $draft->price_type !== 'pagamento' || blank($draft->price_per_person);

        $event = Event::withHidden()->updateOrCreate(['structure_draft_id' => $draft->id], [
            'user_id' => $draft->user_id,
            'venue_id' => $this->venue($draft)?->id,
            'type' => $isEvent ? ProductType::Event : ProductType::Activity,
            // Categorie professionali, scelta multipla (cliente, 26/09/2026).
            // NULL sugli eventi veri anche se la bozza le porta addosso: sono
            // del professionista, e un cambio di ramo può lasciarle lì.
            'activity_categories' => $isEvent ? null : $draft->activity_categories,
            'activity_categories_other' => $isEvent ? null : $this->translations($draft, 'activity_categories_other'),
            // Tipologie di evento (cliente, 27/09/2026): la gemella sull'altro
            // ramo, guardata nello stesso modo e nel verso opposto. Su un
            // servizio professionale «Fiere / Mercatini» non vuol dire niente.
            'event_categories' => $isEvent ? $draft->event_categories : null,
            'event_categories_other' => $isEvent ? $this->translations($draft, 'event_categories_other') : null,
            // Zona operativa: sta AL POSTO del punto d'incontro, quindi vale solo
            // dove un punto d'incontro non c'è.
            'operating_area' => $isEvent ? null : $this->translations($draft, 'operating_area'),
            // Ricorrenza: sola etichetta per la scheda — la cliente ha escluso la
            // generazione automatica delle date ripetute — e la domanda stessa
            // parla di eventi.
            'recurrence' => $isEvent ? $draft->recurrence : null,
            // Prenotazione: nessuna guardia sul tipo. La cliente la chiede ai
            // professionisti come «possibilità di prenotazione» e agli eventi come
            // «obbligatoria o facoltativa»: è la stessa informazione.
            'booking_requirement' => $draft->booking_requirement,
            'title' => $this->translations($draft, 'name'),
            'slug' => $this->slug($draft, $isEvent ? 'evento' : 'attivita'),
            'location' => $draft->locationLabel(),
            // Il wizard salva data e orario separati (orari solo per gli eventi).
            'starts_at' => $this->composeDateTime($draft->date_start, $draft->time_start, '00:00'),
            'ends_at' => $this->composeDateTime($draft->date_end ?? $draft->date_start, $draft->time_end, '23:59'),
            // Senza durata il detail attività farebbe fallback sul mock '3 giorni'.
            'duration_days' => $isEvent ? null : $this->durationDays($draft),
            // Capienza: il wizard ha il campo dallo step 5 (cliente, 27/09/2026:
            // «numero effettivo, con blocco delle iscrizioni al raggiungimento del
            // limite»), quindi un evento può finalmente esaurirsi. NULL resta
            // "illimitata", ed è quello che hanno tutte le schede pubblicate prima
            // di questa modifica. AvailabilityService e ReserveAvailabilityPipe
            // leggono già la colonna: non serve altro a valle.
            // Nessuna guardia sul tipo, a differenza delle tipologie: la capienza
            // ha senso anche per un'attività (un workshop ha dei posti), e un
            // valore scritto dal pannello admin non va buttato.
            'max_participants' => $draft->max_participants,
            'price_cents' => $isFree ? null : $this->cents($draft->price_per_person),
            'is_free' => $isFree,
            'img' => $this->coverPhoto($draft),
            'hero_img' => $this->coverPhoto($draft),
            'description' => $this->translations($draft, 'description'),
            // Descrizione dettagliata (audit 28/09/2026, difetto W1):
            // ActivityDescription la rende obbligatoria per le attività, ma
            // qui non veniva mai nominata e la scheda ristampava la breve al
            // suo posto. Stessa copia di SmartboxPublisher
            // (`detailed_description` → `extended_description`). Vuota sugli
            // eventi veri, come le categorie professionali: il wizard non la
            // chiede per un evento, e una bozza passata da Attività a Evento
            // se la porta addosso. Vuota e non NULL: su un attributo tradotto
            // spatie scrive `{"it":null}`, quindi la scheda la legge con filled().
            'detailed_description' => $isEvent ? null : $this->translations($draft, 'detailed_description'),
            'position' => $current->position ?? ((int) Event::withHidden()->max('position') + 1),
            'cancellation_policy_days' => $this->cancellationDays($draft),
            ...$this->moderationAttributes($current),
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
     *
     * Senza punto d'incontro il nome cadeva sul NOME del servizio, e la scheda
     * di un'attività stampava «Ritrovo: <nome dell'attività>» — un ritrovo che
     * non esiste, perché un professionista lavora su una zona e non ha un luogo
     * d'incontro (cliente, 27/09/2026). Il Venue continua a nascere, perché è
     * lui che regge indirizzo e mappa; quello che non fa più è inventarsi un
     * ritrovo. Nome vuoto = nessun ritrovo, ed è il segnale su cui la scheda
     * pubblica decide se stampare quella riga.
     */
    private function venue(StructureDraft $draft): ?Venue
    {
        $meetingPoint = (string) $draft->getTranslation('meeting_point', 'it');

        $address = trim(implode(' ', array_filter([
            $draft->address,
            $draft->zip,
            $draft->city,
            filled($draft->province) ? '('.$draft->province.')' : null,
        ])));

        // Né ritrovo né indirizzo: un Venue così non direbbe nulla e la card
        // mappa resterebbe nascosta comunque (mapQuery() vuole l'indirizzo).
        if (blank($meetingPoint) && $address === '') {
            return null;
        }

        return Venue::query()->updateOrCreate(['structure_draft_id' => $draft->id], [
            // Stringa vuota e non NULL: `venues.name` non è nullable e la
            // migrazione è fuori perimetro. mapQuery() filtra già i vuoti prima
            // di comporre la query per Google, quindi un nome vuoto non sporca
            // la mappa.
            'name' => $meetingPoint,
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
