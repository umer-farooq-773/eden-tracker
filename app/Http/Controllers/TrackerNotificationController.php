<?php

namespace App\Http\Controllers;

use App\Models\TrackerNotification;
use App\Services\TrackerActivityService;
use Illuminate\Http\JsonResponse;

class TrackerNotificationController extends Controller
{
    public function index(TrackerActivityService $activityService): JsonResponse
    {
        $activityService->detectParkedVehicles();

        $notifications = TrackerNotification::query()
            ->with('trackerDevice:id,name,device_id')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (TrackerNotification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'device_name' => $notification->trackerDevice?->name ?? 'Vehicle',
                'created_at' => $notification->created_at?->timezone('Asia/Karachi')->format('M j, Y · g:i A'),
                'read' => $notification->read_at !== null,
            ]);

        return response()->json([
            'unread_count' => TrackerNotification::query()->whereNull('read_at')->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        TrackerNotification::query()->whereNull('read_at')->update(['read_at' => now('UTC')]);

        return response()->json(['success' => true]);
    }
}
