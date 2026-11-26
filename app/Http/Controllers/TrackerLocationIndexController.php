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

        $mapLocations = $locations->getCollection()
            ->filter(fn (TrackerLocation $location): bool => $location->latitude !== null && $location->longitude !== null)
            ->map(fn (TrackerLocation $location): array => [
                'id' => $location->id,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'altitude_m' => $location->altitude_m !== null ? (float) $location->altitude_m : null,
                'speed_kmh' => $location->speed_kmh !== null ? (float) $location->speed_kmh : null,
                'satellites' => $location->satellites,
                'hdop' => $location->hdop !== null ? (float) $location->hdop : null,
                'gps_fix' => (bool) $location->gps_fix,
                'source' => $location->source,
                'device_name' => $location->trackerDevice?->name ?: ($location->trackerDevice?->device_id ?: 'Unknown device'),
                'device_id' => $location->trackerDevice?->device_id ?? 'N/A',
                'recorded_at' => $location->recorded_at?->timezone('Asia/Karachi')->format('M j, Y · g:i:s A') ?? '—',
                'recorded_timestamp' => $location->recorded_at?->timestamp ?? 0,
            ])
            ->values();

        return view('tracker.locations.index', compact('locations', 'mapLocations'));
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
