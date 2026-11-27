<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackerLocationResource;
use App\Models\TrackerLocation;
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
        ]);
    }
}
