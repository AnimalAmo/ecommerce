<?php

namespace App\Livewire\Admin\Money;

use App\Services\Admin\Money\PayoutLedger;
use App\Services\Admin\Money\Period;
use App\Services\Admin\Money\StripeDashboardLinks;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Incassi: numeri del periodo, divisione per partner, bonifici. */
class Payouts extends Component
{
    /** '2026-09' o '12m'; normalizzato, così l'URL condiviso dice sempre il periodo vero. */
    #[Url]
    public string $period = '';

    public function mount(): void
    {
        $this->period = Period::fromKey($this->period)->key;
    }

    public function updatedPeriod(): void
    {
        $this->period = Period::fromKey($this->period)->key;
    }

    public function render(PayoutLedger $ledger, StripeDashboardLinks $stripe)
    {
        $period = Period::fromKey($this->period);

        return view('livewire.admin.money.payouts', [
            'current' => $period,
            'options' => $ledger->periodOptions($period),
            'totals' => $ledger->totals($period),
            'onSite' => $ledger->onSiteBookings($period),
            'partners' => $ledger->byPartner($period),
            'transfers' => $ledger->transfers($period),
            'stuck' => $ledger->stuck(),
            'stripe' => $stripe,
            'maxAttempts' => (int) config('commerce.payout.max_release_attempts'),
            'exportUrl' => route('admin.payouts.export', ['period' => $period->key]),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-money.title'));
    }
}
