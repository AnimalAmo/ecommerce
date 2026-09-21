<?php

namespace Tests\Feature\Content;

use App\Models\Article\Article;
use App\Services\Content\ArticleService;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Copertine di Animal Times nella media collection `cover`. */
class ArticleCoverTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'come-gestire-i-bisogni-del-cucciolo';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_seeder_gives_every_article_its_cover_with_both_crops(): void
    {
        $this->seed(ArticleSeeder::class);

        foreach (Article::all() as $article) {
            $media = $article->getFirstMedia(Article::COVER);

            $this->assertNotNull($media, "{$article->slug} senza copertina");
            $this->assertTrue($media->hasGeneratedConversion('card'));
            $this->assertTrue($media->hasGeneratedConversion('hero'));
            Storage::disk('public')->assertExists($media->getPathRelativeToRoot('card'));

            [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('card')));
            $this->assertSame([960, 495], [$width, $height]);
        }
    }

    public function test_reseeding_neither_duplicates_nor_replaces_a_cover(): void
    {
        $this->seed(ArticleSeeder::class);

        $article = Article::where('slug', self::SLUG)->sole();
        $article->addMedia(UploadedFile::fake()->image('scelta-dalla-cliente.jpg', 1600, 900))
            ->toMediaCollection(Article::COVER);

        $this->seed(ArticleSeeder::class);

        $this->assertSame(4, Article::count());
        $this->assertSame(1, $article->refresh()->getMedia(Article::COVER)->count());
        $this->assertSame('scelta-dalla-cliente.jpg', $article->getFirstMedia(Article::COVER)->file_name);
    }

    public function test_reseeding_keeps_an_article_edited_from_the_panel(): void
    {
        $this->seed(ArticleSeeder::class);

        $article = Article::where('slug', self::SLUG)->sole();
        $article->setTranslation('title', 'it', 'Titolo della cliente')->save();

        $this->seed(ArticleSeeder::class);

        $this->assertSame('Titolo della cliente', $article->refresh()->titleFor('it'));
    }

    /** Il pezzo della migration: un database già seminato riceve le foto versionate. */
    public function test_an_existing_article_without_a_cover_gets_the_versioned_photo(): void
    {
        $article = Article::create([
            'slug' => self::SLUG,
            'title' => ['it' => 'Cucciolo'],
            'body' => ['it' => '<p>Testo.</p>'],
            'published_at' => '2025-07-18',
        ]);
        $orphan = Article::create([
            'slug' => 'senza-foto',
            'title' => ['it' => 'Senza foto'],
            'body' => ['it' => '<p>Testo.</p>'],
            'published_at' => '2025-07-18',
        ]);

        $covers = app(ArticleService::class);

        $this->assertTrue($covers->importSeedCover($article));
        $this->assertFalse($covers->importSeedCover($article->refresh()), 'la seconda volta non deve rifarlo');
        $this->assertFalse($covers->importSeedCover($orphan));

        $this->assertSame(1, $article->getMedia(Article::COVER)->count());
        // La foto versionata resta dov'è: serve al prossimo database nuovo.
        $this->assertFileExists(ArticleService::seedCoverPath(self::SLUG));
    }

    public function test_the_public_pages_read_the_cover_crops(): void
    {
        $this->seed(ArticleSeeder::class);

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('conversions/'.self::SLUG.'-card.jpg', false)
            ->assertDontSee('img/news/', false);

        $this->get(route('news.detail', self::SLUG))
            ->assertOk()
            ->assertSee('conversions/'.self::SLUG.'-hero.jpg', false);
    }

    public function test_an_article_without_a_cover_shows_a_placeholder_and_its_title_as_alt(): void
    {
        Article::create([
            'slug' => 'senza-copertina',
            'title' => ['it' => 'Senza copertina'],
            'body' => ['it' => '<p>Testo.</p>'],
            'published_at' => now()->subDay()->toDateString(),
        ]);

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Senza copertina')
            ->assertDontSee('<img src="" ', false);
    }

    public function test_the_alt_text_is_the_one_written_by_the_client(): void
    {
        $article = Article::create([
            'slug' => 'con-alt',
            'title' => ['it' => 'Titolo'],
            'body' => ['it' => '<p>Testo.</p>'],
            'cover_alt' => ['it' => 'Un cane al guinzaglio sul sentiero'],
            'published_at' => '2025-01-01',
        ]);

        $this->assertSame('Un cane al guinzaglio sul sentiero', $article->coverAlt('it'));
        $this->assertSame('Un cane al guinzaglio sul sentiero', $article->coverAlt('en'));
        $this->assertSame('Titolo', (new Article(['title' => ['it' => 'Titolo']]))->coverAlt('it'));
    }

    public function test_deleting_an_article_removes_its_cover_files(): void
    {
        $this->seed(ArticleSeeder::class);

        $article = Article::where('slug', self::SLUG)->sole();
        $path = $article->getFirstMedia(Article::COVER)->getPathRelativeToRoot();
        Storage::disk('public')->assertExists($path);

        $article->delete();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('media', 3);
    }
}
