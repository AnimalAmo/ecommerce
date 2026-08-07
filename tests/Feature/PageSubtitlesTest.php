<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sottotitoli delle quattro pagine di sezione, testo fornito dalla cliente
 * (ago 2026). Stanno nei file lang: il test verifica che arrivino in pagina,
 * perché un titolo senza sottotitolo non rompe nulla e passerebbe inosservato.
 */
class PageSubtitlesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public static function pages(): array
    {
        return [
            'Animal Times' => ['news', 'news.subtitle'],
            'Community' => ['community', 'community.subtitle'],
            'Smartbox' => ['smartbox', 'smartbox.subtitle'],
            'Attività ed Eventi' => ['eventi', 'events.subtitle'],
        ];
    }

    #[DataProvider('pages')]
    public function test_the_page_shows_its_subtitle_under_the_title(string $route, string $key): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSeeText(__($key));
    }

    /**
     * La Community ha due intestazioni — hero desktop "Animal Network" e titolo
     * mobile "Community" — e il sottotitolo deve comparire sotto entrambe,
     * altrimenti da telefono sparisce.
     */
    public function test_the_community_subtitle_is_rendered_for_both_layouts(): void
    {
        $html = $this->get(route('community'))->assertOk()->getContent();

        $this->assertSame(2, mb_substr_count($html, __('community.subtitle')));
    }
}
