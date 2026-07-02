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
        ['name' => 'Hotel e servizi in Lombardia', 'slug' => 'lombardia', 'img' => 'holiday-lombardia', 'structures' => 10],
        ['name' => 'Hotel e servizi in Lazio', 'slug' => 'lazio', 'img' => 'holiday-lazio', 'structures' => 10],
        ['name' => 'Hotel e servizi in Campania', 'slug' => 'campania', 'img' => 'holiday-campania', 'structures' => 7],
        ['name' => 'Hotel e servizi in Veneto', 'slug' => 'veneto', 'img' => 'holiday-veneto', 'structures' => 5],
        ['name' => 'Hotel e servizi in Sicilia', 'slug' => 'sicilia', 'img' => 'holiday-sicilia', 'structures' => 5],
        ['name' => 'Hotel e servizi in Emilia Romagna', 'slug' => 'emilia-romagna', 'img' => 'holiday-emilia-romagna', 'structures' => 7],
        ['name' => 'Hotel e servizi in Piemonte', 'slug' => 'piemonte', 'img' => 'holiday-piemonte', 'structures' => 10],
        ['name' => 'Hotel e servizi in Puglia', 'slug' => 'puglia', 'img' => 'holiday-puglia', 'structures' => 5],
        ['name' => 'Hotel e servizi in Toscana', 'slug' => 'toscana', 'img' => 'holiday-toscana', 'structures' => 7],
        ['name' => 'Hotel e servizi in Calabria', 'slug' => 'calabria', 'img' => 'holiday-calabria', 'structures' => 10],
        ['name' => 'Hotel e servizi in Sardegna', 'slug' => 'sardegna', 'img' => 'holiday-sardegna', 'structures' => 10],
        ['name' => 'Hotel e servizi in Liguria', 'slug' => 'liguria', 'img' => 'holiday-liguria', 'structures' => 10],
        ['name' => 'Hotel e servizi in Marche', 'slug' => 'marche', 'img' => 'holiday-marche', 'structures' => 10],
        ['name' => 'Hotel e servizi in Abruzzo', 'slug' => 'abruzzo', 'img' => 'holiday-abruzzo', 'structures' => 5],
        ['name' => 'Hotel e servizi in Friuli-Venezia Giulia', 'slug' => 'friuli-venezia-giulia', 'img' => 'holiday-friuli', 'structures' => 10],
        ['name' => 'Hotel e servizi in Trentino-Alto Adige', 'slug' => 'trentino-alto-adige', 'img' => 'holiday-trentino', 'structures' => 7],
        ['name' => 'Hotel e servizi in Umbria', 'slug' => 'umbria', 'img' => 'holiday-umbria', 'structures' => 5],
        ['name' => 'Hotel e servizi in Basilicata', 'slug' => 'basilicata', 'img' => 'holiday-basilicata', 'structures' => 10],
        ['name' => 'Hotel e servizi in Molise', 'slug' => 'molise', 'img' => 'holiday-molise', 'structures' => 10],
        ['name' => 'Hotel e servizi in Valle d’Aosta', 'slug' => 'valle-daosta', 'img' => 'holiday-valle-daosta', 'structures' => 5],
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
