<?php

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHoliday;
use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\HomePage;
use App\Livewire\Catalog\Smartbox;
use App\Livewire\Catalog\SmartboxDetail;
use App\Livewire\Commerce\Cart;
use App\Livewire\Commerce\Checkout;
use App\Livewire\Commerce\Favorites;
use App\Livewire\Content\AboutUs;
use App\Livewire\Content\Community;
use App\Livewire\Content\News;
use App\Livewire\Content\NewsDetail;
use App\Livewire\Partner\WorkWithUs;
use App\Livewire\Partner\WorkWithUsThanks;
use App\Livewire\Profile\Profile;
use App\Livewire\Profile\ProfileEvents;
use App\Livewire\Profile\ProfileOrders;
use App\Livewire\Profile\ProfileOrderSummary;
use App\Livewire\Profile\ProfilePayment;
use App\Livewire\Profile\ProfileSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/animal-holiday', AnimalHoliday::class)->name('holiday');
Route::get('/animal-holiday/{region}', AnimalHolidayRegion::class)->name('holiday.region');
Route::get('/animal-holiday/{region}/servizi/{service}', AnimalHolidayService::class)->name('holiday.service');
Route::get('/animal-holiday/{region}/{structure}', AnimalHolidayStructure::class)->name('holiday.structure');
Route::get('/eventi', Events::class)->name('eventi');
Route::get('/eventi/attivita/{activity}', ActivityDetail::class)->name('eventi.activity');
Route::get('/eventi/{event}', EventDetail::class)->name('eventi.detail');
Route::get('/smartbox', Smartbox::class)->name('smartbox');
Route::get('/smartbox/{box}', SmartboxDetail::class)->name('smartbox.detail');
Route::get('/chi-siamo', AboutUs::class)->name('about');
Route::get('/community', Community::class)->name('community');
Route::get('/preferiti', Favorites::class)->name('preferiti');
Route::get('/carrello', Cart::class)->name('carrello');
Route::get('/checkout', Checkout::class)->name('checkout');
Route::middleware('auth')->group(function () {
    Route::get('/profilo', Profile::class)->name('profilo');
    Route::get('/profilo/metodo-pagamento', ProfilePayment::class)->name('profilo.pagamento');
    Route::get('/profilo/sicurezza', ProfileSecurity::class)->name('profilo.sicurezza');
    Route::get('/profilo/i-miei-ordini', ProfileOrders::class)->name('profilo.ordini');
    Route::get('/profilo/i-miei-ordini/{order}', ProfileOrderSummary::class)->name('profilo.ordini.riepilogo');
    Route::get('/profilo/eventi-a-cui-partecipo', ProfileEvents::class)->name('profilo.eventi');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');
Route::get('/news', News::class)->name('news');
Route::get('/news/{article}', NewsDetail::class)->name('news.detail');
Route::get('/lavora-con-noi', WorkWithUs::class)->name('work-with-us');
Route::get('/lavora-con-noi/grazie', WorkWithUsThanks::class)->name('work-with-us.thanks');
