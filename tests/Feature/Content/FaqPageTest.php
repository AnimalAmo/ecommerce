<?php

namespace Tests\Feature\Content;

use App\Models\Faq\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /domande-frequenti: le FAQ di piattaforma scritte dal pannello. */
class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_the_platform_questions_by_topic_in_order(): void
    {
        Faq::factory()->platform('smartbox')->create(['question' => ['it' => 'Quanto vale un cofanetto?'], 'answer' => ['it' => 'Diciotto mesi.'], 'position' => 1]);
        Faq::factory()->platform('bookings')->create(['question' => ['it' => 'Come disdico?'], 'answer' => ['it' => 'Dal profilo.'], 'position' => 2]);
        Faq::factory()->platform('bookings')->create(['question' => ['it' => 'Posso portare due cani?'], 'answer' => ['it' => 'Dipende.'], 'position' => 1]);

        $this->get('/domande-frequenti')
            ->assertOk()
            ->assertSeeInOrder(['Prenotazioni', 'Posso portare due cani?', 'Dipende.', 'Come disdico?', 'Smartbox', 'Quanto vale un cofanetto?'])
            ->assertDontSee('Pagamenti');
    }

    public function test_product_questions_stay_on_their_product(): void
    {
        Faq::factory()->create(['question' => ['it' => 'Domanda di una struttura'], 'answer' => ['it' => 'Risposta.']]);

        $this->get('/domande-frequenti')
            ->assertOk()
            ->assertDontSee('Domanda di una struttura')
            ->assertSee(__('faq.empty'));
    }

    public function test_the_english_page_falls_back_to_italian(): void
    {
        $faq = Faq::factory()->platform('payments')->create([
            'question' => ['it' => 'Quando pago?', 'en' => 'When do I pay?'],
            'answer' => ['it' => 'Al checkout.'],
        ]);

        app()->setLocale('en');

        $this->assertSame('When do I pay?', $faq->question);
        $this->assertSame('Al checkout.', $faq->answer);
    }

    public function test_the_footer_links_the_page_only_when_it_has_questions(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('/domande-frequenti', false);

        Faq::factory()->platform()->create(['question' => ['it' => 'Domanda?'], 'answer' => ['it' => 'Risposta.']]);

        $this->get(route('home'))->assertOk()->assertSee('/domande-frequenti', false);
    }

    public function test_the_sitemap_lists_the_page_only_when_it_has_questions(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/domande-frequenti', false);

        Faq::factory()->platform()->create(['question' => ['it' => 'Domanda?'], 'answer' => ['it' => 'Risposta.']]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/domande-frequenti', false)
            ->assertSee('/en/faq', false);
    }
}
