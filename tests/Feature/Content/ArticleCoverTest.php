<?php

namespace Tests\Feature\Content;

use App\Models\Article\Article;
use App\Services\Content\ArticleService;
use Database\Seeders\ArticleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Copertine di Animal Times nella media collection `cover`. */
class ArticleCoverTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'come-gestire-i-bisogni-del-cucciolo';

    private const COVER_MIGRATION = 'database/migrations/2026_09_19_220002_move_article_covers_to_media_library.php';

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

    /**
     * Un articolo che non riceve la foto versionata (nessun jpg per il suo
     * slug) non deve lasciare senza copertina quelli che vengono dopo: il
     * loop della migration non si ferma al primo "niente da fare".
     */
    public function test_the_cover_migration_goes_past_an_article_without_a_versioned_photo(): void
    {
        $this->article('senza-foto');
        $article = $this->article(self::SLUG);

        // Lo schema di prima della migration: cover_path c'era ancora.
        Schema::table('articles', fn (Blueprint $table) => $table->string('cover_path')->nullable());

        (require base_path(self::COVER_MIGRATION))->up();

        $this->assertTrue($article->refresh()->hasMedia(Article::COVER), 'articolo dopo quello senza foto rimasto senza copertina');
        $this->assertFalse(Schema::hasColumn('articles', 'cover_path'));
    }

    /**
     * Rilancio dopo un'interruzione: il primo articolo ha già la copertina,
     * quelli dopo no. Devono riceverla, senza toccare quella che c'è.
     */
    public function test_importing_the_seed_covers_goes_past_the_articles_that_need_nothing(): void
    {
        $covers = app(ArticleService::class);

        $first = $this->article('viaggiare-con-il-tuo-animale');
        $covers->importSeedCover($first);
        $this->article('senza-foto');
        $last = $this->article(self::SLUG);

        $this->assertSame(1, $covers->importSeedCovers());

        $this->assertTrue($last->refresh()->hasMedia(Article::COVER));
        $this->assertSame(1, $first->refresh()->getMedia(Article::COVER)->count());
        $this->assertDatabaseCount('media', 2);
    }

    /**
     * Il nome del file lo sceglie chi carica: un PNG valido chiamato .html
     * finirebbe sul disco pubblico come pagina servita dall'origine del sito.
     * L'estensione la decide il contenuto.
     */
    public function test_the_cover_extension_comes_from_the_content_not_from_the_uploaded_name(): void
    {
        $article = Article::create([
            'slug' => 'titolo-prova',
            'title' => ['it' => 'Titolo prova'],
            'body' => ['it' => '<p>Testo.</p>'],
        ]);

        ob_start();
        imagepng(imagecreatetruecolor(1600, 900));
        $path = tempnam(sys_get_temp_dir(), 'cover');
        file_put_contents($path, ob_get_clean().'<script>alert(document.cookie)</script>');

        app(ArticleService::class)->replaceCover($article, new UploadedFile($path, 'cover.html', null, null, true));

        $media = $article->refresh()->getFirstMedia(Article::COVER);
        $this->assertSame('titolo-prova.png', $media->file_name);
        $this->assertStringEndsWith('.png', $media->getPathRelativeToRoot());
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

    private function article(string $slug): Article
    {
        return Article::create([
            'slug' => $slug,
            'title' => ['it' => 'Titolo'],
            'body' => ['it' => '<p>Testo.</p>'],
            'published_at' => '2025-07-18',
        ]);
    }
}
