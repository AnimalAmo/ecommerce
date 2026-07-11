<?php

namespace App\Livewire\Partner\Bookings;

use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Prenotazioni partner (XD "Prenotazioni strutture/eventi/attività/smartbox"):
 * un'unica pagina con 4 tab per famiglia, colonne dedicate, ricerca live e
 * filtro data. Righe DEMO fedeli al mockup (come le stat della Dashboard):
 * il checkout B2C oggi non genera prenotazioni sui servizi partner — quando
 * accadrà la fonte diventerà OrderItem sui prodotti con user_id del partner
 * (colonne introdotte dalla pipeline di pubblicazione).
 */
class PartnerBookings extends Component
{
    public const TABS = ['strutture', 'eventi', 'attivita', 'smartbox'];

    public string $tab = 'strutture';

    public string $search = '';

    /** Data selezionata dal filtro (Y-m-d dal date-picker), null = nessun filtro. */
    public ?string $date = null;

    /** Guard sul binding di flux:tabs: valori fuori lista tornano alla prima tab. */
    public function updatedTab(string $value): void
    {
        if (! in_array($value, self::TABS, true)) {
            $this->tab = self::TABS[0];
        }
    }

    public function render()
    {
        return view('livewire.partner.bookings.index', [
            'bookings' => $this->filteredRows(),
            'columns' => $this->columns(),
        ])->title(__('partner.bookings.title'));
    }

    /**
     * Colonne della tab corrente: chiave riga => chiave lang. Le famiglie
     * differiscono come nel mockup (eventi: Ora e niente Prezzo; smartbox:
     * Validità e niente Data/N. Persone).
     *
     * @return array<string, string>
     */
    private function columns(): array
    {
        $common = [
            'id' => 'col_id',
            'first_name' => 'col_first_name',
            'last_name' => 'col_last_name',
            'email' => 'col_email',
        ];

        return $common + match ($this->tab) {
            'eventi' => ['title' => 'col_event', 'date' => 'col_date', 'time' => 'col_time', 'people' => 'col_people'],
            'attivita' => ['title' => 'col_activity', 'date' => 'col_date', 'price' => 'col_price', 'people' => 'col_people'],
            'smartbox' => ['title' => 'col_smartbox', 'date' => 'col_validity', 'price' => 'col_price'],
            default => ['title' => 'col_structure', 'date' => 'col_date', 'price' => 'col_price', 'people' => 'col_people'],
        };
    }

    /** @return list<array<string, mixed>> */
    private function filteredRows(): array
    {
        $term = mb_strtolower(trim($this->search));

        $rows = array_filter($this->demoRows(), function (array $row) use ($term): bool {
            if ($term !== '' && ! str_contains(mb_strtolower(implode(' ', [
                $row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['title'],
            ])), $term)) {
                return false;
            }

            if ($this->date !== null && $this->date !== '') {
                $day = Carbon::parse($this->date);

                return $day->betweenIncluded(Carbon::parse($row['date_from']), Carbon::parse($row['date_to']));
            }

            return true;
        });

        return array_values($rows);
    }

    /**
     * Righe demo della tab corrente, ripetute come nella griglia del mockup.
     *
     * @return list<array<string, mixed>>
     */
    private function demoRows(): array
    {
        $base = [
            'id' => 'BD94KEU9E',
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia.rossi@gmail.com',
            'time' => null,
            'people' => 2,
        ];

        [$row, $count] = match ($this->tab) {
            'eventi' => [array_merge($base, [
                'title' => 'Puppy Yoga',
                'date' => '20/02/24',
                'date_from' => '2024-02-20', 'date_to' => '2024-02-20',
                'time' => '13:30',
                'price' => null,
            ]), 3],
            'attivita' => [array_merge($base, [
                'title' => 'Vacanza di relax in montagna',
                'date' => '20/02/24 - 25/02/2024',
                'date_from' => '2024-02-20', 'date_to' => '2024-02-25',
                'price' => '215€',
            ]), 7],
            'smartbox' => [array_merge($base, [
                'title' => 'Weekend di relax in Lombardia',
                'date' => '20/02/24 - 20/02/2025',
                'date_from' => '2024-02-20', 'date_to' => '2025-02-20',
                'price' => '215€',
                'people' => null,
            ]), 4],
            default => [array_merge($base, [
                'title' => 'Hotel Brescia',
                'date' => '20/02/24 - 25/02/2024',
                'date_from' => '2024-02-20', 'date_to' => '2024-02-25',
                'price' => '215€',
            ]), 7],
        };

        return array_fill(0, $count, $row);
    }
}
