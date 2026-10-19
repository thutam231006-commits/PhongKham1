<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/specialists', [BookingController::class, 'specialistsApi'])->name('api.specialists.index');
