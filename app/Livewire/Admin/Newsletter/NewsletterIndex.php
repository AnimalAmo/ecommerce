<?php

namespace App\Livewire\Admin\Newsletter;

use App\Models\Newsletter\NewsletterSubscriber;
use App\Services\Admin\Newsletter\NewsletterAdmin;
use App\Services\Newsletter\SubscriptionService;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Schermata Newsletter: numeri, iscritti con la prova del consenso (filtri ed
 * esportazione), invii, e la conferma di cortesia ai contatti della vecchia
 * casella di registrazione.
 */
class NewsletterIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'stato', except: '')]
    public string $status = '';

    #[Url(as: 'origine', except: '')]
    public string $source = '';

    #[Url(as: 'lingua', except: '')]
    public string $locale = '';

    /** Iscritto di cui si guarda la prova o che si sta disiscrivendo. */
    #[Locked]
    public ?int $selectedId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'source', 'locale'], true)) {
            $this->resetPage();
        }
    }

    /** @return array{search: string, status: string, source: string, locale: string} */
    public function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'source' => $this->source,
            'locale' => $this->locale,
        ];
    }

    public function showProof(int $id): void
    {
        $this->selectedId = NewsletterSubscriber::findOrFail($id)->getKey();

        Flux::modal('newsletter-proof')->show();
    }

    /**
     * Chiusura delle modali. Un metodo e non `$set('selectedId', null)`: la
     * proprietà è Locked, e Livewire rifiuta qualunque scrittura dal browser.
     */
    public function closeModal(): void
    {
        $this->selectedId = null;
    }

    public function askUnsubscribe(int $id): void
    {
        $this->selectedId = NewsletterSubscriber::findOrFail($id)->getKey();

        Flux::modal('newsletter-unsubscribe')->show();
    }

    public function unsubscribe(SubscriptionService $subscriptions): void
    {
        $subscriber = NewsletterSubscriber::find($this->selectedId);

        if ($subscriber !== null) {
            $subscriptions->unsubscribe($subscriber);
            Flux::toast(text: __('admin-newsletter.unsubscribe_modal.done', ['email' => $subscriber->email]), variant: 'success');
        }

        $this->selectedId = null;
        Flux::modal('newsletter-unsubscribe')->close();
    }

    public function sendLegacyConfirmations(SubscriptionService $subscriptions): void
    {
        $subscriptions->importLegacyFlags();
        $sent = $subscriptions->sendLegacyConfirmations();

        Flux::modal('newsletter-legacy')->close();
        Flux::toast(text: trans_choice('admin-newsletter.legacy.done', $sent, ['count' => $sent]), variant: 'success');
    }

    public function render(NewsletterAdmin $admin, SubscriptionService $subscriptions)
    {
        $page = $admin->paginate($this->filters());

        return view('livewire.admin.newsletter.index', [
            'admin' => $admin,
            'page' => $page,
            'totals' => $admin->totals(),
            'lastSent' => $admin->lastSent(),
            'campaigns' => $admin->campaigns(),
            'legacyCount' => NewsletterSubscriber::legacyNeverContacted()->count() + $subscriptions->legacyFlagsToImport(),
            'selected' => $this->selectedId !== null ? NewsletterSubscriber::with('user')->find($this->selectedId) : null,
            'exportUrl' => route('admin.newsletter.export', array_filter($this->filters())),
            'hasFilters' => array_filter($this->filters()) !== [],
        ])
            ->layout('layouts::admin')
            ->title(__('admin-newsletter.title'));
    }
}
