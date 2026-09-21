<?php

namespace Database\Seeders;

use App\Models\Article\Article;
use App\Services\Content\ArticleService;
use Illuminate\Database\Seeder;

/**
 * Carica gli articoli di Animal Times dai file HTML versionati in
 * seeders/content/articles/, prodotti da docx-to-article.py sui .docx della
 * cliente (consegna del 01/09/2026).
 *
 * Titolo e data non stanno nei .docx: il titolo del documento è tutto
 * maiuscolo, e la data del file è quella dell'export Word. Quelle qui sotto
 * sono le date di pubblicazione stampate sulle grafiche social.
 *
 * Come il PageSeeder crea solo quello che manca (firstOrCreate, mai
 * updateOrCreate): da quando la cliente scrive gli articoli dal pannello il
 * database è la fonte di verità, e un db:seed non deve riscriverli. Per lo
 * stesso motivo la copertina versionata ({slug}.jpg accanto all'HTML) entra
 * solo in un articolo che non ne ha già una.
 */
class ArticleSeeder extends Seeder
{
    /** @var array<string, array{title: string, published_at: string}> */
    private const ARTICLES = [
        'come-far-diventare-il-tuo-bb-un-alloggio-pet-friendly' => [
            'title' => 'Come far diventare il tuo B&B un alloggio pet-friendly',
            'published_at' => '2025-08-11',
        ],
        'come-gestire-i-bisogni-del-cucciolo' => [
            'title' => 'Come gestire i bisogni del cucciolo: consigli per una convivenza armoniosa',
            'published_at' => '2025-07-18',
        ],
        'come-migliorare-accoglienza-animali-strutture' => [
            'title' => 'Come migliorare l’accoglienza per gli animali: idee innovative per le strutture',
            'published_at' => '2025-06-13',
        ],
        'viaggiare-con-il-tuo-animale' => [
            'title' => 'Viaggiare con il tuo animale: consigli pratici per vacanze pet-friendly',
            'published_at' => '2025-03-14',
        ],
    ];

    private const LOCALES = ['it', 'en'];

    public function run(ArticleService $articles): void
    {
        foreach (self::ARTICLES as $slug => $attributes) {
            $body = $this->bodies($slug);

            if ($body === []) {
                continue;
            }

            $article = Article::firstOrCreate(['slug' => $slug], [
                // Titolo tradotto solo in italiano: la cliente non ha ancora
                // consegnato le versioni inglesi (il model ripiega sull'italiano).
                'title' => ['it' => $attributes['title']],
                'body' => $body,
                'published_at' => $attributes['published_at'],
            ]);

            $articles->importSeedCover($article);
        }
    }

    /**
     * Le lingue senza file vengono saltate: il model ripiega sull'italiano,
     * così una traduzione che arriva dopo non blocca la pubblicazione.
     *
     * @return array<string, string>
     */
    private function bodies(string $slug): array
    {
        $bodies = [];

        foreach (self::LOCALES as $locale) {
            $path = database_path("seeders/content/articles/{$slug}.{$locale}.html");

            if (is_file($path)) {
                $bodies[$locale] = trim(file_get_contents($path));
            }
        }

        return $bodies;
    }
}
