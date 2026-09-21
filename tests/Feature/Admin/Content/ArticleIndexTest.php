<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\ArticleIndex;
use App\Models\Article\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsSuperadmin();
    }

    private function article(string $title, ?string $publishedAt, array $attributes = []): Article
    {
        $article = new Article([
            'slug' => str($title)->slug()->toString(),
            'title' => ['it' => $title],
            'body' => ['it' => '<p>Testo di '.$title.'.</p>'],
            'published_at' => $publishedAt,
        ]);
        // forceFill: anche le letture, che nessun form scrive.
        $article->forceFill($attributes)->save();

        return $article;
    }

    public function test_the_list_shows_published_scheduled_and_draft_articles(): void
    {
        $this->article('Viaggiare con il cane', '2026-09-02', ['category' => 'travel', 'views' => 1402]);
        $this->article('Cinque sentieri sul Garda', null);
        $this->article('Il cane in treno', now()->addWeek()->toDateString());

        $this->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('Gli articoli del magazine. 1 pubblicato, 1 in bozza.')
            ->assertSee('Viaggiare con il cane')
            ->assertSee('Viaggi')
            ->assertSee('1.402')
            ->assertSee('2 set 2026')
            ->assertSee('Cinque sentieri sul Garda')
            ->assertSee('In bozza')
            ->assertSee('Il cane in treno')
            ->assertSee('Programmato');
    }

    public function test_filters_narrow_the_list(): void
    {
        $this->article('Viaggiare con il cane', '2026-09-02', ['category' => 'travel']);
        $this->article('Cuccioli in casa', null, ['category' => 'puppies']);

        Livewire::test(ArticleIndex::class)
            ->set('status', 'draft')
            ->assertSee('Cuccioli in casa')
            ->assertDontSee('Viaggiare con il cane')
            ->set('status', '')
            ->set('category', 'travel')
            ->assertSee('Viaggiare con il cane')
            ->assertDontSee('Cuccioli in casa')
            ->set('category', '')
            ->set('search', 'CUCCIOLI')
            ->assertSee('Cuccioli in casa')
            ->assertDontSee('Viaggiare con il cane');
    }

    public function test_the_english_column_says_which_articles_are_translated(): void
    {
        $this->article('Solo italiano', '2026-09-02');
        $this->article('Tradotto', '2026-09-01', [
            'title' => ['it' => 'Tradotto', 'en' => 'Translated'],
            'body' => ['it' => '<p>Testo.</p>', 'en' => '<p>Text.</p>'],
        ]);

        $this->get(route('admin.articles.index'))
            ->assertSeeInOrder(['Solo italiano', 'IT', 'Tradotto', 'IT, EN']);
    }

    public function test_deleting_asks_first_and_removes_the_cover(): void
    {
        $article = $this->article('Da cancellare', '2026-09-02');
        $article->addMedia(UploadedFile::fake()->image('cover.jpg', 1600, 900))->toMediaCollection(Article::COVER);
        $path = $article->getFirstMedia(Article::COVER)->getPathRelativeToRoot();

        Livewire::test(ArticleIndex::class)
            ->call('askDelete', $article->id)
            ->assertSet('deleting', $article->id)
            ->assertSee('Eliminare questo articolo?')
            ->assertSee('“Da cancellare” verrà rimosso dal sito')
            ->call('confirmDelete')
            ->assertSet('deleting', null);

        $this->assertModelMissing($article);
        $this->assertDatabaseCount('media', 0);
        Storage::disk('public')->assertMissing($path);
    }
}
