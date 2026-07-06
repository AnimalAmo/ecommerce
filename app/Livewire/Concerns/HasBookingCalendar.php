<?php

namespace App\Livewire\Concerns;

use App\Models\Structure\Structure;
use App\Services\Availability\AvailabilityService;
use DateTimeImmutable;

/**
 * Macchina condivisa del picker prenotazione (estratta dal pop-up "Modifica
 * prenotazione" del carrello, riusata dai widget delle pagine detail):
 * calendario mensile con selezione range o giorno singolo e giorni
 * passati/chiusi disabilitati (AvailabilityService::closedDates), più gli
 * stepper ospiti e animali con i clamp ratificati (10 ospiti / 5 animali).
 * Le viste stanno in resources/views/partials/booking/.
 */
trait HasBookingCalendar
{
    /** Copie di lavoro del picker: date dd/mm/yyyy (check-in = giorno singolo nei picker a un giorno). */
    public ?string $editCheckIn = null;

    public ?string $editCheckOut = null;

    /** @var array{adulti: int, ragazzi: int, bambini: int} */
    public array $editGuests = ['adulti' => 1, 'ragazzi' => 0, 'bambini' => 0];

    /** Animali per specie ({specie: count}, decisione ratificata #4). */
    public array $editAnimals = ['cane' => 0];

    /** true = il calendario seleziona un giorno singolo (service), false = range check-in/check-out. */
    public bool $calendarSingleDay = false;

    /** Mese (1-12) e anno mostrati dal calendario. */
    public int $calendarMonth = 1;

    public int $calendarYear = 2024;

    /** Clamp degli stepper (estrapolazione minima ratificata, DA SEGNALARE). */
    public const MAX_GUESTS = 10;

    public const MAX_ANIMALS = 5;

    /** Nomi dei mesi per il titolo del calendario ("Marzo 2024"). */
    public const MONTHS = [
        1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
    ];

    /** Il calendario parte dal mese corrente (le azioni possono ripuntarlo). */
    public function mountHasBookingCalendar(): void
    {
        $this->pointCalendarAt(new DateTimeImmutable('today'));
    }

    /** Freccia sinistra del calendario. */
    public function previousMonth(): void
    {
        $this->calendarMonth--;

        if ($this->calendarMonth < 1) {
            $this->calendarMonth = 12;
            $this->calendarYear--;
        }
    }

    /** Freccia destra del calendario. */
    public function nextMonth(): void
    {
        $this->calendarMonth++;

        if ($this->calendarMonth > 12) {
            $this->calendarMonth = 1;
            $this->calendarYear++;
        }
    }

    /**
     * Click su un giorno del calendario (data Y-m-d). Giorni passati o chiusi
     * sono disabilitati (il click non fa nulla). A giorno singolo imposta solo
     * il check-in; a range: imposta il check-in, un click su un giorno
     * successivo imposta il check-out, un click prima del check-in (o a
     * intervallo già completo) fa ripartire la selezione.
     */
    public function selectDay(string $date): void
    {
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($day === false || $day < new DateTimeImmutable('today') || $this->isClosedDay($day)) {
            return;
        }

        if ($this->calendarSingleDay) {
            $this->editCheckIn = $day->format('d/m/Y');
            $this->editCheckOut = null;

            return;
        }

        $checkIn = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : null;

        if ($checkIn === null || $day < $checkIn || $this->editCheckOut !== null) {
            $this->editCheckIn = $day->format('d/m/Y');
            $this->editCheckOut = null;
        } else {
            $this->editCheckOut = $day->format('d/m/Y');
        }
    }

    /** Stepper "+" Ospiti (clamp sul totale: il bottone in vista è disabled a MAX_GUESTS). */
    public function incrementGuest(string $key): void
    {
        if (array_key_exists($key, $this->editGuests) && ! $this->guestsAtMax()) {
            $this->editGuests[$key]++;
        }
    }

    /** Stepper "−" Ospiti (adulti minimo 1, ragazzi/bambini minimo 0). */
    public function decrementGuest(string $key): void
    {
        if (array_key_exists($key, $this->editGuests)) {
            $min = $key === 'adulti' ? 1 : 0;
            $this->editGuests[$key] = max($min, $this->editGuests[$key] - 1);
        }
    }

    /** Stepper "+" Animali per specie (clamp sul totale, come gli ospiti). */
    public function incrementAnimal(string $species): void
    {
        if (array_key_exists($species, $this->editAnimals) && ! $this->animalsAtMax()) {
            $this->editAnimals[$species]++;
        }
    }

    /** Stepper "−" Animali per specie (minimo 0). */
    public function decrementAnimal(string $species): void
    {
        if (array_key_exists($species, $this->editAnimals)) {
            $this->editAnimals[$species] = max(0, $this->editAnimals[$species] - 1);
        }
    }

    /** true se il totale ospiti ha raggiunto il clamp (bottoni "+" disabilitati in vista). */
    public function guestsAtMax(): bool
    {
        return array_sum($this->editGuests) >= self::MAX_GUESTS;
    }

    /** true se il totale animali ha raggiunto il clamp. */
    public function animalsAtMax(): bool
    {
        return array_sum($this->editAnimals) >= self::MAX_ANIMALS;
    }

    /** Ore selezionabili nei picker orario dei servizi (08:00–20:00, passo 1h). */
    public static function bookingHours(): array
    {
        return array_map(fn (int $hour): string => sprintf('%02d:00', $hour), range(8, 20));
    }

    /** Titolo del calendario ("Marzo 2024"). */
    protected function calendarLabel(): string
    {
        return self::MONTHS[$this->calendarMonth].' '.$this->calendarYear;
    }

    /** Punta il calendario al mese della data indicata. */
    protected function pointCalendarAt(DateTimeImmutable $date): void
    {
        $this->calendarMonth = (int) $date->format('n');
        $this->calendarYear = (int) $date->format('Y');
    }

    /**
     * Struttura di cui disabilitare i giorni chiusi nel calendario (null =
     * nessuna chiusura, restano disabilitati solo i giorni passati). Le classi
     * che usano il trait la sovrascrivono quando prenotano una structure/service.
     */
    protected function calendarStructure(): ?Structure
    {
        return null;
    }

    /**
     * Griglia reale del mese mostrato: settimane da domenica (Dom … Sab come in
     * XD), con i giorni dei mesi adiacenti a completare le righe; 'inRange'
     * marca i giorni dentro l'intervallo selezionato (cerchio giallo),
     * 'disabled' i giorni passati o chiusi (greyed, non cliccabili).
     */
    protected function buildCalendar(): array
    {
        $first = new DateTimeImmutable(sprintf('%d-%02d-01', $this->calendarYear, $this->calendarMonth));
        $cursor = $first->modify('-'.(int) $first->format('w').' days');
        $lastOfMonth = $first->modify('last day of this month');
        $gridEnd = $lastOfMonth->modify('+'.(6 - (int) $lastOfMonth->format('w')).' days');

        $rangeStart = $this->editCheckIn !== null ? self::parseDate($this->editCheckIn) : null;
        $rangeEnd = $this->editCheckOut !== null ? self::parseDate($this->editCheckOut) : $rangeStart;

        $today = new DateTimeImmutable('today');
        $closed = $this->closedCalendarDays($cursor, $gridEnd);

        $weeks = [];

        do {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->format('Y-m-d');

                $week[] = [
                    'day' => (int) $cursor->format('j'),
                    'date' => $date,
                    'inMonth' => (int) $cursor->format('n') === $this->calendarMonth
                        && (int) $cursor->format('Y') === $this->calendarYear,
                    'inRange' => $rangeStart !== null && $cursor >= $rangeStart && $cursor <= $rangeEnd,
                    'disabled' => $cursor < $today || in_array($date, $closed, true),
                ];

                $cursor = $cursor->modify('+1 day');
            }

            $weeks[] = $week;
        } while ($cursor <= $lastOfMonth);

        return $weeks;
    }

    /** dd/mm/yyyy → DateTimeImmutable a mezzanotte. */
    protected static function parseDate(string $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('!d/m/Y', $date) ?: new DateTimeImmutable('today');
    }

    /** true se il giorno è chiuso per la struttura del calendario. */
    private function isClosedDay(DateTimeImmutable $day): bool
    {
        $structure = $this->calendarStructure();

        if ($structure === null) {
            return false;
        }

        $closed = app(AvailabilityService::class)
            ->closedDates($structure, (int) $day->format('Y'), (int) $day->format('n'));

        return in_array($day->format('Y-m-d'), $closed, true);
    }

    /**
     * Giorni chiusi ('Y-m-d') su tutti i mesi coperti dalla griglia (le code
     * dei mesi adiacenti incluse), per marcare le celle disabilitate.
     *
     * @return list<string>
     */
    private function closedCalendarDays(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $structure = $this->calendarStructure();

        if ($structure === null) {
            return [];
        }

        $availability = app(AvailabilityService::class);
        $closed = [];

        for ($month = $from->modify('first day of this month'); $month <= $to; $month = $month->modify('first day of next month')) {
            $closed = [
                ...$closed,
                ...$availability->closedDates($structure, (int) $month->format('Y'), (int) $month->format('n')),
            ];
        }

        return $closed;
    }
}
