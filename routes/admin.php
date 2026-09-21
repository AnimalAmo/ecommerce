<?php

use App\Http\Controllers\Admin\CatalogExportController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\NewsletterExportController;
use App\Http\Controllers\Admin\PayoutExportController;
use App\Http\Controllers\Admin\UserExportController;
use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Livewire\Admin\Catalog\Approvals;
use App\Livewire\Admin\Catalog\CatalogIndex;
use App\Livewire\Admin\Catalog\CatalogShow;
use App\Livewire\Admin\Content\ArticleEdit;
use App\Livewire\Admin\Content\ArticleIndex;
use App\Livewire\Admin\Content\CommunityIndex;
use App\Livewire\Admin\Content\FaqIndex;
use App\Livewire\Admin\Content\PageEdit;
use App\Livewire\Admin\Content\PageIndex;
use App\Livewire\Admin\Content\SitePageEdit;
use App\Livewire\Admin\Home;
use App\Livewire\Admin\Money\Payouts;
use App\Livewire\Admin\Newsletter\CampaignEdit;
use App\Livewire\Admin\Newsletter\NewsletterIndex;
use App\Livewire\Admin\People\Inbox;
use App\Livewire\Admin\People\UserIndex;
use App\Livewire\Admin\People\UserShow;
use App\Livewire\Admin\Reviews\ReviewIndex;
use App\Livewire\Admin\Search;
use Illuminate\Support\Facades\Route;

/*
| Pannello di amministrazione (prefisso /admin, nomi admin.*), registrato in
| bootstrap/app.php fuori dal gruppo localizzato: solo italiano.
*/

// Accesso: pubblico, il token nell'URL di reset è la sola credenziale.
Route::get('accesso', Login::class)->name('login');
Route::get('password-dimenticata', ForgotPassword::class)->name('password.request');
Route::get('reimposta-password/{token}', ResetPassword::class)->name('password.reset');

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::post('esci', LogoutController::class)->name('logout');

    Route::get('/', Home::class)->name('home');
    Route::get('cerca', Search::class)->name('search');

    // Catalogo
    Route::get('catalogo', CatalogIndex::class)->name('catalog.index');
    Route::get('catalogo/esporta', CatalogExportController::class)->name('catalog.export');
    Route::get('catalogo/{type}/{id}', CatalogShow::class)
        ->whereIn('type', ['structure', 'event', 'smartbox_package'])
        ->whereNumber('id')
        ->name('catalog.show');
    Route::get('da-approvare', Approvals::class)->name('approvals');
    Route::get('recensioni', ReviewIndex::class)->name('reviews');

    // Contenuti
    Route::get('pagine', PageIndex::class)->name('pages.index');
    Route::get('pagine/nuova', PageEdit::class)->name('pages.create');
    Route::get('pagine/sito/{section}', SitePageEdit::class)->name('pages.site');
    Route::get('pagine/{page}', PageEdit::class)->whereNumber('page')->name('pages.edit');
    Route::get('animal-times', ArticleIndex::class)->name('articles.index');
    Route::get('animal-times/nuovo', ArticleEdit::class)->name('articles.create');
    Route::get('animal-times/{article}', ArticleEdit::class)->whereNumber('article')->name('articles.edit');
    Route::get('domande-frequenti', FaqIndex::class)->name('faqs');
    Route::get('community', CommunityIndex::class)->name('community');

    // Persone
    Route::get('iscritti', UserIndex::class)->name('users.index');
    Route::get('iscritti/esporta', UserExportController::class)->name('users.export');
    Route::get('iscritti/{user}', UserShow::class)->whereNumber('user')->name('users.show');
    Route::get('contatti', Inbox::class)->name('inbox');
    Route::get('newsletter', NewsletterIndex::class)->name('newsletter.index');
    Route::get('newsletter/esporta', NewsletterExportController::class)->name('newsletter.export');
    Route::get('newsletter/nuova', CampaignEdit::class)->name('newsletter.create');
    Route::get('newsletter/{campaign}', CampaignEdit::class)->whereNumber('campaign')->name('newsletter.edit');

    // Denaro
    Route::get('incassi', Payouts::class)->name('payouts');
    Route::get('incassi/esporta', PayoutExportController::class)->name('payouts.export');
});
