<?php

namespace App\Livewire\Admin;

use App\Services\Admin\Catalog\CatalogPresenter;
use App\Services\Admin\Dashboard\DashboardOverview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;

/** Home del pannello: da fare, numeri del sito, ultime schede, arrivi dal sito. */
class Home extends Component
{
    public function render(DashboardOverview $overview, CatalogPresenter $presenter)
    {
        $greeting = __('admin-dashboard.home.greeting.'.$overview->greeting());

        return view('livewire.admin.home', [
            'heading' => trim($greeting.' '.auth()->user()?->first_name),
            'today' => Str::ucfirst($overview->now()->locale(app()->getLocale())->isoFormat('dddd D MMMM')),
            'todo' => $overview->todo(),
            'catalog' => $overview->catalogCounts(),
            'subscribers' => $overview->subscribers(),
            'sales' => $overview->monthSales(),
            'onSite' => $overview->monthOnSiteBookings(),
            'partners' => $overview->partners(),
            'latest' => $overview->latestListings()->map(fn (Model $item): array => $presenter->row($item) + [
                'when' => $item->created_at?->locale(app()->getLocale())->diffForHumans(),
            ]),
            'inbox' => $overview->inbox(),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-dashboard.home.title'));
    }
}
