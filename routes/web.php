<?php

use App\Http\Controllers\TrackerLocationIndexController;
use App\Http\Controllers\TrackerLocationUpdatesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tracker/locations', TrackerLocationIndexController::class)
    ->name('tracker.locations.index');

Route::get('/tracker/locations/updates', TrackerLocationUpdatesController::class)
    ->middleware('throttle:120,1')
    ->name('tracker.locations.updates');

Route::delete('/tracker/locations', [TrackerLocationIndexController::class, 'destroyAll'])
    ->name('tracker.locations.destroy-all');

Route::delete('/tracker/locations/{trackerLocation}', [TrackerLocationIndexController::class, 'destroy'])
    ->name('tracker.locations.destroy');
