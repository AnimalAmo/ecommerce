<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Viaggi e servizi pet-friendly')]
class HomePage extends Component
{
    public string $where = '';

    public string $when = '';

    // Data mirrors XD "Homepage – 4" (real copy from the artboard; body text was lorem placeholder).
    public array $regions = [
        ['img' => 'holiday-liguria',  'name' => 'Hotel e servizi in Liguria',             'structures' => 10],
        ['img' => 'holiday-veneto',   'name' => 'Hotel e servizi in Veneto',              'structures' => 5],
        ['img' => 'holiday-trentino', 'name' => 'Hotel e servizi in Trentino-Alto Adige', 'structures' => 7],
    ];

    public array $events = [
        ['img' => 'event-cavallo',  'title' => 'Passeggiata a cavallo',                'location' => 'Genova, Italia',                'date' => 'Oggi alle ore 12:30',        'price' => null],
        ['img' => 'event-mare',     'title' => 'Weekend al mare',                      'location' => 'Fiesole (FI), Toscana',         'date' => 'Lun, 8 Gen alle ore 19:30',  'price' => '25,00'],
        ['img' => 'event-asini',    'title' => 'Esperienza con gli asini in fattoria', 'location' => 'Manciano (GR), Toscana',        'date' => 'Oggi alle ore 15:00',        'price' => '18,00'],
        ['img' => 'event-maneggio', 'title' => 'Weekend in maneggio',                  'location' => 'Massa Lubrense (NA), Campania', 'date' => 'Ven, 18 Gen alle ore 15:00', 'price' => '35,00'],
        ['img' => 'event-cavallo',  'title' => 'Trekking al lago',                     'location' => 'Molveno (TN), Trentino',        'date' => 'Sab, 20 Gen alle ore 09:00', 'price' => '12,00'],
    ];

    public array $news = [
        ['img' => 'news-trenitalia', 'date' => '20 Ottobre 2023', 'title' => 'Novità Trenitalia trasporto animali', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'news-easyjet',    'date' => '3 Ottobre 2023',  'title' => 'Novità EasyJet trasporto animali',    'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'event-cavallo',   'date' => '5 Ottobre 2025',  'title' => 'Viaggiare in montagna con il cane',   'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
    ];

    public function search(): void
    {
        // TODO: redirect to search results once the listing page exists.
    }

    public function render()
    {
        return view('livewire.home-page');
    }
}
