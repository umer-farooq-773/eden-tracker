<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackerLocationResource;
use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use App\Models\VehicleParked;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TrackerLiveMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $devices = TrackerDevice::query()
            ->where('is_active', true)
            ->with('latestLocation')
            ->orderBy('name')
            ->get();

        $selectedDevice = $devices->firstWhere('device_id', $request->query('device_id'));

        if ($selectedDevice) {
            $locations = $selectedDevice->locations()
                ->with('trackerDevice:id,device_id,name')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->latest('recorded_at')
                ->limit(500)
                ->get()
                ->reverse()
                ->values();
        } else {
            $locations = $devices
                ->pluck('latestLocation')
                ->filter(fn (?TrackerLocation $location): bool => $location !== null && $location->latitude !== null && $location->longitude !== null)
                ->values();
        }

        $mapLocations = TrackerLocationResource::collection($locations)->resolve();
        $locationCursor = (int) (TrackerLocation::query()->max('id') ?? 0);
        $activeParkings = VehicleParked::query()
            ->with('trackerDevice:id,device_id,name')
            ->whereNull('ended_at')
            ->when($selectedDevice, fn ($query) => $query->where('tracker_device_id', $selectedDevice->id))
            ->latest('started_at')
            ->get();
        $mapParkings = $activeParkings->map(fn ($parking): array => [
            'id' => $parking->id,
            'started_at' => $parking->started_at?->timezone('Asia/Karachi')->format('M j, Y · g:i A'),
            'location' => $parking->location_data,
            'device_name' => $parking->trackerDevice?->name ?? 'Vehicle',
            'device_id' => $parking->trackerDevice?->device_id ?? 'N/A',
        ])->values();
        $mapDevices = $devices->map(fn (TrackerDevice $device): array => [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'name' => $device->name,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'location' => $device->latestLocation
                ? TrackerLocationResource::make($device->latestLocation)->resolve()
                : null,
        ])->values();

        return view('tracker.live-map.index', compact(
            'mapDevices',
            'mapLocations',
            'mapParkings',
            'locationCursor',
            'selectedDevice',
            'devices',
        ));
    }
}
