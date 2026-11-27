<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\TrackerDevice;
use App\Services\TrackerActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TrackerLocationController extends Controller
{
    public function __invoke(Request $request, TrackerActivityService $activityService): JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Content-Type must be application/json.',
            ], 415);
        }

        $identity = $request->validate([
            'device_id' => ['required', 'string', 'max:64'],
        ]);

        $device = TrackerDevice::query()
            ->where('device_id', $identity['device_id'])
            ->first();
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization header is missing or malformed. Use: Authorization: Bearer <raw device token> (no colon after Bearer).',
            ], 401);
        }

        if (preg_match('/^\\$2[aby]\\$/', $plainToken)) {
            return response()->json([
                'success' => false,
                'message' => 'A stored token hash was sent. Use the raw device token shown when the tracker was registered or its token was rotated.',
            ], 401);
        }

        if (! $device || ! $device->is_active || ! Hash::check($plainToken, $device->token_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Tracker authentication failed. Check that the device is registered and active, and that the raw bearer token is current.',
            ], 401);
        }

        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:64'],
            'device_name' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude_m' => ['nullable', 'numeric', 'between:-1000,100000'],
            'speed_kmh' => ['nullable', 'numeric', 'between:0,500'],
            'satellites' => ['nullable', 'integer', 'between:0,100'],
            'hdop' => ['nullable', 'numeric', 'between:0,99.99'],
            'gps_fix' => ['required', 'boolean'],
            'source' => ['required', 'string', 'in:live,simulated'],
            'recorded_at' => ['required', 'date'],
        ]);

        if (! hash_equals($device->name, $data['device_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tracker authentication failed. The device_name must exactly match the registered tracker name.',
            ], 401);
        }

        if ($data['gps_fix'] && (($data['latitude'] ?? null) === null || ($data['longitude'] ?? null) === null)) {
            throw ValidationException::withMessages([
                'latitude' => ['Latitude and longitude are required when gps_fix is true.'],
            ]);
        }

        if (! $data['gps_fix'] && (($data['latitude'] ?? null) !== null || ($data['longitude'] ?? null) !== null || ($data['altitude_m'] ?? null) !== null)) {
            throw ValidationException::withMessages([
                'gps_fix' => ['Coordinates and altitude must be null when gps_fix is false.'],
            ]);
        }

        if (! preg_match('/(?:Z|[+-]\\d{2}:\\d{2})$/i', $data['recorded_at'])) {
            throw ValidationException::withMessages([
                'recorded_at' => ['The recorded_at timestamp must include a timezone offset.'],
            ]);
        }

        $recordedAt = Carbon::parse($data['recorded_at'])->utc();
        $receivedAt = now('UTC');

        DB::transaction(function () use ($device, $data, $recordedAt, $receivedAt, $activityService): void {
            $location = $device->locations()->create([
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'altitude_m' => $data['altitude_m'] ?? null,
                'speed_kmh' => $data['speed_kmh'] ?? null,
                'satellites' => $data['satellites'] ?? null,
                'hdop' => $data['hdop'] ?? null,
                'gps_fix' => $data['gps_fix'],
                'source' => $data['source'],
                'recorded_at' => $recordedAt,
                'received_at' => $receivedAt,
            ]);

            $activityService->recordLocation($device, $location);
            $device->forceFill(['last_seen_at' => $receivedAt])->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Location received',
            'device_id' => $device->device_id,
            'received_at' => $receivedAt->toIso8601String(),
        ], 201);
    }
}
