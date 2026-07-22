<?php

namespace App\Livewire\Content;

use Livewire\Component;

class News extends Component
{
    /**
     * Articoli campione in ordine di griglia XD (riga per riga).
     * slug: riservato alla futura pagina News dettaglio.
     * Excerpt vuoto: copy in attesa della cliente — le card degradano a foto+data+titolo+link.
     */
    private const EXCERPT = '';

    public const ARTICLES = [
        ['title' => 'Nuove normative strutture pet friendly', 'slug' => 'normative-strutture-pet-friendly', 'date' => '5 Ottobre 2023', 'img' => 'news-regulations', 'excerpt' => self::EXCERPT],
        ['title' => 'Novità Trenitalia trasporto animali', 'slug' => 'novita-trenitalia-trasporto-animali', 'date' => '20 Ottobre 2023', 'img' => 'news-trenitalia', 'excerpt' => self::EXCERPT],
        ['title' => 'Novità EasyJet trasporto animali', 'slug' => 'novita-easyjet-trasporto-animali', 'date' => '3 Ottobre 2023', 'img' => 'news-easyjet', 'excerpt' => self::EXCERPT],
        ['title' => 'Presenza pronto soccorso veterinario', 'slug' => 'presenza-pronto-soccorso-veterinario', 'date' => '5 Ottobre 2023', 'img' => 'news-vet-first-aid', 'excerpt' => self::EXCERPT],
        ['title' => 'Assistenza animali', 'slug' => 'assistenza-animali', 'date' => '20 Ottobre 2023', 'img' => 'news-animal-care', 'excerpt' => self::EXCERPT],
        ['title' => 'Novità trasporto animali', 'slug' => 'novita-trasporto-animali', 'date' => '3 Ottobre 2023', 'img' => 'news-animal-transport', 'excerpt' => self::EXCERPT],
    ];

    public function render()
    {
        return view('livewire.content.news', ['articles' => self::ARTICLES])->title(__('news.page_title'));
    }
}
