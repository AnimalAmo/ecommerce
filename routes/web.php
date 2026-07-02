<?php

use App\Livewire\AnimalHoliday;
use App\Livewire\AnimalHolidayRegion;
use App\Livewire\HomePage;
use App\Livewire\WorkWithUs;
use App\Livewire\WorkWithUsThanks;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/animal-holiday', AnimalHoliday::class)->name('holiday');
Route::get('/animal-holiday/{region}', AnimalHolidayRegion::class)->name('holiday.region');
Route::get('/lavora-con-noi', WorkWithUs::class)->name('work-with-us');
Route::get('/lavora-con-noi/grazie', WorkWithUsThanks::class)->name('work-with-us.thanks');
