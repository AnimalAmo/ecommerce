<?php

namespace App\Livewire\Partner;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Statistiche dashboard (placeholder XD): etichetta, valore, variazione %,
     * e se la variazione è positiva (verde) o negativa (rossa).
     */
    public array $stats = [
        ['label' => 'partner.dashboard.stat_sold', 'value' => '112', 'delta' => '2.9%', 'positive' => true],
        ['label' => 'partner.dashboard.stat_cancelled', 'value' => '21', 'delta' => '0.7%', 'positive' => false],
        ['label' => 'partner.dashboard.stat_saved', 'value' => '236', 'delta' => '4.2%', 'positive' => true],
    ];

    /**
     * Il nome del saluto si legge a ogni render da chi è loggato, non da una
     * proprietà pubblica: la rotta è dietro `auth`+`partner`, quindi l'utente
     * c'è sempre, e così il valore non viaggia nel payload Livewire (dove il
     * client potrebbe riscriverlo).
     */
    public function render()
    {
        return view('livewire.partner.dashboard', [
            'partnerName' => Auth::user()->first_name,
        ])->title(__('partner.dashboard.title'));
    }
}
