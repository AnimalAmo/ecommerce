<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\ArticleEdit;
use App\Models\Article\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->actingAsSuperadmin(['first_name' => 'Silvia', 'last_name' => 'Rossi']);
    }

    private function cover(): UploadedFile
    {
        return UploadedFile::fake()->image('Foto Copertina.JPG', 1600, 900);
    }

    private function published(array $attributes = []): Article
    {
        $article = Article::create(array_merge([
            'slug' => 'viaggiare-col-cane',
            'title' => ['it' => 'Viaggiare col cane'],
            'body' => ['it' => '<p>Il testo.</p>'],
            'published_at' => '2026-09-02',
        ], $attributes));

        $article->addMedia($this->cover())->toMediaCollection(Article::COVER);

        return $article;
    }

    public function test_a_draft_saves_without_a_cover_and_stays_off_the_site(): void
    {
        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Cinque sentieri sul Garda')
            ->set('body.it', '<p>Una bozza.</p>')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.articles.edit', Article::sole()));

        $article = Article::sole();
        $this->assertSame('cinque-sentieri-sul-garda', $article->slug);
        $this->assertNull($article->published_at);
        $this->assertSame(Article::DRAFT, $article->status());
        $this->assertSame($this->admin->id, $article->author_id);

        $this->get(route('news.detail', 'cinque-sentieri-sul-garda'))->assertNotFound();
        $this->get(route('news'))->assertDontSee('Cinque sentieri sul Garda');
    }

    public function test_publishing_needs_a_cover_and_the_italian_text(): void
    {
        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Senza foto')
            ->set('body.it', '<p>Testo.</p>')
            ->call('publish')
            ->assertHasErrors('cover')
            ->assertSee(__('admin-content.articles.cover_required'));

        Livewire::test(ArticleEdit::class)
            ->set('locale', 'en')
            ->set('title.it', 'Senza testo')
            ->set('body.it', '<p></p>')
            ->set('cover', $this->cover())
            ->call('publish')
            ->assertHasErrors('body.it')
            ->assertSet('locale', 'it');

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_an_article_is_published_with_its_cover_and_shows_up_on_the_site(): void
    {
        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Come far diventare il tuo B&B pet friendly')
            ->set('title.en', 'How to make your B&B pet friendly')
            ->set('excerpt.it', 'Ciotole, un giardino recintato e regole chiare.')
            ->set('body.it', '<h2>Si parte</h2><p>Chi viaggia con il cane prenota in un altro modo.</p><script>x()</script>')
            ->set('body.en', '<p>Guests travelling with a dog book differently.</p>')
            ->set('coverAlt.it', 'Una camera con la cuccia')
            ->set('category', 'partners')
            ->set('cover', $this->cover())
            ->call('publish')
            ->assertHasNoErrors();

        $article = Article::sole();
        $this->assertSame(Article::PUBLISHED, $article->status());
        $this->assertSame(today()->toDateString(), $article->published_at->toDateString());
        $this->assertSame('partners', $article->category);
        $this->assertSame('<h2>Si parte</h2><p>Chi viaggia con il cane prenota in un altro modo.</p>', $article->getTranslation('body', 'it'));
        $this->assertTrue($article->hasMedia(Article::COVER));
        $this->assertSame('come-far-diventare-il-tuo-bb-pet-friendly.jpg', $article->getFirstMedia(Article::COVER)->file_name);

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Come far diventare il tuo B&amp;B pet friendly', false)
            ->assertSee('Ciotole, un giardino recintato e regole chiare.')
            ->assertSee('conversions/come-far-diventare-il-tuo-bb-pet-friendly-card.jpg', false)
            ->assertSee('alt="Una camera con la cuccia"', false);

        $this->get(route('news.detail', $article->slug))
            ->assertOk()
            ->assertSee('<h2>Si parte</h2>', false)
            ->assertDontSee('x()', false);
    }

    public function test_a_future_date_schedules_the_article(): void
    {
        $date = now()->addDays(10)->toDateString();

        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Il cane in treno')
            ->set('body.it', '<p>Testo.</p>')
            ->set('cover', $this->cover())
            ->set('publishedAt', $date)
            ->call('publish')
            ->assertHasNoErrors();

        $article = Article::sole();
        $this->assertSame(Article::SCHEDULED, $article->status());
        $this->get(route('news.detail', $article->slug))->assertNotFound();

        $this->travelTo(now()->addDays(10));
        $this->get(route('news.detail', $article->slug))->assertOk();
    }

    public function test_editing_a_published_article_keeps_it_online(): void
    {
        $article = $this->published();

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->assertSet('publishedAt', '2026-09-02')
            ->assertSee('Riporta in bozza')
            ->set('title.it', 'Viaggiare col cane, edizione 2026')
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $this->assertSame('Viaggiare col cane, edizione 2026', $article->titleFor('it'));
        $this->assertSame('2026-09-02', $article->published_at->toDateString());
        $this->assertSame('viaggiare-col-cane', $article->slug, 'lo slug non cambia da solo col titolo');
    }

    public function test_unpublishing_takes_the_article_off_the_site(): void
    {
        $article = $this->published();

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->call('unpublish')
            ->assertSet('publishedAt', '');

        $this->assertNull($article->refresh()->published_at);
        $this->get(route('news.detail', 'viaggiare-col-cane'))->assertNotFound();
    }

    public function test_a_new_cover_replaces_the_old_one(): void
    {
        $article = $this->published();
        $old = $article->getFirstMedia(Article::COVER)->getPathRelativeToRoot();

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->set('cover', UploadedFile::fake()->image('nuova.png', 1600, 900))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('cover', null);

        $article->refresh();
        $this->assertSame(1, $article->getMedia(Article::COVER)->count());
        $this->assertSame('viaggiare-col-cane.png', $article->getFirstMedia(Article::COVER)->file_name);
        Storage::disk('public')->assertMissing($old);
    }

    /** Un'immagine vera con un nome da pagina web: passa, ma salvata come immagine. */
    public function test_a_cover_named_like_a_web_page_is_saved_with_the_image_extension(): void
    {
        $article = $this->published();

        ob_start();
        imagepng(imagecreatetruecolor(1600, 900));
        $png = ob_get_clean().'<script>alert(document.cookie)</script>';

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->set('cover', UploadedFile::fake()->createWithContent('cover.html', $png)->mimeType('image/png'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('viaggiare-col-cane.png', $article->refresh()->getFirstMedia(Article::COVER)->file_name);
    }

    public function test_the_cover_must_be_an_image(): void
    {
        Livewire::test(ArticleEdit::class)
            ->set('cover', UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'))
            ->assertHasErrors('cover')
            ->assertSee('Copertina: serve un&#039;immagine.', false);
    }

    public function test_a_taken_address_is_refused_and_a_derived_one_gets_a_suffix(): void
    {
        $this->published();

        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Altro articolo')
            ->set('slug', 'Viaggiare col cane')
            ->call('saveDraft')
            ->assertHasErrors('slug');

        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Viaggiare col cane')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertTrue(Article::where('slug', 'viaggiare-col-cane-2')->exists());
    }

    public function test_the_article_can_be_deleted_from_the_editor(): void
    {
        $article = $this->published();

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->assertSee('Eliminare questo articolo?')
            ->call('delete')
            ->assertRedirect(route('admin.articles.index'));

        $this->assertModelMissing($article);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_the_preview_shows_the_open_language(): void
    {
        Livewire::test(ArticleEdit::class)
            ->set('title.it', 'Titolo italiano')
            ->set('body.it', '<p>Corpo italiano.</p>')
            ->set('title.en', 'English title')
            ->set('body.en', '<p>English body.</p>')
            ->call('preview')
            ->assertSee('Corpo italiano.')
            ->set('locale', 'en')
            ->assertSee('English title')
            ->assertSee('English body.');
    }

    public function test_the_editor_shows_status_and_author(): void
    {
        $article = $this->published(['author_id' => $this->admin->id]);

        $this->get(route('admin.articles.edit', $article))
            ->assertOk()
            ->assertSee('Viaggiare col cane')
            ->assertSee('Pubblicato il 2 set 2026')
            ->assertSee('autore: Silvia Rossi')
            ->assertSee(route('news.detail', 'viaggiare-col-cane'), false);
    }

    public function test_the_site_counts_the_reads(): void
    {
        $article = $this->published();

        $this->get(route('news.detail', 'viaggiare-col-cane'))->assertOk();
        $this->get(route('news.detail', 'viaggiare-col-cane'))->assertOk();

        $this->assertSame(2, $article->refresh()->views);
    }
}
