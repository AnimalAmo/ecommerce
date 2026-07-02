<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHolidayRegion extends Component
{
    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    /** slug => nome per le 20 regioni italiane. */
    public const REGION_NAMES = [
        'abruzzo' => 'Abruzzo',
        'basilicata' => 'Basilicata',
        'calabria' => 'Calabria',
        'campania' => 'Campania',
        'emilia-romagna' => 'Emilia Romagna',
        'friuli-venezia-giulia' => 'Friuli-Venezia Giulia',
        'lazio' => 'Lazio',
        'liguria' => 'Liguria',
        'lombardia' => 'Lombardia',
        'marche' => 'Marche',
        'molise' => 'Molise',
        'piemonte' => 'Piemonte',
        'puglia' => 'Puglia',
        'sardegna' => 'Sardegna',
        'sicilia' => 'Sicilia',
        'toscana' => 'Toscana',
        'trentino-alto-adige' => 'Trentino-Alto Adige',
        'umbria' => 'Umbria',
        'valle-daosta' => 'Valle d’Aosta',
        'veneto' => 'Veneto',
    ];

    /**
     * Risultati campione in ordine di griglia XD (riga per riga).
     * Rating con virgola decimale italiana; prezzo placeholder "0,00 €" ovunque.
     */
    public array $results = [
        ['type' => 'hotel', 'name' => 'Hotel Brescia', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => '4,5', 'img' => 'regione-hotel-brescia'],
        ['type' => 'hotel', 'name' => 'Villaggio Turistico Tre Capitelli', 'location' => 'Tre Capitelli (BS), Italia', 'rating' => '3', 'img' => 'regione-villaggio-tre-capitelli'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'location' => 'Mantova, Italia', 'rating' => '3,5', 'img' => 'regione-hotel-mantova'],
        ['type' => 'servizi', 'name' => 'Dog sitting', 'location' => 'Mantova, Italia', 'rating' => '4,5', 'img' => 'regione-dog-sitting'],
        ['type' => 'servizi', 'name' => 'Centro di addestramento', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => '4,5', 'img' => 'regione-centro-addestramento'],
        ['type' => 'servizi', 'name' => 'Pet sitting', 'location' => 'Viareggio, Italia', 'rating' => '4,5', 'img' => 'regione-pet-sitting'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'location' => 'Mantova, Italia', 'rating' => '3,5', 'img' => 'regione-hotel-mantova-2'],
        ['type' => 'hotel', 'name' => 'Lamasu W&R', 'location' => 'San Felice del Benaco (BS) - Italia', 'rating' => '5', 'img' => 'regione-lamasu'],
        ['type' => 'hotel', 'name' => 'Hotel Brescia', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => '4,5', 'img' => 'regione-hotel-brescia-2'],
        ['type' => 'hotel', 'name' => 'Villaggio Turistico Tre Capitelli', 'location' => 'Tre Capitelli (BS), Italia', 'rating' => '3', 'img' => 'regione-villaggio-tre-capitelli-2'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'location' => 'Mantova, Italia', 'rating' => '3,5', 'img' => 'regione-hotel-mantova-3'],
        ['type' => 'hotel', 'name' => 'Lamasu W&R', 'location' => 'San Felice del Benaco (BS) - Italia', 'rating' => '5', 'img' => 'regione-lamasu-2'],
    ];

    public function mount(string $region): void
    {
        abort_unless(isset(self::REGION_NAMES[$region]), 404);

        $this->regionSlug = $region;
        $this->regionName = self::REGION_NAMES[$region];
        $this->where = $this->regionName;
    }

    public function search(): void
    {
        // TODO: filter structures once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.animal-holiday-region')
            ->title('AnimalAmo — Hotel e servizi in '.$this->regionName);
    }
}
