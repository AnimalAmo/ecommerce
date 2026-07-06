<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

class ProfileEvents extends Component
{
    /** Tab attiva (deep-link ?tab=passati come gli ordini). */
    #[Url(as: 'tab', except: 'programma')]
    public string $tab = 'programma';

    public const TABS = ['programma' => 'In programma', 'passati' => 'Passati'];

    /**
     * Eventi mock come da XD "Profilo – Eventi a cui partecipo" (un solo evento in programma).
     * Nessun artboard per il tab Passati: lista vuota con la riga "Nessun risultato" dei preferiti.
     */
    // TODO: eventi reali da backend
    public const EVENTS = [
        'programma' => [
            [
                'id' => 'festa-pet-friendly',
                'title' => 'Festa Pet Friendly',
                'tag' => 'Evento',
                'photo' => 'event-festa-pet-friendly.jpg',
                'time' => 'LUN, 30 MAG ALLE 15:30',
                'location' => 'Milano, Italia',
                'price' => 'Gratis',
            ],
        ],
        'passati' => [],
    ];

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.profile-events', [
            'tabs' => self::TABS,
            'events' => self::EVENTS[$this->tab],
        ])->title('Eventi a cui partecipo — AnimalAmo');
    }
}
