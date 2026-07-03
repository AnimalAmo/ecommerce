<?php

use App\Livewire\AnimalHoliday;
use App\Livewire\AnimalHolidayRegion;
use App\Livewire\AnimalHolidayStructure;
use App\Livewire\HomePage;
use App\Livewire\Smartbox;
use App\Livewire\WorkWithUs;
use App\Livewire\WorkWithUsThanks;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/animal-holiday', AnimalHoliday::class)->name('holiday');
Route::get('/animal-holiday/{region}', AnimalHolidayRegion::class)->name('holiday.region');
Route::get('/animal-holiday/{region}/{structure}', AnimalHolidayStructure::class)->name('holiday.structure');
Route::get('/smartbox', Smartbox::class)->name('smartbox');
Route::get('/lavora-con-noi', WorkWithUs::class)->name('work-with-us');
Route::get('/lavora-con-noi/grazie', WorkWithUsThanks::class)->name('work-with-us.thanks');
