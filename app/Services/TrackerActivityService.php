<?php

namespace App\Services;

use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use App\Models\TrackerNotification;
use App\Models\VehicleParked;
use App\Models\VehicleTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackerActivityService
{
    /** @return array<string, mixed> */
    public function locationSnapshot(TrackerLocation $location): array
    {
        return [
            'location_id' => $location->id,
            'latitude' => $location->latitude !== null ? (float) $location->latitude : null,
            'longitude' => $location->longitude !== null ? (float) $location->longitude : null,
            'altitude_m' => $location->altitude_m !== null ? (float) $location->altitude_m : null,
            'speed_kmh' => $location->speed_kmh !== null ? (float) $location->speed_kmh : null,
            'satellites' => $location->satellites,
            'hdop' => $location->hdop !== null ? (float) $location->hdop : null,
            'gps_fix' => (bool) $location->gps_fix,
            'source' => $location->source,
            'recorded_at' => $location->recorded_at?->toIso8601String(),
            'received_at' => $location->received_at?->toIso8601String(),
        ];
    }

    public function recordLocation(TrackerDevice $device, TrackerLocation $location): void
    {
        $lockedDevice = TrackerDevice::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();
        $snapshot = $this->locationSnapshot($location);

        $parking = VehicleParked::query()
            ->where('tracker_device_id', $lockedDevice->id)
            ->whereNull('ended_at')
            ->lockForUpdate()
            ->first();

        if ($parking) {
            $parking->forceFill(['ended_at' => $location->received_at])->save();
        } elseif (
            $lockedDevice->last_seen_at
            && $lockedDevice->last_seen_at->diffInMinutes($location->received_at) >= $this->parkingThresholdLabel()
        ) {
            $lastGpsLocation = $lockedDevice->locations()
                ->where('gps_fix', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('id', '<', $location->id)
                ->latest('received_at')
                ->first();

            if ($lastGpsLocation) {
                $lastSnapshot = $this->locationSnapshot($lastGpsLocation);
                $parking = VehicleParked::query()->create([
                    'tracker_device_id' => $lockedDevice->id,
                    'started_at' => $lockedDevice->last_seen_at,
                    'ended_at' => $location->received_at,
                    'last_location_id' => $lastGpsLocation->id,
                    'location_data' => $lastSnapshot,
                ]);

                TrackerNotification::query()->create([
                    'tracker_device_id' => $lockedDevice->id,
                    'type' => 'vehicle_parked',
                    'title' => 'Vehicle may have been parked',
                    'message' => $lockedDevice->name.' had no GPS update for at least '.$this->parkingThresholdLabel().' minutes. The interval has been saved.',
                    'data' => ['parking_id' => $parking->id, 'started_at' => $parking->started_at->toIso8601String(), 'ended_at' => $parking->ended_at->toIso8601String(), 'location' => $lastSnapshot],
                ]);
            }
        }

        if (! $lockedDevice->is_trip_tracking) {
            return;
        }

        $trip = VehicleTrip::query()
            ->where('tracker_device_id', $lockedDevice->id)
            ->whereNull('ended_at')
            ->lockForUpdate()
            ->first();

        if (! $trip) {
            if (! $location->gps_fix) {
                return;
            }

            $trip = VehicleTrip::query()->create([
                'tracker_device_id' => $lockedDevice->id,
                'started_at' => $location->received_at,
                'location_points' => [$snapshot],
            ]);

            TrackerNotification::query()->create([
                'tracker_device_id' => $lockedDevice->id,
                'type' => 'trip_started',
                'title' => 'Trip started',
                'message' => $lockedDevice->name.' started a new trip.',
                'data' => ['trip_id' => $trip->id, 'location' => $snapshot],
            ]);

            return;
        }

        $points = $trip->location_points ?? [];
        $points[] = $snapshot;
        $trip->forceFill(['location_points' => $points])->save();
    }

    public function startTrip(TrackerDevice $device): VehicleTrip
    {
        return DB::transaction(function () use ($device): VehicleTrip {
            $lockedDevice = TrackerDevice::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();
            $trip = VehicleTrip::query()
                ->where('tracker_device_id', $lockedDevice->id)
                ->whereNull('ended_at')
                ->first();

            if ($trip) {
                return $trip;
            }

            $location = $lockedDevice->locations()->where('gps_fix', true)->whereNotNull('latitude')->whereNotNull('longitude')->latest('received_at')->first();
            if (! $location) {
                throw ValidationException::withMessages([
                    'tracker' => ['A GPS location is required before starting a trip.'],
                ]);
            }

            $snapshot = $this->locationSnapshot($location);
            $trip = VehicleTrip::query()->create([
                'tracker_device_id' => $lockedDevice->id,
                'started_at' => now('UTC'),
                'location_points' => [$snapshot],
            ]);
            $lockedDevice->forceFill(['is_trip_tracking' => true])->save();

            TrackerNotification::query()->create([
                'tracker_device_id' => $lockedDevice->id,
                'type' => 'trip_started',
                'title' => 'Trip started',
                'message' => $lockedDevice->name.' started a new trip.',
                'data' => ['trip_id' => $trip->id, 'location' => $snapshot],
            ]);

            return $trip;
        });
    }

    public function stopTrip(TrackerDevice $device): ?VehicleTrip
    {
        return DB::transaction(function () use ($device): ?VehicleTrip {
            $lockedDevice = TrackerDevice::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();
            $trip = VehicleTrip::query()
                ->where('tracker_device_id', $lockedDevice->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            $lockedDevice->forceFill(['is_trip_tracking' => false])->save();
            if (! $trip) {
                return null;
            }

            $trip->forceFill(['ended_at' => now('UTC')])->save();
            $points = $trip->location_points ?? [];
            $snapshot = $points === [] ? null : $points[array_key_last($points)];

            TrackerNotification::query()->create([
                'tracker_device_id' => $lockedDevice->id,
                'type' => 'trip_stopped',
                'title' => 'Trip stopped',
                'message' => $lockedDevice->name.' trip was stopped.',
                'data' => ['trip_id' => $trip->id, 'location' => $snapshot],
            ]);

            return $trip;
        });
    }

    public function detectParkedVehicles(): int
    {
        $thresholdMinutes = max(1, (int) config('tracker.parking_after_minutes', 5));
        $staleBefore = now('UTC')->subMinutes($thresholdMinutes);
        $createdCount = 0;

        TrackerDevice::query()
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', $staleBefore)
            ->chunkById(100, function ($devices) use ($staleBefore, &$createdCount): void {
                foreach ($devices as $device) {
                    DB::transaction(function () use ($device, $staleBefore, &$createdCount): void {
                        $lockedDevice = TrackerDevice::query()->whereKey($device->id)->lockForUpdate()->first();
                        if (! $lockedDevice || ! $lockedDevice->last_seen_at || $lockedDevice->last_seen_at->gt($staleBefore)) {
                            return;
                        }

                        $hasOpenParking = VehicleParked::query()
                            ->where('tracker_device_id', $lockedDevice->id)
                            ->whereNull('ended_at')
                            ->lockForUpdate()
                            ->exists();
                        if ($hasOpenParking) {
                            return;
                        }

                        $location = $lockedDevice->locations()
                            ->where('gps_fix', true)
                            ->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->latest('received_at')
                            ->first();
                        if (! $location) {
                            return;
                        }

                        $snapshot = $this->locationSnapshot($location);
                        $parking = VehicleParked::query()->create([
                            'tracker_device_id' => $lockedDevice->id,
                            'started_at' => $lockedDevice->last_seen_at,
                            'last_location_id' => $location->id,
                            'location_data' => $snapshot,
                        ]);

                        TrackerNotification::query()->create([
                            'tracker_device_id' => $lockedDevice->id,
                            'type' => 'vehicle_parked',
                            'title' => 'Vehicle may be parked',
                            'message' => $lockedDevice->name.' has not sent a GPS update for '.$this->parkingThresholdLabel().' minutes. Last known position is saved.',
                            'data' => ['parking_id' => $parking->id, 'started_at' => $parking->started_at->toIso8601String(), 'location' => $snapshot],
                        ]);

                        $createdCount++;
                    });
                }
            });

        return $createdCount;
    }

    private function parkingThresholdLabel(): int
    {
        return max(1, (int) config('tracker.parking_after_minutes', 5));
    }
}
