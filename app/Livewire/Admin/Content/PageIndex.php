<?php

namespace App\Livewire\Admin\Content;

use App\Services\Content\PageService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Elenco unico delle pagine (design: is_legali): sezioni del sito riscrivibili
 * (opzione B), pagine legali e pagine libere. Sono poche decine di righe: si
 * filtrano in memoria, senza paginazione.
 */
class PageIndex extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'tipo', except: '')]
    public string $kind = '';

    #[Url(as: 'stato', except: '')]
    public string $state = '';

    public function render(PageService $pages)
    {
        $rows = $pages->filter($pages->rows(), $this->search, $this->kind, $this->state);

        return view('livewire.admin.content.page-index', [
            'rows' => $rows,
            'kindTones' => [PageService::KIND_SITE => 'info', 'legal' => 'muted', 'free' => 'purple'],
            'stateTones' => [PageService::ORIGINAL => 'muted', PageService::REWRITTEN => 'success', PageService::MISSING => 'warning'],
        ])
            ->layout('layouts::admin')
            ->title(__('admin-content.pages.title'));
    }
}
