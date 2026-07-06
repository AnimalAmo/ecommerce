<?php

namespace App\Livewire;

use App\Models\Event\Event;
use App\Models\Region\Region;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Viaggi e servizi pet-friendly')]
class HomePage extends Component
{
    public string $where = '';

    public string $when = '';

    // News ancora mock: il backend news arriva con lo step 5 della roadmap.
    public array $news = [
        ['img' => 'news-trenitalia', 'date' => '20 Ottobre 2023', 'title' => 'Novità Trenitalia trasporto animali', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'news-easyjet', 'date' => '3 Ottobre 2023', 'title' => 'Novità EasyJet trasporto animali', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'event-cavallo', 'date' => '5 Ottobre 2025', 'title' => 'Viaggiare in montagna con il cane', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
    ];

    public function search(): void
    {
        // TODO: redirect to search results once the listing page exists.
    }

    public function render()
    {
        return view('livewire.home-page', [
            'regions' => Region::whereNotNull('home_position')->orderBy('home_position')->get(),
            'events' => Event::whereNotNull('home_position')->orderBy('home_position')->get(),
        ]);
    }
}
