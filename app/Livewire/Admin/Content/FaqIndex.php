<?php

namespace App\Livewire\Admin\Content;

use App\Models\Faq\Faq;
use App\Services\Content\FaqService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Domande frequenti (design: is_faq): quelle della pagina di assistenza per
 * argomento e, nella seconda scheda, quelle agganciate alle singole schede
 * del catalogo. Creazione e modifica in una modale, ordine per trascinamento
 * (wire:sort) dentro il proprio gruppo.
 */
class FaqIndex extends Component
{
    public const TABS = ['platform', 'products'];

    #[Url(as: 'vista', except: 'platform')]
    public string $tab = 'platform';

    /** Modale di scrittura: null = nuova domanda. */
    public ?int $editingId = null;

    public bool $editorOpen = false;

    public string $scope = 'platform';

    public string $topic = 'bookings';

    public string $product = '';

    /** @var array<string, string> */
    public array $question = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $answer = ['it' => '', 'en' => ''];

    public ?int $deletingId = null;

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'platform';
        }
    }

    public function updatedTab(): void
    {
        $this->mount();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->scope = $this->tab === 'products' ? 'product' : 'platform';
        $this->openEditor();
    }

    public function edit(int $id): void
    {
        $faq = Faq::findOrFail($id);

        $this->resetForm();
        $this->editingId = $faq->id;
        $this->scope = $faq->isPlatform() ? 'platform' : 'product';
        $this->topic = $faq->topic ?? 'bookings';
        $this->product = $faq->isPlatform() ? '' : $faq->faqable_type.':'.$faq->faqable_id;

        foreach (['it', 'en'] as $locale) {
            $this->question[$locale] = (string) $faq->getTranslation('question', $locale, false);
            $this->answer[$locale] = (string) $faq->getTranslation('answer', $locale, false);
        }

        $this->openEditor();
    }

    public function save(FaqService $faqs): void
    {
        $this->validate($this->rules($faqs), __('admin-content.validation'), $this->attributes());

        $faqs->save($this->editingId ? Faq::findOrFail($this->editingId) : null, [
            'topic' => $this->scope === 'platform' ? $this->topic : null,
            'product' => $this->scope === 'product' ? $this->product : null,
            'question' => $this->question,
            'answer' => $this->answer,
        ]);

        // La domanda compare nella scheda del pannello in cui è finita.
        $this->tab = $this->scope === 'product' ? 'products' : 'platform';

        Flux::modal('faq-editor')->close();
        $this->resetForm();

        Flux::toast(text: __('admin-content.faq.saved'), variant: 'success');
    }

    public function closeEditor(): void
    {
        $this->resetForm();
    }

    public function askDelete(int $id): void
    {
        $this->deletingId = Faq::findOrFail($id)->id;

        Flux::modal('faq-delete')->show();
    }

    public function confirmDelete(FaqService $faqs): void
    {
        $faq = $this->deletingId === null ? null : Faq::find($this->deletingId);

        $this->deletingId = null;
        Flux::modal('faq-delete')->close();

        if ($faq === null) {
            return;
        }

        $faqs->delete($faq);

        Flux::toast(text: __('admin-content.faq.deleted'), variant: 'success');
    }

    /** wire:sort: la domanda trascinata e la sua nuova posizione (0 = prima) nel gruppo. */
    public function sort(int $item, int $position, FaqService $faqs): void
    {
        $faq = Faq::find($item);

        if ($faq !== null) {
            $faqs->move($faq, $position);
        }
    }

    public function render(FaqService $faqs)
    {
        $deleting = $this->deletingId === null ? null : Faq::find($this->deletingId);

        return view('livewire.admin.content.faq-index', [
            'groups' => $this->groups($faqs),
            // La lista delle schede serve solo a modale aperta, e solo per le domande di scheda.
            'productOptions' => $this->editorOpen && $this->scope === 'product' ? $faqs->productOptions() : [],
            'deleting' => $deleting,
            'deletingBody' => $deleting === null ? '' : $this->deleteBody($deleting, $faqs),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-content.faq.title'));
    }

    /**
     * I riquadri della scheda aperta, nella stessa forma per argomenti e schede.
     *
     * @return list<array{key: string, label: string, family: string|null, url: string|null, faqs: list<array{id: int, question: string, answer: string, english: bool}>}>
     */
    private function groups(FaqService $faqs): array
    {
        $groups = $this->tab === 'products'
            ? $faqs->productGroups()->all()
            : collect($faqs->platformGroups())
                ->filter(fn ($group) => $group->isNotEmpty())
                ->map(fn ($group, string $topic): array => [
                    'key' => 'topic-'.$topic,
                    'label' => __('faq.topics.'.$topic),
                    'family' => null,
                    'url' => null,
                    'faqs' => $group,
                ])
                ->values()
                ->all();

        return array_map(fn (array $group): array => [
            ...$group,
            'faqs' => $group['faqs']->map(fn (Faq $faq): array => [
                'id' => $faq->id,
                'question' => (string) $faq->getTranslation('question', 'it'),
                'answer' => (string) $faq->getTranslation('answer', 'it'),
                'english' => trim((string) $faq->getTranslation('question', 'en', false)) !== ''
                    && trim((string) $faq->getTranslation('answer', 'en', false)) !== '',
            ])->all(),
        ], $groups);
    }

    private function openEditor(): void
    {
        $this->editorOpen = true;
        $this->resetErrorBag();

        Flux::modal('faq-editor')->show();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'editorOpen', 'scope', 'topic', 'product', 'question', 'answer']);
        $this->resetErrorBag();
    }

    private function deleteBody(Faq $faq, FaqService $faqs): string
    {
        $question = (string) $faq->getTranslation('question', 'it');

        if ($faq->isPlatform()) {
            return __('admin-content.faq.delete_body_platform', ['question' => $question]);
        }

        $class = FaqService::PRODUCT_TYPES[$faq->faqable_type] ?? null;
        $product = $class ? $class::query()->withoutGlobalScopes()->find($faq->faqable_id) : null;

        return __('admin-content.faq.delete_body_product', [
            'question' => $question,
            'product' => $product ? $faqs->productLabel($product) : '—',
        ]);
    }

    /** @return array<string, mixed> */
    private function rules(FaqService $faqs): array
    {
        return [
            'scope' => ['required', Rule::in(['platform', 'product'])],
            // Si valida solo il campo della destinazione scelta: l'altro può restare
            // col valore di prima (una domanda che passa da una scheda alla pagina).
            'topic' => $this->scope === 'platform' ? ['required', Rule::in(Faq::TOPICS)] : ['nullable'],
            'product' => $this->scope === 'product' ? ['required', Rule::in(array_keys($faqs->productOptions()))] : ['nullable'],
            'question.it' => ['required', 'string', 'max:500'],
            'question.en' => ['nullable', 'string', 'max:500'],
            'answer.it' => ['required', 'string', 'max:5000'],
            'answer.en' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'topic' => __('admin-content.faq.fields.topic'),
            'product' => __('admin-content.faq.fields.product'),
            'question.it' => __('admin-content.faq.fields.question_it'),
            'question.en' => __('admin-content.faq.fields.question_en'),
            'answer.it' => __('admin-content.faq.fields.answer_it'),
            'answer.en' => __('admin-content.faq.fields.answer_en'),
        ];
    }
}
