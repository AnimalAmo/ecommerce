<?php

namespace App\Livewire\Admin\Reviews;

use App\Models\Review\Review;
use App\Services\Admin\Reviews\ReviewModeration;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Recensioni: tre schede (in attesa, pubblicate, nascoste) con pubblica,
 * nascondi, ripristina ed elimina. La logica sta in ReviewModeration.
 */
class ReviewIndex extends Component
{
    use WithPagination;

    #[Url(except: Review::STATUS_PENDING)]
    public string $tab = Review::STATUS_PENDING;

    #[Url(except: '')]
    public string $q = '';

    /** Recensione nella modale "Elimina". */
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->normalize();
    }

    public function updatedTab(): void
    {
        $this->normalize();
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function publish(int $id, ReviewModeration $moderation): void
    {
        if ($review = $moderation->find($id)) {
            $moderation->publish($review);
            Flux::toast(text: __('admin-people.reviews.published_done'), variant: 'success');
        }
    }

    public function hide(int $id, ReviewModeration $moderation): void
    {
        if ($review = $moderation->find($id)) {
            $moderation->hide($review);
            Flux::toast(text: __('admin-people.reviews.hidden_done'), variant: 'success');
        }
    }

    public function askDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('review-delete')->show();
    }

    public function delete(ReviewModeration $moderation): void
    {
        if ($this->deletingId !== null && ($review = $moderation->find($this->deletingId))) {
            $moderation->delete($review);
            Flux::toast(text: __('admin-people.reviews.deleted'), variant: 'success');
        }

        $this->deletingId = null;
        Flux::modal('review-delete')->close();
    }

    public function render(ReviewModeration $moderation)
    {
        return view('livewire.admin.reviews.index', [
            'reviews' => $moderation->query($this->tab, $this->q)->paginate(20),
            'counts' => $moderation->counts(),
            'deleting' => $this->deletingId !== null ? $moderation->find($this->deletingId) : null,
        ])
            ->layout('layouts::admin')
            ->title(__('admin-people.reviews.title'));
    }

    private function normalize(): void
    {
        if (! in_array($this->tab, ReviewModeration::TABS, true)) {
            $this->tab = Review::STATUS_PENDING;
        }
    }
}
