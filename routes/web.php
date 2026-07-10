<?php

use App\Http\Controllers\LocaleSwitchController;
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
use App\Livewire\Partner\ActivityDescription as PartnerActivityDescription;
use App\Livewire\Partner\ActivityLocation as PartnerActivityLocation;
use App\Livewire\Partner\ActivityName as PartnerActivityName;
use App\Livewire\Partner\ActivityType as PartnerActivityType;
use App\Livewire\Partner\CreateService as PartnerCreateService;
use App\Livewire\Partner\Dashboard as PartnerDashboard;
use App\Livewire\Partner\HotelAnimalServices as PartnerHotelAnimalServices;
use App\Livewire\Partner\HotelCancellation as PartnerHotelCancellation;
use App\Livewire\Partner\HotelDescription as PartnerHotelDescription;
use App\Livewire\Partner\HotelLocation as PartnerHotelLocation;
use App\Livewire\Partner\HotelPayment as PartnerHotelPayment;
use App\Livewire\Partner\HotelPhotos as PartnerHotelPhotos;
use App\Livewire\Partner\HotelRooms as PartnerHotelRooms;
use App\Livewire\Partner\HotelSmartbox as PartnerHotelSmartbox;
use App\Livewire\Partner\HotelServices as PartnerHotelServices;
use App\Livewire\Partner\HotelTitle as PartnerHotelTitle;
use App\Livewire\Partner\PartnerRegisterStep1;
use App\Livewire\Partner\PartnerRegisterStep2;
use App\Livewire\Partner\StructureType as PartnerStructureType;
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
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

// Localized storefront routes: default locale (it) has no URL prefix,
// non-default locales (en) get a /{locale} prefix + translated slugs.
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localize', 'localizationRedirect'],
], function () {
    Route::get('/', HomePage::class)->name('home');
    Route::get(LaravelLocalization::transRoute('routes.holiday'), AnimalHoliday::class)->name('holiday');
    Route::get(LaravelLocalization::transRoute('routes.holiday.region'), AnimalHolidayRegion::class)->name('holiday.region');
    Route::get(LaravelLocalization::transRoute('routes.holiday.service'), AnimalHolidayService::class)->name('holiday.service');
    Route::get(LaravelLocalization::transRoute('routes.holiday.structure'), AnimalHolidayStructure::class)->name('holiday.structure');
    Route::get(LaravelLocalization::transRoute('routes.eventi'), Events::class)->name('eventi');
    Route::get(LaravelLocalization::transRoute('routes.eventi.activity'), ActivityDetail::class)->name('eventi.activity');
    Route::get(LaravelLocalization::transRoute('routes.eventi.detail'), EventDetail::class)->name('eventi.detail');
    Route::get(LaravelLocalization::transRoute('routes.smartbox'), Smartbox::class)->name('smartbox');
    Route::get(LaravelLocalization::transRoute('routes.smartbox.detail'), SmartboxDetail::class)->name('smartbox.detail');
    Route::get(LaravelLocalization::transRoute('routes.about'), AboutUs::class)->name('about');
    Route::get(LaravelLocalization::transRoute('routes.community'), Community::class)->name('community');
    Route::get(LaravelLocalization::transRoute('routes.preferiti'), Favorites::class)->name('preferiti');
    Route::get(LaravelLocalization::transRoute('routes.carrello'), Cart::class)->name('carrello');
    Route::get(LaravelLocalization::transRoute('routes.checkout'), Checkout::class)->name('checkout');

    Route::middleware('auth')->group(function () {
        Route::get(LaravelLocalization::transRoute('routes.profilo'), Profile::class)->name('profilo');
        Route::get(LaravelLocalization::transRoute('routes.profilo.pagamento'), ProfilePayment::class)->name('profilo.pagamento');
        Route::get(LaravelLocalization::transRoute('routes.profilo.sicurezza'), ProfileSecurity::class)->name('profilo.sicurezza');
        Route::get(LaravelLocalization::transRoute('routes.profilo.ordini'), ProfileOrders::class)->name('profilo.ordini');
        Route::get(LaravelLocalization::transRoute('routes.profilo.ordini.riepilogo'), ProfileOrderSummary::class)->name('profilo.ordini.riepilogo');
        Route::get(LaravelLocalization::transRoute('routes.profilo.eventi'), ProfileEvents::class)->name('profilo.eventi');
    });

    Route::get(LaravelLocalization::transRoute('routes.news'), News::class)->name('news');
    Route::get(LaravelLocalization::transRoute('routes.news.detail'), NewsDetail::class)->name('news.detail');
    Route::get(LaravelLocalization::transRoute('routes.work-with-us'), WorkWithUs::class)->name('work-with-us');
    Route::get(LaravelLocalization::transRoute('routes.work-with-us.thanks'), WorkWithUsThanks::class)->name('work-with-us.thanks');
    Route::get(LaravelLocalization::transRoute('routes.partner.register'), PartnerRegisterStep1::class)->name('partner.register');
    Route::get(LaravelLocalization::transRoute('routes.partner.register.step2'), PartnerRegisterStep2::class)->name('partner.register.step2');
    Route::get(LaravelLocalization::transRoute('routes.partner.dashboard'), PartnerDashboard::class)->name('partner.dashboard');
    Route::get(LaravelLocalization::transRoute('routes.partner.service.create'), PartnerCreateService::class)->name('partner.service.create');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.type'), PartnerStructureType::class)->name('partner.structure.type');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.type'), PartnerActivityType::class)->name('partner.activity.type');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.name'), PartnerActivityName::class)->name('partner.activity.name');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.location'), PartnerActivityLocation::class)->name('partner.activity.location');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.description'), PartnerActivityDescription::class)->name('partner.activity.description');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.title'), PartnerHotelTitle::class)->name('partner.structure.hotel.title');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.location'), PartnerHotelLocation::class)->name('partner.structure.hotel.location');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.description'), PartnerHotelDescription::class)->name('partner.structure.hotel.description');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.rooms'), PartnerHotelRooms::class)->name('partner.structure.hotel.rooms');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.cancellation'), PartnerHotelCancellation::class)->name('partner.structure.hotel.cancellation');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.services'), PartnerHotelServices::class)->name('partner.structure.hotel.services');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.animal-services'), PartnerHotelAnimalServices::class)->name('partner.structure.hotel.animal-services');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.smartbox'), PartnerHotelSmartbox::class)->name('partner.structure.hotel.smartbox');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.photos'), PartnerHotelPhotos::class)->name('partner.structure.hotel.photos');
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.payment'), PartnerHotelPayment::class)->name('partner.structure.hotel.payment');
});

// Non-localized routes (no language prefix).
Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

Route::get('locale/{locale}', LocaleSwitchController::class)->name('locale.switch');
