<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackerLocationResource;
use App\Models\TrackerLocation;
use App\Models\VehicleParked;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackerLocationUpdatesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['required', 'integer', 'min:0'],
        ]);

        $locations = TrackerLocation::query()
            ->with('trackerDevice:id,device_id,name')
            ->where('id', '>', $validated['after_id'])
            ->oldest('id')
            ->limit(100)
            ->get();

        $rows = $locations
            ->reverse()
            ->map(fn (TrackerLocation $location): string => view('tracker.locations.partials.table-row', compact('location'))->render())
            ->implode('');

        return response()->json([
            'data' => TrackerLocationResource::collection($locations)->resolve(),
            'rows' => $rows,
            'cursor' => max((int) $validated['after_id'], (int) ($locations->max('id') ?? 0)),
            'total' => TrackerLocation::query()->count(),
            'active_parkings' => VehicleParked::query()
                ->with('trackerDevice:id,device_id,name')
                ->whereNull('ended_at')
                ->get()
                ->map(fn (VehicleParked $parking): array => [
                    'id' => $parking->id,
                    'started_at' => $parking->started_at?->timezone('Asia/Karachi')->format('M j, Y · g:i A'),
                    'location' => $parking->location_data,
                    'device_name' => $parking->trackerDevice?->name ?? 'Vehicle',
                    'device_id' => $parking->trackerDevice?->device_id ?? 'N/A',
                ]),
        ]);
    }
}
