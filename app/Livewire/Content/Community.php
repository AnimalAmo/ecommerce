<?php

namespace App\Livewire\Content;

use App\Services\CommunityService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class Community extends Component
{
    /** Tab attiva ("tutti" | "miei"); deep-linkabile via ?tab=miei (stesso pattern di EventDetail). */
    #[Url(except: 'tutti')]
    public string $tab = 'tutti';

    public const TABS = ['tutti', 'miei'];

    /** Ricerca live (toolbar): filtra i post per titolo/body. */
    public string $search = '';

    /** Testo del composer nella hero. */
    public string $composerBody = '';

    /** Tag selezionati nel composer (flux:checkbox.group variant pills, multi-selezione; default XD "Avventura"). */
    public array $composerTags = ['Avventura'];

    /**
     * Filtri tipologia attivi (chips sotto la toolbar, artboard "Community – filtro").
     * Su desktop ci arrivano da addFilter/removeFilter, su mobile dal wire:model del
     * pannello "Filtri community": va quindi ripulito anche in ingresso.
     */
    public array $activeFilters = [];

    /** Bozze di risposta per id post (input pill in fondo a ogni card). */
    public array $replyDrafts = [];

    /**
     * Sweet alert "Domanda condivisa con successo!" dopo la pubblicazione (XD app
     * "Community – sweet alert"). È un overlay, non un <dialog>: il pannello è solo
     * mobile e showModal() da desktop bloccherebbe la pagina (vedi CLAUDE.md).
     */
    public bool $shared = false;

    public function mount(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'tutti';
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
        }
    }

    /** Pubblica dal composer. Solo utenti loggati (ospite → login). */
    public function publish(CommunityService $community): void
    {
        if (! Auth::check()) {
            Flux::modal('login')->show();

            return;
        }

        $body = trim($this->composerBody);

        if ($body === '') {
            return;
        }

        $community->publish(Auth::user(), $body, $this->composerTags);

        $this->composerBody = '';
        $this->composerTags = ['Avventura'];

        // Composer mobile: chiude il pannello a tutta pagina. close() su un <dialog> non
        // aperto è un no-op, quindi da desktop non ha effetti (a differenza di show()).
        Flux::modal('scrivi-domanda')->close();

        // Lo sweet alert copre solo il mobile: da desktop il post compare e basta.
        $this->shared = true;
    }

    /** X / tap fuori dallo sweet alert "Domanda condivisa con successo!". */
    public function dismissShared(): void
    {
        $this->shared = false;
    }

    /** Il pannello filtri mobile scrive direttamente l'array: tiene solo i tag noti. */
    public function updatedActiveFilters(): void
    {
        $this->activeFilters = array_values(array_intersect(CommunityService::TAGS, $this->activeFilters));
    }

    /** Selezione dal menu "Filtra tipologia" → aggiunge la chip filtro attivo. */
    public function addFilter(string $tag): void
    {
        if (in_array($tag, CommunityService::TAGS, true) && ! in_array($tag, $this->activeFilters, true)) {
            $this->activeFilters[] = $tag;
        }
    }

    /** La × sulla chip rimuove il filtro. */
    public function removeFilter(string $tag): void
    {
        $this->activeFilters = array_values(array_diff($this->activeFilters, [$tag]));
    }

    /** Risposta inline dalla card (solo desktop). Solo utenti loggati (ospite → login). */
    public function reply(int $postId, CommunityService $community): void
    {
        if (! Auth::check()) {
            Flux::modal('login')->show();

            return;
        }

        $body = trim($this->replyDrafts[$postId] ?? '');

        if ($body === '') {
            return;
        }

        $community->reply($postId, Auth::user(), $body);

        unset($this->replyDrafts[$postId]);
    }

    public function render(CommunityService $community)
    {
        $needle = trim($this->search);

        $visiblePosts = collect($community->posts(Auth::id()))
            ->when($this->tab === 'miei', fn ($posts) => $posts->filter(
                fn (array $post): bool => $post['mine'],
            ))
            ->when($needle !== '', fn ($posts) => $posts->filter(
                fn (array $post): bool => mb_stripos($post['title'], $needle) !== false
                    || mb_stripos($post['body'], $needle) !== false,
            ))
            ->when($this->activeFilters !== [], fn ($posts) => $posts->filter(
                fn (array $post): bool => in_array($post['tag'], $this->activeFilters, true),
            ))
            ->values()
            ->all();

        return view('livewire.content.community', [
            'visiblePosts' => $visiblePosts,
            'tags' => CommunityService::TAGS,
        ])->title(__('community.page_title'));
    }
}
