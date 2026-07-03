<?php

namespace App\Livewire;

use Livewire\Component;

class SmartboxDetail extends Component
{
    /** Slug del cofanetto dalla rotta (es. "relax-lombardia"). */
    public string $boxSlug = '';

    /** Titolo del cofanetto (es. "Weekend di relax in Lombardia"). */
    public string $title = '';

    /** Destinatari (es. "Coppia"). */
    public string $audience = '';

    /** Nome immagine card (senza estensione) in public/img/xd. */
    public string $img = '';

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
        ['label' => 'Dog sitter', 'included' => true],
        ['label' => 'Servizio veterinario', 'included' => true],
        ['label' => 'Omaggio di benvenuto', 'included' => true],
        ['label' => 'Dog Beach nelle vicinanze', 'included' => true],
        ['label' => 'Supplemento animali', 'included' => false],
        ['label' => 'Piscina per cani', 'included' => false],
    ];

    public function mount(string $box): void
    {
        $entry = collect(Smartbox::BOXES)->firstWhere('slug', $box);

        abort_unless($entry !== null, 404);

        $this->boxSlug = $box;
        $this->title = $entry['title'];
        $this->audience = $entry['audience'];
        $this->img = $entry['img'];
    }

    public function render()
    {
        return view('livewire.smartbox-detail')
            ->title('AnimalAmo — '.$this->title);
    }
}
