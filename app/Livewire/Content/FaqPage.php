<?php

namespace App\Livewire\Content;

use App\Services\Content\FaqService;
use Livewire\Component;

/**
 * Pagina pubblica delle domande frequenti: le FAQ di piattaforma scritte dal
 * pannello, per argomento, nella lingua del sito (con l'italiano come rete).
 */
class FaqPage extends Component
{
    public function render(FaqService $faqs)
    {
        return view('livewire.content.faq-page', [
            'groups' => array_filter($faqs->platformGroups(), fn ($group) => $group->isNotEmpty()),
        ])->title(__('faq.page_title'));
    }
}
