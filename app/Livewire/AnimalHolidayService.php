<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Dettaglio servizio')]
class AnimalHolidayService extends Component
{
    /** Slug regione dalla rotta (es. "lombardia"). */
    public string $regionSlug = '';

    /** Nome visualizzato della regione (es. "Lombardia"). */
    public string $regionName = '';

    /** Slug servizio dalla rotta (es. "dog-sitting"). */
    public string $serviceSlug = '';

    /** Nome visualizzato del servizio (es. "Dog sitting"). */
    public string $serviceName = '';

    /** Località del servizio (dal risultato campione della regione). */
    public string $location = '';

    public string $rating = '4,5 stelle';

    /** Pop-up "Aggiunto al carrello" (stesso pattern del dettaglio struttura). */
    public bool $cartPopupOpen = false;

    /** Servizi Animali: incluso (check verde) / escluso (X magenta). */
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

    public function mount(string $region, string $service): void
    {
        abort_unless(isset(AnimalHolidayRegion::REGION_NAMES[$region]), 404);

        $entry = collect(AnimalHolidayRegion::RESULTS)
            ->first(fn (array $result) => $result['slug'] === $service && $result['type'] === 'servizi');

        abort_unless($entry !== null, 404);

        $this->regionSlug = $region;
        $this->regionName = AnimalHolidayRegion::REGION_NAMES[$region];
        $this->serviceSlug = $service;
        $this->serviceName = $entry['name'];
        $this->location = $entry['location'];
        $this->rating = $entry['rating'].' stelle';
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
        return view('livewire.animal-holiday-service')
            ->title('AnimalAmo — '.$this->serviceName);
    }
}
