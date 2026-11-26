<?php

use App\Http\Controllers\API\V1\TrackerLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/tracker')->name('api.v1.tracker.')
    ->middleware('throttle:120,1')
    ->group(function () {
        Route::post('/location', TrackerLocationController::class)->name('location.store');
    });
