<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHoliday extends Component
{
    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    /** Regioni in ordine di griglia XD (riga per riga). */
    public array $regions = [
        ['name' => 'Hotel e servizi in Lombardia', 'img' => 'holiday-lombardia', 'structures' => 10],
        ['name' => 'Hotel e servizi in Lazio', 'img' => 'holiday-lazio', 'structures' => 10],
        ['name' => 'Hotel e servizi in Campania', 'img' => 'holiday-campania', 'structures' => 7],
        ['name' => 'Hotel e servizi in Veneto', 'img' => 'holiday-veneto', 'structures' => 5],
        ['name' => 'Hotel e servizi in Sicilia', 'img' => 'holiday-sicilia', 'structures' => 5],
        ['name' => 'Hotel e servizi in Emilia Romagna', 'img' => 'holiday-emilia-romagna', 'structures' => 7],
        ['name' => 'Hotel e servizi in Piemonte', 'img' => 'holiday-piemonte', 'structures' => 10],
        ['name' => 'Hotel e servizi in Puglia', 'img' => 'holiday-puglia', 'structures' => 5],
        ['name' => 'Hotel e servizi in Toscana', 'img' => 'holiday-toscana', 'structures' => 7],
        ['name' => 'Hotel e servizi in Calabria', 'img' => 'holiday-calabria', 'structures' => 10],
        ['name' => 'Hotel e servizi in Sardegna', 'img' => 'holiday-sardegna', 'structures' => 10],
        ['name' => 'Hotel e servizi in Liguria', 'img' => 'holiday-liguria', 'structures' => 10],
        ['name' => 'Hotel e servizi in Marche', 'img' => 'holiday-marche', 'structures' => 10],
        ['name' => 'Hotel e servizi in Abruzzo', 'img' => 'holiday-abruzzo', 'structures' => 5],
        ['name' => 'Hotel e servizi in Friuli-Venezia Giulia', 'img' => 'holiday-friuli', 'structures' => 10],
        ['name' => 'Hotel e servizi in Trentino-Alto Adige', 'img' => 'holiday-trentino', 'structures' => 7],
        ['name' => 'Hotel e servizi in Umbria', 'img' => 'holiday-umbria', 'structures' => 5],
        ['name' => 'Hotel e servizi in Basilicata', 'img' => 'holiday-basilicata', 'structures' => 10],
        ['name' => 'Hotel e servizi in Molise', 'img' => 'holiday-molise', 'structures' => 10],
        ['name' => 'Hotel e servizi in Valle d’Aosta', 'img' => 'holiday-valle-daosta', 'structures' => 5],
    ];

    public function search(): void
    {
        // TODO: filter structures once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.animal-holiday');
    }
}
