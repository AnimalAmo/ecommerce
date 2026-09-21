<?php

namespace App\Services\Content;

use App\Models\Article\Article;

/**
 * Articoli di Animal Times: copertine e scrittura dal pannello.
 */
class ArticleService
{
    /** Foto versionate degli articoli della consegna di settembre, una per slug. */
    public static function seedCoverPath(string $slug): string
    {
        return database_path("seeders/content/articles/{$slug}.jpg");
    }

    /**
     * Mette nella collection `cover` la foto versionata dell'articolo, se ne
     * esiste una e l'articolo non ha già una copertina (che vince sempre: è
     * quella scelta dalla cliente). La usano l'ArticleSeeder su un database
     * nuovo e la migration che ha tolto le foto da public/img/news.
     */
    public function importSeedCover(Article $article): bool
    {
        $path = self::seedCoverPath($article->slug);

        if (! is_file($path) || $article->hasMedia(Article::COVER)) {
            return false;
        }

        $article->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection(Article::COVER);

        return true;
    }
}
