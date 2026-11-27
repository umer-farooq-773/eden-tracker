<?php

use App\Http\Controllers\TrackerLiveMapController;
use App\Http\Controllers\TrackerLocationIndexController;
use App\Http\Controllers\TrackerLocationUpdatesController;
use App\Http\Controllers\TrackerNotificationController;
use App\Http\Controllers\TrackerTripController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tracker/live-map', TrackerLiveMapController::class)
    ->name('tracker.live-map');

Route::get('/tracker/locations', TrackerLocationIndexController::class)
    ->name('tracker.locations.index');

Route::get('/tracker/locations/updates', TrackerLocationUpdatesController::class)
    ->middleware('throttle:120,1')
    ->name('tracker.locations.updates');

Route::get('/tracker/notifications', [TrackerNotificationController::class, 'index'])
    ->name('tracker.notifications.index');
Route::patch('/tracker/notifications/read', [TrackerNotificationController::class, 'markAllRead'])
    ->name('tracker.notifications.read');
Route::post('/tracker/devices/{trackerDevice}/trip/start', [TrackerTripController::class, 'start'])
    ->name('tracker.trips.start');
Route::post('/tracker/devices/{trackerDevice}/trip/stop', [TrackerTripController::class, 'stop'])
    ->name('tracker.trips.stop');

Route::delete('/tracker/locations', [TrackerLocationIndexController::class, 'destroyAll'])
    ->name('tracker.locations.destroy-all');

Route::delete('/tracker/locations/{trackerLocation}', [TrackerLocationIndexController::class, 'destroy'])
    ->name('tracker.locations.destroy');
