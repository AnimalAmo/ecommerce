<?php

namespace App\Livewire\Admin\Content;

use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use App\Services\Content\CommunityModerationService;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Community / Animal Network (design: is_community): segnalati, tutti i post,
 * rimossi. Lascia pubblicato, rimuovi (nascondi), ripristina, elimina; le
 * risposte di un post si aprono in una modale e si nascondono o eliminano una
 * per una.
 */
class CommunityIndex extends Component
{
    use WithPagination;

    #[Url(except: CommunityModerationService::FLAGGED)]
    public string $tab = CommunityModerationService::FLAGGED;

    /** @var array{action: string, id: int}|null post o risposta in attesa di conferma */
    public ?array $confirming = null;

    /** Post di cui è aperta la modale delle risposte. */
    public ?int $repliesOf = null;

    public function mount(): void
    {
        if (! in_array($this->tab, CommunityModerationService::TABS, true)) {
            $this->tab = CommunityModerationService::FLAGGED;
        }
    }

    public function updatedTab(): void
    {
        $this->mount();
        $this->resetPage();
    }

    public function keep(int $id, CommunityModerationService $moderation): void
    {
        $moderation->keep(CommunityPost::findOrFail($id));

        Flux::toast(text: __('admin-content.community.kept'), variant: 'success');
    }

    public function restore(int $id, CommunityModerationService $moderation): void
    {
        $moderation->restore(CommunityPost::findOrFail($id));

        Flux::toast(text: __('admin-content.community.restored'), variant: 'success');
    }

    /** Rimuovi ed Elimina passano dalla modale di conferma. */
    public function ask(string $action, int $id): void
    {
        abort_unless(in_array($action, ['hide', 'delete', 'delete_reply'], true), 404);

        $action === 'delete_reply' ? CommunityPostReply::findOrFail($id) : CommunityPost::findOrFail($id);

        $this->confirming = ['action' => $action, 'id' => $id];

        Flux::modal('community-confirm')->show();
    }

    public function confirm(CommunityModerationService $moderation): void
    {
        $confirming = $this->confirming;

        $this->confirming = null;
        Flux::modal('community-confirm')->close();

        if ($confirming === null) {
            return;
        }

        $message = match ($confirming['action']) {
            'hide' => $this->run(fn () => $moderation->hide(CommunityPost::findOrFail($confirming['id'])), 'hidden'),
            'delete' => $this->run(fn () => $moderation->delete(CommunityPost::findOrFail($confirming['id'])), 'deleted'),
            'delete_reply' => $this->run(fn () => $moderation->deleteReply(CommunityPostReply::findOrFail($confirming['id'])), 'reply_deleted'),
            default => null,
        };

        if ($message !== null) {
            Flux::toast(text: $message, variant: 'success');
        }
    }

    public function openReplies(int $id): void
    {
        $this->repliesOf = CommunityPost::findOrFail($id)->id;

        Flux::modal('community-replies')->show();
    }

    public function hideReply(int $id, CommunityModerationService $moderation): void
    {
        $moderation->hideReply(CommunityPostReply::findOrFail($id));

        Flux::toast(text: __('admin-content.community.reply_hidden'), variant: 'success');
    }

    public function restoreReply(int $id, CommunityModerationService $moderation): void
    {
        $moderation->restoreReply(CommunityPostReply::findOrFail($id));

        Flux::toast(text: __('admin-content.community.reply_restored'), variant: 'success');
    }

    public function render(CommunityModerationService $moderation)
    {
        $counts = $moderation->counts();
        $repliesPost = $this->repliesOf === null ? null : CommunityPost::find($this->repliesOf);

        return view('livewire.admin.content.community-index', [
            'counts' => $counts,
            'page' => $moderation->posts($this->tab, $this->getPage()),
            'repliesPost' => $repliesPost,
            'replies' => $repliesPost === null ? collect() : $moderation->replies($repliesPost),
            'confirmText' => $this->confirmText(),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-content.community.title'));
    }

    private function run(callable $action, string $messageKey): string
    {
        $action();

        return __('admin-content.community.'.$messageKey);
    }

    /** @return array{title: string, body: string, button: string}|null */
    private function confirmText(): ?array
    {
        if ($this->confirming === null) {
            return null;
        }

        $id = $this->confirming['id'];

        return match ($this->confirming['action']) {
            'hide' => [
                'title' => __('admin-content.community.hide_title'),
                'body' => __('admin-content.community.hide_body', ['author' => CommunityPost::find($id)?->author_name]),
                'button' => __('admin-content.community.hide'),
            ],
            'delete' => [
                'title' => __('admin-content.community.delete_title'),
                'body' => __('admin-content.community.delete_body', ['author' => CommunityPost::find($id)?->author_name]),
                'button' => __('admin-content.community.delete'),
            ],
            default => [
                'title' => __('admin-content.community.reply_delete_title'),
                'body' => __('admin-content.community.reply_delete_body', ['author' => CommunityPostReply::find($id)?->author_name]),
                'button' => __('admin-content.community.delete'),
            ],
        };
    }
}
