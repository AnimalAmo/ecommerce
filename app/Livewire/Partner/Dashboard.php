<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class Dashboard extends Component
{
    /** Nome partner (placeholder finché non esiste l'auth partner). */
    public string $partnerName = 'Susanna';

    /**
     * Statistiche dashboard (placeholder XD): etichetta, valore, variazione %,
     * e se la variazione è positiva (verde) o negativa (rossa).
     */
    public array $stats = [
        ['label' => 'partner.dashboard.stat_sold', 'value' => '112', 'delta' => '2.9%', 'positive' => true],
        ['label' => 'partner.dashboard.stat_cancelled', 'value' => '21', 'delta' => '0.7%', 'positive' => false],
        ['label' => 'partner.dashboard.stat_saved', 'value' => '236', 'delta' => '4.2%', 'positive' => true],
    ];

    public function render()
    {
        return view('livewire.partner.dashboard')
            ->title(__('partner.dashboard.title'));
    }
}
