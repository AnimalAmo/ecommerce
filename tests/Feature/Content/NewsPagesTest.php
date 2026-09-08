<?php

namespace Tests\Feature\Content;

use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
    }

    public function test_the_list_shows_the_articles_of_the_client(): void
    {
        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Animal Times')
            ->assertSee('Come far diventare il tuo B&amp;B un alloggio pet-friendly', false)
            ->assertSee('Viaggiare con il tuo animale', false)
            ->assertSee('11 Agosto 2025');
    }

    /** Gli articoli campione dell'XD non devono sopravvivere alla messa online. */
    public function test_the_placeholder_articles_are_gone(): void
    {
        $this->get(route('news'))
            ->assertOk()
            ->assertDontSee('Novità Trenitalia trasporto animali')
            ->assertDontSee('news-regulations');
    }

    /** Con meno articoli di una pagina il bottone non deve comparire: non avrebbe nulla da caricare. */
    public function test_the_load_more_button_stays_hidden_when_everything_fits(): void
    {
        $this->get(route('news'))
            ->assertOk()
            ->assertDontSee(__('news.load_more'));
    }

    public function test_the_detail_renders_the_body_as_html(): void
    {
        $this->get(route('news.detail', 'come-gestire-i-bisogni-del-cucciolo'))
            ->assertOk()
            ->assertSee('Come gestire i bisogni del cucciolo', false)
            // Guardia contro un `{!! !!}` che diventasse `{{ }}`: il tag
            // letterale sparirebbe pur restando vero il resto.
            ->assertSee('<h2>✨ Preparare la casa</h2>', false)
            ->assertSee('img/news/come-gestire-i-bisogni-del-cucciolo-hero.jpg', false);
    }

    public function test_the_detail_lists_the_other_articles_as_related(): void
    {
        $response = $this->get(route('news.detail', 'come-gestire-i-bisogni-del-cucciolo'));

        $response->assertOk()
            ->assertSee(__('news.related'))
            ->assertSee('viaggiare-con-il-tuo-animale', false);
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get(route('news.detail', 'normative-strutture-pet-friendly'))->assertNotFound();
    }

    public function test_the_home_shows_the_three_newest_articles(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Come far diventare il tuo B&amp;B un alloggio pet-friendly', false)
            ->assertSee('img/news/come-gestire-i-bisogni-del-cucciolo.jpg', false)
            ->assertDontSee('Novità Trenitalia trasporto animali');
    }
}
