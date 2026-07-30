<?php

use App\Http\Controllers\LocaleSwitchController;
use App\Livewire\Auth\ResetPassword;
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
use App\Livewire\Content\Contact;
use App\Livewire\Content\News;
use App\Livewire\Content\NewsDetail;
use App\Livewire\Content\PostDetail;
use App\Livewire\Partner\Activity\ActivityAnimalServices as PartnerActivityAnimalServices;
use App\Livewire\Partner\Activity\ActivityCancellation as PartnerActivityCancellation;
use App\Livewire\Partner\Activity\ActivityCost as PartnerActivityCost;
use App\Livewire\Partner\Activity\ActivityDescription as PartnerActivityDescription;
use App\Livewire\Partner\Activity\ActivityIncluded as PartnerActivityIncluded;
use App\Livewire\Partner\Activity\ActivityInfo as PartnerActivityInfo;
use App\Livewire\Partner\Activity\ActivityLocation as PartnerActivityLocation;
use App\Livewire\Partner\Activity\ActivityName as PartnerActivityName;
use App\Livewire\Partner\Activity\ActivityPhotos as PartnerActivityPhotos;
use App\Livewire\Partner\Activity\ActivityType as PartnerActivityType;
use App\Livewire\Partner\Bookings\PartnerBookingDetail;
use App\Livewire\Partner\Bookings\PartnerBookings;
use App\Livewire\Partner\CreateService as PartnerCreateService;
use App\Livewire\Partner\Dashboard as PartnerDashboard;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Livewire\Partner\MyServices\PartnerServiceDetail;
use App\Livewire\Partner\Profile\PartnerProfileInfo;
use App\Livewire\Partner\Profile\PartnerProfilePayment;
use App\Livewire\Partner\Profile\PartnerProfileSecurity;
use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Livewire\Partner\Registration\WorkWithUs;
use App\Livewire\Partner\Registration\WorkWithUsThanks;
use App\Livewire\Partner\Smartbox\SmartboxCancellation as PartnerSmartboxCancellation;
use App\Livewire\Partner\Smartbox\SmartboxDescription as PartnerSmartboxDescription;
use App\Livewire\Partner\Smartbox\SmartboxDuration as PartnerSmartboxDuration;
use App\Livewire\Partner\Smartbox\SmartboxIncluded as PartnerSmartboxIncluded;
use App\Livewire\Partner\Smartbox\SmartboxIncludedAnimals as PartnerSmartboxIncludedAnimals;
use App\Livewire\Partner\Smartbox\SmartboxMeals as PartnerSmartboxMeals;
use App\Livewire\Partner\Smartbox\SmartboxName as PartnerSmartboxName;
use App\Livewire\Partner\Smartbox\SmartboxOffers as PartnerSmartboxOffers;
use App\Livewire\Partner\Smartbox\SmartboxPhotos as PartnerSmartboxPhotos;
use App\Livewire\Partner\Smartbox\SmartboxPrice as PartnerSmartboxPrice;
use App\Livewire\Partner\Smartbox\SmartboxStructures as PartnerSmartboxStructures;
use App\Livewire\Partner\Smartbox\SmartboxType as PartnerSmartboxType;
use App\Livewire\Partner\Structure\HotelAnimalServices as PartnerHotelAnimalServices;
use App\Livewire\Partner\Structure\HotelCancellation as PartnerHotelCancellation;
use App\Livewire\Partner\Structure\HotelDescription as PartnerHotelDescription;
use App\Livewire\Partner\Structure\HotelLocation as PartnerHotelLocation;
use App\Livewire\Partner\Structure\HotelPayment as PartnerHotelPayment;
use App\Livewire\Partner\Structure\HotelPhotos as PartnerHotelPhotos;
use App\Livewire\Partner\Structure\HotelRooms as PartnerHotelRooms;
use App\Livewire\Partner\Structure\HotelServices as PartnerHotelServices;
use App\Livewire\Partner\Structure\HotelSmartbox as PartnerHotelSmartbox;
use App\Livewire\Partner\Structure\HotelTitle as PartnerHotelTitle;
use App\Livewire\Partner\Structure\StructureType as PartnerStructureType;
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
    Route::get(LaravelLocalization::transRoute('routes.contact'), Contact::class)->name('contact');
    Route::get(LaravelLocalization::transRoute('routes.community'), Community::class)->name('community');
    Route::get(LaravelLocalization::transRoute('routes.community.post'), PostDetail::class)->name('community.post');
    Route::get(LaravelLocalization::transRoute('routes.preferiti'), Favorites::class)->name('preferiti');
    Route::get(LaravelLocalization::transRoute('routes.carrello'), Cart::class)->name('carrello');
    Route::get(LaravelLocalization::transRoute('routes.checkout'), Checkout::class)->name('checkout');

    Route::middleware('auth')->group(function () {
        Route::get(LaravelLocalization::transRoute('routes.profilo'), Profile::class)->name('profilo');
        // Stesso componente: su mobile /profilo è solo il menu (artboard app "Profilo"),
        // i campi anagrafici stanno sulla loro schermata. Su desktop le due rotte coincidono.
        Route::get(LaravelLocalization::transRoute('routes.profilo.anagrafica'), Profile::class)->name('profilo.anagrafica');
        Route::get(LaravelLocalization::transRoute('routes.profilo.pagamento'), ProfilePayment::class)->name('profilo.pagamento');
        Route::get(LaravelLocalization::transRoute('routes.profilo.sicurezza'), ProfileSecurity::class)->name('profilo.sicurezza');
        Route::get(LaravelLocalization::transRoute('routes.profilo.ordini'), ProfileOrders::class)->name('profilo.ordini');
        Route::get(LaravelLocalization::transRoute('routes.profilo.ordini.riepilogo'), ProfileOrderSummary::class)->name('profilo.ordini.riepilogo');
        Route::get(LaravelLocalization::transRoute('routes.profilo.eventi'), ProfileEvents::class)->name('profilo.eventi');
    });

    // Reimposta password: pubblica, il token nell'URL è la sola credenziale
    // (la richiesta del link parte dalla modale, non da una rotta dedicata).
    Route::get(LaravelLocalization::transRoute('routes.password.reset'), ResetPassword::class)->name('password.reset');

    Route::get(LaravelLocalization::transRoute('routes.news'), News::class)->name('news');
    Route::get(LaravelLocalization::transRoute('routes.news.detail'), NewsDetail::class)->name('news.detail');
    Route::get(LaravelLocalization::transRoute('routes.work-with-us'), WorkWithUs::class)->name('work-with-us');
    Route::get(LaravelLocalization::transRoute('routes.work-with-us.thanks'), WorkWithUsThanks::class)->name('work-with-us.thanks');
    Route::get(LaravelLocalization::transRoute('routes.partner.register'), PartnerRegisterStep1::class)->name('partner.register');
    Route::get(LaravelLocalization::transRoute('routes.partner.register.step2'), PartnerRegisterStep2::class)->name('partner.register.step2');
    // Area riservata partner: fuori dagli step del form (dashboard, crea servizio,
    // profilo). Richiede login + account attivo con ruolo partner. Gli step del
    // wizard (registrazione + creazione struttura/attività/smartbox) restano pubblici.
    Route::middleware(['auth', 'partner'])->group(function () {
        Route::get(LaravelLocalization::transRoute('routes.partner.dashboard'), PartnerDashboard::class)->name('partner.dashboard');
        Route::get(LaravelLocalization::transRoute('routes.partner.service.create'), PartnerCreateService::class)->name('partner.service.create');
        Route::get(LaravelLocalization::transRoute('routes.partner.services'), PartnerMyServices::class)->name('partner.services');
        Route::get(LaravelLocalization::transRoute('routes.partner.bookings'), PartnerBookings::class)->name('partner.bookings');
        Route::get(LaravelLocalization::transRoute('routes.partner.bookings.show'), PartnerBookingDetail::class)->name('partner.bookings.show');
        Route::get(LaravelLocalization::transRoute('routes.partner.services.show'), PartnerServiceDetail::class)->name('partner.services.show');
        Route::get(LaravelLocalization::transRoute('routes.partner.profile'), PartnerProfileInfo::class)->name('partner.profile');
        Route::get(LaravelLocalization::transRoute('routes.partner.profile.payment'), PartnerProfilePayment::class)->name('partner.profile.payment');
        Route::get(LaravelLocalization::transRoute('routes.partner.profile.security'), PartnerProfileSecurity::class)->name('partner.profile.security');
    });
    Route::get(LaravelLocalization::transRoute('routes.partner.structure.type'), PartnerStructureType::class)->name('partner.structure.type');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.type'), PartnerActivityType::class)->name('partner.activity.type');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.type'), PartnerSmartboxType::class)->name('partner.smartbox.type');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.name'), PartnerSmartboxName::class)->name('partner.smartbox.name');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.description'), PartnerSmartboxDescription::class)->name('partner.smartbox.description');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.duration'), PartnerSmartboxDuration::class)->name('partner.smartbox.duration');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.cancellation'), PartnerSmartboxCancellation::class)->name('partner.smartbox.cancellation');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.meals'), PartnerSmartboxMeals::class)->name('partner.smartbox.meals');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.offers'), PartnerSmartboxOffers::class)->name('partner.smartbox.offers');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.included'), PartnerSmartboxIncluded::class)->name('partner.smartbox.included');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.included-animals'), PartnerSmartboxIncludedAnimals::class)->name('partner.smartbox.included-animals');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.structures'), PartnerSmartboxStructures::class)->name('partner.smartbox.structures');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.photos'), PartnerSmartboxPhotos::class)->name('partner.smartbox.photos');
    Route::get(LaravelLocalization::transRoute('routes.partner.smartbox.price'), PartnerSmartboxPrice::class)->name('partner.smartbox.price');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.name'), PartnerActivityName::class)->name('partner.activity.name');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.location'), PartnerActivityLocation::class)->name('partner.activity.location');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.description'), PartnerActivityDescription::class)->name('partner.activity.description');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.info'), PartnerActivityInfo::class)->name('partner.activity.info');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.included'), PartnerActivityIncluded::class)->name('partner.activity.included');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.animal-services'), PartnerActivityAnimalServices::class)->name('partner.activity.animal-services');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.cost'), PartnerActivityCost::class)->name('partner.activity.cost');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.photos'), PartnerActivityPhotos::class)->name('partner.activity.photos');
    Route::get(LaravelLocalization::transRoute('routes.partner.activity.cancellation'), PartnerActivityCancellation::class)->name('partner.activity.cancellation');
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
