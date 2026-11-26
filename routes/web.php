<?php

use App\Http\Controllers\TrackerLocationIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tracker/locations', TrackerLocationIndexController::class)
    ->name('tracker.locations.index');

Route::delete('/tracker/locations', [TrackerLocationIndexController::class, 'destroyAll'])
    ->name('tracker.locations.destroy-all');

Route::delete('/tracker/locations/{trackerLocation}', [TrackerLocationIndexController::class, 'destroy'])
    ->name('tracker.locations.destroy');
