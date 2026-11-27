<?php

namespace App\Http\Controllers;

use App\Models\TrackerDevice;
use App\Services\TrackerActivityService;
use Illuminate\Http\RedirectResponse;

class TrackerTripController extends Controller
{
    public function start(TrackerDevice $trackerDevice, TrackerActivityService $activityService): RedirectResponse
    {
        $activityService->startTrip($trackerDevice);

        return to_route('tracker.locations.index')->with('status', $trackerDevice->name.' trip started.');
    }

    public function stop(TrackerDevice $trackerDevice, TrackerActivityService $activityService): RedirectResponse
    {
        $activityService->stopTrip($trackerDevice);

        return to_route('tracker.locations.index')->with('status', $trackerDevice->name.' trip stopped.');
    }
}
