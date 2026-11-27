<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackerLocationResource;
use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use App\Models\VehicleParked;
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

        $mapLocations = collect(TrackerLocationResource::collection(
            $locations->getCollection()->filter(
                fn (TrackerLocation $location): bool => $location->latitude !== null && $location->longitude !== null
            )->values()
        )->resolve());
        $locationCursor = (int) (TrackerLocation::query()->max('id') ?? 0);
        $devices = TrackerDevice::query()
            ->where('is_active', true)
            ->with('activeTrip')
            ->orderBy('name')
            ->get();
        $activeParkings = VehicleParked::query()
            ->with('trackerDevice:id,device_id,name')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->get();
        $mapParkings = $activeParkings->map(fn ($parking): array => [
            'id' => $parking->id,
            'started_at' => $parking->started_at?->timezone('Asia/Karachi')->format('M j, Y · g:i A'),
            'location' => $parking->location_data,
            'device_name' => $parking->trackerDevice?->name ?? 'Vehicle',
            'device_id' => $parking->trackerDevice?->device_id ?? 'N/A',
        ])->values();

        return view('tracker.locations.index', compact('locations', 'mapLocations', 'locationCursor', 'devices', 'activeParkings', 'mapParkings'));
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
