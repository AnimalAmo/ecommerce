<?php

namespace App\Livewire\Admin\People;

use App\Models\ContactMessage\ContactMessage;
use App\Models\Partner\PartnerApplication;
use App\Services\Admin\People\InboxService;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/** Contatti e candidature: elenco a sinistra, dettaglio a destra. */
class Inbox extends Component
{
    use WithPagination;

    #[Url(except: 'messages')]
    public string $tab = 'messages';

    #[Url(except: 'open')]
    public string $filter = 'open';

    /** Elemento aperto nel dettaglio; null = il primo dell'elenco. */
    #[Url(as: 'id')]
    public ?int $selected = null;

    public function mount(): void
    {
        $this->normalize();
    }

    public function updatedTab(): void
    {
        $this->normalize();
        $this->selected = null;
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->normalize();
        $this->selected = null;
        $this->resetPage();
    }

    public function select(int $id): void
    {
        $this->selected = $id;
    }

    public function toggleHandled(InboxService $inbox): void
    {
        if ($item = $this->current($inbox)) {
            $inbox->setHandled($item, $item->handled_at === null);
            Flux::toast(text: __('admin-people.inbox.'.($item->handled_at !== null ? 'marked_handled' : 'reopened')), variant: 'success');
        }
    }

    public function toggleArchived(InboxService $inbox): void
    {
        if ($item = $this->current($inbox)) {
            $inbox->setArchived($item, $item->archived_at === null);
            Flux::toast(text: __('admin-people.inbox.'.($item->archived_at !== null ? 'archived_done' : 'unarchived_done')), variant: 'success');
        }
    }

    public function askDelete(): void
    {
        Flux::modal('inbox-delete')->show();
    }

    public function delete(InboxService $inbox): void
    {
        if ($item = $this->current($inbox)) {
            $inbox->delete($item);
            $this->selected = null;
            Flux::toast(text: __('admin-people.inbox.deleted'), variant: 'success');
        }

        Flux::modal('inbox-delete')->close();
    }

    public function invite(InboxService $inbox): void
    {
        $item = $this->current($inbox);

        if (! $item instanceof PartnerApplication) {
            return;
        }

        try {
            $inbox->invite($item);
        } catch (RuntimeException $e) {
            Flux::toast(text: $e->getMessage(), variant: 'danger');

            return;
        }

        Flux::toast(text: __('admin-people.inbox.invited', ['email' => $item->email]), variant: 'success');
    }

    public function render(InboxService $inbox)
    {
        $items = $inbox->query($this->tab, $this->filter)->paginate(20);
        $current = $this->current($inbox) ?? $items->first();

        // Senza scelta esplicita si apre il primo: le azioni lavorano su $selected.
        $this->selected = $current?->id;

        return view('livewire.admin.people.inbox', [
            'items' => $items,
            'current' => $current,
            'counts' => $inbox->openCounts(),
            'isApplications' => $this->tab === 'applications',
        ])
            ->layout('layouts::admin')
            ->title(__('admin-people.inbox.title'));
    }

    private function current(InboxService $inbox): ContactMessage|PartnerApplication|null
    {
        return $this->selected !== null ? $inbox->find($this->tab, $this->selected) : null;
    }

    private function normalize(): void
    {
        if (! in_array($this->tab, InboxService::TABS, true)) {
            $this->tab = 'messages';
        }

        if (! in_array($this->filter, InboxService::FILTERS, true)) {
            $this->filter = 'open';
        }
    }
}
