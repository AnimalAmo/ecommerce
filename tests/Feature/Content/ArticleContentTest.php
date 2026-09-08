<?php

namespace Tests\Feature\Content;

use App\Models\Article\Article;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Invarianti dei file prodotti da docx-to-article.py: fanno fallire la suite se
 * una riconversione futura reintroduce lo sporco di Word o perde una foto.
 */
class ArticleContentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{0: string}> */
    public static function slugProvider(): array
    {
        return [
            ['come-far-diventare-il-tuo-bb-un-alloggio-pet-friendly'],
            ['come-gestire-i-bisogni-del-cucciolo'],
            ['come-migliorare-accoglienza-animali-strutture'],
            ['viaggiare-con-il-tuo-animale'],
        ];
    }

    public function test_the_seeder_loads_every_article(): void
    {
        $this->seed(ArticleSeeder::class);

        $this->assertSame(4, Article::count());
    }

    public function test_reseeding_does_not_duplicate_the_articles(): void
    {
        $this->seed(ArticleSeeder::class);
        $this->seed(ArticleSeeder::class);

        $this->assertSame(4, Article::count());
    }

    public function test_the_articles_come_out_newest_first(): void
    {
        $this->seed(ArticleSeeder::class);

        $this->assertSame([
            'come-far-diventare-il-tuo-bb-un-alloggio-pet-friendly',
            'come-gestire-i-bisogni-del-cucciolo',
            'come-migliorare-accoglienza-animali-strutture',
            'viaggiare-con-il-tuo-animale',
        ], Article::published()->pluck('slug')->all());
    }

    /** La data è quella stampata sulla grafica social, non quella del file Word. */
    public function test_the_dates_are_the_publication_ones(): void
    {
        $this->seed(ArticleSeeder::class);

        $this->assertSame(
            '2025-03-14',
            Article::where('slug', 'viaggiare-con-il-tuo-animale')->sole()->published_at->toDateString()
        );
    }

    #[DataProvider('slugProvider')]
    public function test_the_html_is_clean(string $slug): void
    {
        $path = database_path("seeders/content/articles/{$slug}.it.html");
        $this->assertFileExists($path);

        $html = file_get_contents($path);

        $this->assertStringNotContainsString('<span', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringNotContainsString('<h1', $html, "L'h1 è del blade, non del corpo");
        $this->assertMatchesRegularExpression('/^<(p|h2|ul)>/', $html);
    }

    #[DataProvider('slugProvider')]
    public function test_every_article_has_its_two_photos(string $slug): void
    {
        $this->assertFileExists(public_path("img/news/{$slug}.jpg"));
        $this->assertFileExists(public_path("img/news/{$slug}-hero.jpg"));
    }

    /** L'occhiello delle card nasce dal primo paragrafo: nessuna colonna da tenere allineata. */
    public function test_the_excerpt_comes_from_the_first_paragraph_without_markup(): void
    {
        $this->seed(ArticleSeeder::class);

        $excerpt = Article::where('slug', 'come-gestire-i-bisogni-del-cucciolo')->sole()->excerptFor('it');

        $this->assertStringStartsWith('L’arrivo di un cucciolo in casa', $excerpt);
        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertLessThanOrEqual(200, mb_strlen($excerpt));
    }

    /** Senza traduzione inglese la pagina non deve uscire vuota: ripiega sull'italiano. */
    public function test_a_missing_translation_falls_back_to_italian(): void
    {
        $this->seed(ArticleSeeder::class);

        $article = Article::where('slug', 'viaggiare-con-il-tuo-animale')->sole();

        $this->assertSame($article->titleFor('it'), $article->titleFor('en'));
        $this->assertNotSame('', $article->bodyFor('en'));
    }
}
