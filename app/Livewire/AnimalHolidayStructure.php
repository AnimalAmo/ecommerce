<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Dettaglio struttura')]
class AnimalHolidayStructure extends Component
{
    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug struttura dalla rotta (es. "hotel-brescia"). */
    public string $structureSlug = '';

    /** Nome visualizzato della struttura (es. "Hotel Brescia"). */
    public string $structureName = '';

    /** Località campione — verbatim dall'XD (probabile refuso per "Darfo Boario Terme"). */
    public string $location = 'Dario Boario Terme (BS), Italia';

    public string $rating = '4,5 stelle';

    /** Pop-up "Aggiunto al carrello" (XD: "Pop-up aggiunta al carrello"). */
    public bool $cartPopupOpen = false;

    /** Servizi Hotel: incluso (check verde) / escluso (X magenta). */
    public array $hotelServices = [
        ['label' => 'Aria condizionata negli spazi comuni', 'included' => true],
        ['label' => 'Lavanderia', 'included' => true],
        ['label' => 'Ascensore', 'included' => true],
        ['label' => 'Wifi', 'included' => true],
        ['label' => 'Noleggio bici', 'included' => false],
        ['label' => 'Spa', 'included' => false],
    ];

    /** Servizi Animali: incluso / escluso. */
    public array $animalServices = [
        ['label' => 'Pet sitting', 'included' => true],
        ['label' => 'Servizio veterinario', 'included' => true],
        ['label' => 'Omaggio di benvenuto', 'included' => true],
        ['label' => 'Dog Beach nelle vicinanze', 'included' => true],
        ['label' => 'Supplemento animali', 'included' => false],
        ['label' => 'Piscina per cani', 'included' => false],
    ];

    /** Domande frequenti (contenuto campione identico per le 5 righe, come da XD). */
    public array $faqs = [
        ['question' => 'Lorem ipsum dolor sit amet, consetetur sadipscing', 'answer' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'],
        ['question' => 'Lorem ipsum dolor sit amet, consetetur sadipscing', 'answer' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'],
        ['question' => 'Lorem ipsum dolor sit amet, consetetur sadipscing', 'answer' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'],
        ['question' => 'Lorem ipsum dolor sit amet, consetetur sadipscing', 'answer' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'],
        ['question' => 'Lorem ipsum dolor sit amet, consetetur sadipscing', 'answer' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'],
    ];

    /** Recensioni campione. stars: numero di stelle piene + eventuale mezza (.5). */
    public array $reviews = [
        ['date' => '23 febbraio 2023', 'stars' => 5.0, 'title' => 'Incredibile!', 'body' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.', 'initials' => 'GR', 'name' => 'Giulia Rossi', 'avatar' => '#FF9F3E'],
        ['date' => '23 febbraio 2023', 'stars' => 4.5, 'title' => 'Molto bello', 'body' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.', 'initials' => 'AB', 'name' => 'Andrea Bianchi', 'avatar' => '#FF9F3E'],
        ['date' => '23 febbraio 2023', 'stars' => 4.5, 'title' => 'Incredibile!', 'body' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.', 'initials' => 'FS', 'name' => 'Francesca Sogni', 'avatar' => '#3E72FF'],
    ];

    public function mount(string $region, string $structure): void
    {
        abort_unless(isset(AnimalHolidayRegion::REGION_NAMES[$region]), 404);

        $entry = collect(AnimalHolidayRegion::RESULTS)->firstWhere('slug', $structure);

        abort_unless($entry !== null, 404);

        $this->regionSlug = $region;
        $this->regionName = AnimalHolidayRegion::REGION_NAMES[$region];
        $this->structureSlug = $structure;
        $this->structureName = $entry['name'];
    }

    public function addToCart(): void
    {
        // TODO: carrello reale — per ora mostra solo il pop-up di conferma.
        $this->cartPopupOpen = true;
    }

    public function closeCartPopup(): void
    {
        $this->cartPopupOpen = false;
    }

    public function loadMoreReviews(): void
    {
        // TODO: paginare le recensioni quando esisterà il backend.
    }

    public function render()
    {
        return view('livewire.animal-holiday-structure')
            ->title('AnimalAmo — '.$this->structureName);
    }
}
