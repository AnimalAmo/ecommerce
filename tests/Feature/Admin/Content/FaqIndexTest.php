<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\FaqIndex;
use App\Models\Event\Event;
use App\Models\Faq\Faq;
use App\Models\Structure\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FaqIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsSuperadmin();
    }

    private function platformFaq(string|array $question, string $topic = 'bookings', int $position = 1, array $answer = ['it' => 'Risposta.']): Faq
    {
        return Faq::factory()->platform($topic)->create([
            'question' => is_array($question) ? $question : ['it' => $question],
            'answer' => $answer,
            'position' => $position,
        ]);
    }

    public function test_the_platform_questions_are_grouped_by_topic(): void
    {
        $this->platformFaq(['it' => 'Posso portare più di un animale?', 'en' => 'Can I bring more than one pet?'], 'bookings', 1, ['it' => 'Dipende dalla struttura.', 'en' => 'It depends.']);
        $this->platformFaq('Quanto vale un cofanetto?', 'smartbox');

        $this->get(route('admin.faqs'))
            ->assertOk()
            ->assertSeeInOrder(['Prenotazioni', 'Posso portare più di un animale?', 'Dipende dalla struttura.', 'Smartbox', 'Quanto vale un cofanetto?'])
            ->assertSee('1 domanda');

        // Solo gli argomenti che hanno domande diventano riquadri.
        $groups = Livewire::test(FaqIndex::class)->viewData('groups');
        $this->assertSame(['Prenotazioni', 'Smartbox'], array_column($groups, 'label'));
        $this->assertTrue($groups[0]['faqs'][0]['english']);
        $this->assertFalse($groups[1]['faqs'][0]['english']);
    }

    public function test_a_platform_question_is_created_in_both_languages(): void
    {
        Livewire::test(FaqIndex::class)
            ->call('create')
            ->assertSet('scope', 'platform')
            ->set('topic', 'payments')
            ->set('question.it', 'Quando ricevo i pagamenti?')
            ->set('answer.it', 'Ogni quindici giorni.')
            ->set('question.en', 'When do I get paid?')
            ->set('answer.en', 'Every fifteen days.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $faq = Faq::sole();
        $this->assertTrue($faq->isPlatform());
        $this->assertSame('payments', $faq->topic);
        $this->assertSame(1, $faq->position);
        $this->assertSame('Every fifteen days.', $faq->getTranslation('answer', 'en'));
    }

    public function test_the_italian_question_and_answer_are_required(): void
    {
        Livewire::test(FaqIndex::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors(['question.it', 'answer.it']);

        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_a_new_question_goes_to_the_end_of_its_topic(): void
    {
        $this->platformFaq('Prima', 'bookings', 1);
        $this->platformFaq('Seconda', 'bookings', 2);

        Livewire::test(FaqIndex::class)
            ->call('create')
            ->set('topic', 'bookings')
            ->set('question.it', 'Terza')
            ->set('answer.it', 'Risposta.')
            ->call('save');

        $this->assertSame(3, Faq::where('question->it', 'Terza')->sole()->position);
    }

    public function test_editing_a_question_updates_it_and_clears_an_emptied_translation(): void
    {
        $faq = $this->platformFaq('Come disdico?', 'bookings', 1, ['it' => 'Dal profilo.', 'en' => 'From your profile.']);

        Livewire::test(FaqIndex::class)
            ->call('edit', $faq->id)
            ->assertSet('question.it', 'Come disdico?')
            ->assertSet('answer.en', 'From your profile.')
            ->set('question.it', 'Come disdico una prenotazione?')
            ->set('answer.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $faq->refresh();
        $this->assertSame('Come disdico una prenotazione?', $faq->getTranslation('question', 'it'));
        $this->assertSame('', (string) $faq->getTranslation('answer', 'en', false));
        // Sul sito l'inglese ripiega sull'italiano.
        app()->setLocale('en');
        $this->assertSame('Dal profilo.', $faq->answer);
    }

    public function test_dragging_reorders_within_the_topic(): void
    {
        $first = $this->platformFaq('Prima', 'bookings', 1);
        $second = $this->platformFaq('Seconda', 'bookings', 2);
        $third = $this->platformFaq('Terza', 'bookings', 3);
        $other = $this->platformFaq('Altro argomento', 'smartbox', 1);

        Livewire::test(FaqIndex::class)->call('sort', $third->id, 0);

        $this->assertSame([$third->id, $first->id, $second->id], Faq::where('topic', 'bookings')->orderBy('position')->pluck('id')->all());
        $this->assertSame(1, $other->refresh()->position);
    }

    public function test_deleting_asks_first_and_closes_the_gap(): void
    {
        $first = $this->platformFaq('Prima', 'bookings', 1);
        $second = $this->platformFaq('Seconda', 'bookings', 2);

        Livewire::test(FaqIndex::class)
            ->call('askDelete', $first->id)
            ->assertSee('“Prima” sparirà dalla pagina di assistenza.')
            ->call('confirmDelete');

        $this->assertModelMissing($first);
        $this->assertSame(1, $second->refresh()->position);
    }

    public function test_a_question_is_attached_to_a_structure_and_shows_on_its_page(): void
    {
        $structure = Structure::factory()->create(['name' => ['it' => 'Hotel Brescia']]);

        $component = Livewire::test(FaqIndex::class)
            ->set('tab', 'products')
            ->call('create')
            ->assertSet('scope', 'product');

        $this->assertArrayHasKey('structure:'.$structure->id, $component->viewData('productOptions'));

        $component
            ->set('product', 'structure:'.$structure->id)
            ->set('question.it', 'Il cane può stare in camera?')
            ->set('answer.it', 'Sì, fino a 20 kg.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('tab', 'products')
            ->assertSee('Hotel Brescia')
            ->assertSee('Il cane può stare in camera?');

        $faq = Faq::sole();
        $this->assertSame('structure', $faq->faqable_type);
        $this->assertSame($structure->id, $faq->faqable_id);
        $this->assertNull($faq->topic);
        $this->assertSame(['Il cane può stare in camera?'], $structure->faqs()->get()->map(fn (Faq $f) => $f->question)->all());
    }

    public function test_a_question_can_move_from_a_product_to_the_help_page(): void
    {
        $event = Event::factory()->create();
        $faq = Faq::factory()->create([
            'faqable_type' => 'event',
            'faqable_id' => $event->id,
            'question' => ['it' => 'Serve la prenotazione?'],
            'answer' => ['it' => 'Sì.'],
            'position' => 4,
        ]);
        $this->platformFaq('Già presente', 'bookings', 1);

        Livewire::test(FaqIndex::class)
            ->call('edit', $faq->id)
            ->assertSet('product', 'event:'.$event->id)
            ->set('scope', 'platform')
            ->set('topic', 'bookings')
            ->call('save')
            ->assertHasNoErrors();

        $faq->refresh();
        $this->assertTrue($faq->isPlatform());
        $this->assertSame('bookings', $faq->topic);
        $this->assertSame(2, $faq->position);
    }

    public function test_an_unknown_product_is_refused(): void
    {
        Livewire::test(FaqIndex::class)
            ->call('create')
            ->set('scope', 'product')
            ->set('product', 'structure:999')
            ->set('question.it', 'Domanda?')
            ->set('answer.it', 'Risposta.')
            ->call('save')
            ->assertHasErrors('product')
            // In italiano: lang/it/validation.php non copre `in`, e il ripiego sarebbe l'inglese.
            ->assertSee('Il valore scelto per scheda non è fra quelli possibili.');

        $this->assertDatabaseCount('faqs', 0);
    }
}
