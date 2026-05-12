<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocalizationController;




//for multi language support

Route::get('lang/{locale}', LocalizationController::class)->name('lang.switch');




//
Route::get('/', function () {
    return view('welcome');
});
