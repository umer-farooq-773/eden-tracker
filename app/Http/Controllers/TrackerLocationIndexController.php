<?php

namespace App\Http\Controllers;

use App\Models\TrackerLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TrackerLocationIndexController extends Controller
{
    public function __invoke(): View
    {
        $locations = TrackerLocation::query()
            ->with('trackerDevice:id,device_id,name')
            ->latest('recorded_at')
            ->paginate(50);

        return view('tracker.locations.index', compact('locations'));
    }

    public function destroy(TrackerLocation $trackerLocation): RedirectResponse
    {
        $trackerLocation->delete();

        return to_route('tracker.locations.index')
            ->with('status', 'Location record #'.$trackerLocation->id.' was deleted.');
    }

    public function destroyAll(): RedirectResponse
    {
        $deletedCount = TrackerLocation::query()->delete();

        return to_route('tracker.locations.index')
            ->with('status', $deletedCount.' location records were deleted.');
    }
}
