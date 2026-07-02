<?php

use App\Livewire\HomePage;
use App\Livewire\WorkWithUs;
use App\Livewire\WorkWithUsThanks;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePage::class)->name('home');
Route::get('/lavora-con-noi', WorkWithUs::class)->name('work-with-us');
Route::get('/lavora-con-noi/grazie', WorkWithUsThanks::class)->name('work-with-us.thanks');
