<?php

namespace App\Livewire\Content;

use Livewire\Component;

class NewsDetail extends Component
{
    /** Slug articolo dalla rotta (es. "normative-strutture-pet-friendly"); il nome differisce dal parametro {article} per non collidere col binding Livewire. */
    public string $articleSlug = '';

    /**
     * Paragrafo del corpo articolo — lorem come da XD "News – dettaglio"
     * (i primi tre paragrafi sono identici nell'artboard).
     */
    private const PARAGRAPH = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';

    /** Corpo articolo: 4 paragrafi come nel frame testo XD (978x486, il quarto è più corto). */
    public const BODY = [
        self::PARAGRAPH,
        self::PARAGRAPH,
        self::PARAGRAPH,
        'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.',
    ];

    /**
     * Ordine di preferenza degli "Articoli correlati": la selezione dell'artboard XD
     * (Trenitalia, EasyJet, Trasporto animali, Assistenza animali) seguita dagli altri.
     * I correlati sono i primi 4 di questa lista escluso l'articolo corrente — con
     * l'articolo campione ("Nuove normative…") riproduce esattamente l'artboard.
     */
    private const RELATED_ORDER = [
        'novita-trenitalia-trasporto-animali',
        'novita-easyjet-trasporto-animali',
        'novita-trasporto-animali',
        'assistenza-animali',
        'presenza-pronto-soccorso-veterinario',
        'normative-strutture-pet-friendly',
    ];

    public function mount(string $article): void
    {
        abort_unless(collect(News::ARTICLES)->contains('slug', $article), 404);

        $this->articleSlug = $article;
    }

    public function render()
    {
        $article = collect(News::ARTICLES)->firstWhere('slug', $this->articleSlug);

        $related = collect(self::RELATED_ORDER)
            ->reject(fn (string $slug) => $slug === $this->articleSlug)
            ->take(4)
            ->map(fn (string $slug) => collect(News::ARTICLES)->firstWhere('slug', $slug))
            ->values()
            ->all();

        // Foto hero 620x451 (@2x 1240x902): variante "-hero" ottimizzata dai sorgenti XD;
        // per EasyJet il sorgente non è nell'export → fallback alla foto card 620px (object-cover).
        $hero = 'img/xd/'.$article['img'].'-hero.jpg';
        if (! file_exists(public_path($hero))) {
            $hero = 'img/xd/'.$article['img'].'.jpg';
        }

        return view('livewire.content.news-detail', [
            'article' => $article,
            'body' => self::BODY,
            'hero' => $hero,
            'related' => $related,
        ])->title(__('news.detail_page_title', ['title' => $article['title']]));
    }
}
