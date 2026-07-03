<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Attività ed Eventi')]
class Events extends Component
{
    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    /**
     * Eventi campione in ordine di griglia XD (riga per riga).
     * type: 'event' = evento singolo (scheda /eventi/{slug}); 'activity' = attività multi-giorno (scheda /eventi/attivita/{slug}).
     * time: null = attività multi-giorno senza orario; timeAccent: true = riga orario #8E53E6 (durata).
     * price: null = default simbolo "A partire da 0,00 €"; button: 'partecipa' | 'carrello'.
     */
    public const EVENTS = [
        ['title' => 'Brunch Pet Friendly', 'slug' => 'brunch-pet-friendly', 'type' => 'event', 'badge' => 'Evento', 'time' => 'OGGI ALLE ORE 13:30', 'timeAccent' => false, 'location' => 'San Pellegrino, Italia', 'price' => '25 € a persona', 'button' => 'carrello', 'img' => 'event-brunch-pet-friendly'],
        ['title' => 'Weekend di escursioni', 'slug' => 'weekend-escursioni', 'type' => 'activity', 'badge' => 'Evento', 'time' => null, 'timeAccent' => false, 'location' => 'Viareggio, Italia', 'price' => '118 € a persona', 'button' => 'carrello', 'img' => 'event-weekend-escursioni'],
        ['title' => 'Festa Pet Friendly', 'slug' => 'festa-pet-friendly', 'type' => 'event', 'badge' => 'Evento', 'time' => 'LUN, 8 GEN ALLE 19:30', 'timeAccent' => false, 'location' => 'Milano, Italia', 'price' => 'Gratis', 'button' => 'partecipa', 'img' => 'event-festa-pet-friendly'],
        ['title' => 'Raduno per cuccioli', 'slug' => 'raduno-cuccioli', 'type' => 'event', 'badge' => 'Evento', 'time' => 'VEN, 18 GEN ALLE 15:00', 'timeAccent' => false, 'location' => 'San Pellegrino, Italia', 'price' => 'Gratis', 'button' => 'partecipa', 'img' => 'event-raduno-cuccioli'],
        ['title' => 'Weekend al mare', 'slug' => 'weekend-mare', 'type' => 'activity', 'badge' => 'Evento', 'time' => null, 'timeAccent' => false, 'location' => 'Genova, Italia', 'price' => '210 € a persona', 'button' => 'carrello', 'img' => 'event-weekend-mare'],
        ['title' => 'Giornata in piscina', 'slug' => 'giornata-piscina', 'type' => 'event', 'badge' => 'Evento', 'time' => 'SAB, 25 FEB ALLE ORE 15:00', 'timeAccent' => false, 'location' => 'Viareggio, Italia', 'price' => '8 € a persona', 'button' => 'carrello', 'img' => 'event-giornata-piscina'],
        ['title' => 'Weekend nel bosco', 'slug' => 'weekend-bosco', 'type' => 'activity', 'badge' => 'Evento', 'time' => null, 'timeAccent' => false, 'location' => 'Brianza, Italia', 'price' => 'Gratis', 'button' => 'partecipa', 'img' => 'event-weekend-bosco'],
        ['title' => 'Puppy Yoga', 'slug' => 'puppy-yoga', 'type' => 'event', 'badge' => 'Evento', 'time' => 'VEN, 18 APR ALLE 15:00', 'timeAccent' => false, 'location' => 'Sassari, Italia', 'price' => '25 € a persona', 'button' => 'carrello', 'img' => 'event-puppy-yoga'],
        ['title' => 'Vacanza di relax in montagna', 'slug' => 'vacanza-montagna', 'type' => 'activity', 'badge' => 'Evento', 'time' => 'DURATA DI 5 GIORNI', 'timeAccent' => true, 'location' => 'Alpi, Italia', 'price' => '250 € a persona', 'button' => 'carrello', 'img' => 'event-vacanza-montagna'],
        ['title' => 'Pomeriggio di addestramento', 'slug' => 'pomeriggio-addestramento', 'type' => 'event', 'badge' => 'Evento', 'time' => 'SAB, 25 MAG ALLE ORE 15:00', 'timeAccent' => false, 'location' => 'Milano, Italia', 'price' => null, 'button' => 'partecipa', 'img' => 'event-pomeriggio-addestramento'],
        ['title' => 'Puppy Yoga', 'slug' => 'puppy-yoga-milano', 'type' => 'event', 'badge' => 'Evento', 'time' => 'LUN, 30 MAG ALLE 15:30', 'timeAccent' => false, 'location' => 'Milano, Italia', 'price' => '25 € a persona', 'button' => 'carrello', 'img' => 'event-puppy-yoga-2'],
        ['title' => 'Giochi per cuccioli', 'slug' => 'giochi-cuccioli', 'type' => 'event', 'badge' => 'Evento', 'time' => 'VEN, 18 GIU ALLE 15:00', 'timeAccent' => false, 'location' => 'Sassari, Italia', 'price' => 'Gratis', 'button' => 'partecipa', 'img' => 'event-giochi-cuccioli'],
    ];

    public function search(): void
    {
        // TODO: filter events once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.events', ['events' => self::EVENTS]);
    }
}
