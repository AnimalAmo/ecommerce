<?php

namespace App\Livewire;

use Livewire\Component;

class EventDetail extends Component
{
    /** Slug evento dalla rotta (es. "brunch-pet-friendly"); il nome differisce dal parametro {event} per non collidere col binding Livewire. */
    public string $eventSlug = '';

    /**
     * "Cosa è incluso" — due colonne di voci campione come da XD
     * (statiche come nelle pagine sorelle; included: check verde / X rosa).
     */
    public const INCLUDED = [
        [
            ['label' => 'Aria condizionata negli spazi comuni', 'included' => true],
            ['label' => 'Pranzo', 'included' => true],
            ['label' => 'Ascensore', 'included' => true],
            ['label' => 'Wifi', 'included' => true],
            ['label' => 'Noleggio bici', 'included' => false],
            ['label' => 'Spa', 'included' => false],
        ],
        [
            ['label' => 'Dog sitter', 'included' => true],
            ['label' => 'Servizio veterinario', 'included' => true],
            ['label' => 'Omaggio di benvenuto', 'included' => true],
            ['label' => 'Dog Beach nelle vicinanze', 'included' => true],
            ['label' => 'Supplemento animali', 'included' => false],
            ['label' => 'Piscina per cani', 'included' => false],
        ],
    ];

    public function mount(string $event): void
    {
        abort_unless(collect(Events::EVENTS)->contains('slug', $event), 404);

        $this->eventSlug = $event;
    }

    public function render()
    {
        $event = collect(Events::EVENTS)->firstWhere('slug', $this->eventSlug);

        return view('livewire.event-detail', ['event' => $event, 'includedColumns' => self::INCLUDED])
            ->title('AnimalAmo — '.$event['title']);
    }
}
