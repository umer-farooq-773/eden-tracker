<?php

use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use App\Models\VehicleParked;
use App\Models\VehicleTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('trip history page renders successfully with devices and metrics', function () {
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-TRACKER-001',
        'name' => 'Eden Demo Tracker',
        'token_hash' => hash('sha256', 'secret-token'),
        'is_active' => true,
    ]);

    TrackerLocation::query()->create([
        'tracker_device_id' => $device->id,
        'latitude' => 33.6380919,
        'longitude' => 73.0770668,
        'altitude_m' => 514.5,
        'speed_kmh' => 40.5,
        'satellites' => 15,
        'hdop' => 1.80,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now(),
        'received_at' => now(),
    ]);

    $response = $this->get(route('tracker.trips.index'));

    $response->assertOk();
    $response->assertSee('Trip History');
    $response->assertSee('Eden Demo Tracker');
    $response->assertSee('EDEN-TRACKER-001');
    $response->assertSee('Vehicle / Unit');
    $response->assertSee('Show Track');
    $response->assertSee('Vehicle Telemetry');
    $response->assertSee('player-timeline-slider');
});

test('trip history data endpoint returns json structure with trips and stops', function () {
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-TRACKER-002',
        'name' => 'Eden Tracker 2',
        'token_hash' => hash('sha256', 'secret-token'),
        'is_active' => true,
    ]);

    $loc1 = TrackerLocation::query()->create([
        'tracker_device_id' => $device->id,
        'latitude' => 33.6380919,
        'longitude' => 73.0770668,
        'altitude_m' => 514.5,
        'speed_kmh' => 35.0,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now()->subMinutes(10),
        'received_at' => now()->subMinutes(10),
    ]);

    $loc2 = TrackerLocation::query()->create([
        'tracker_device_id' => $device->id,
        'latitude' => 33.6425919,
        'longitude' => 73.0823168,
        'altitude_m' => 520.0,
        'speed_kmh' => 45.0,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now()->subMinutes(5),
        'received_at' => now()->subMinutes(5),
    ]);

    VehicleTrip::query()->create([
        'tracker_device_id' => $device->id,
        'started_at' => now()->subMinutes(10),
        'ended_at' => now()->subMinutes(5),
        'location_points' => [
            ['latitude' => 33.6380919, 'longitude' => 73.0770668, 'speed_kmh' => 35.0],
            ['latitude' => 33.6425919, 'longitude' => 73.0823168, 'speed_kmh' => 45.0],
        ],
    ]);

    VehicleParked::query()->create([
        'tracker_device_id' => $device->id,
        'started_at' => now()->subMinutes(5),
        'ended_at' => now(),
        'last_location_id' => $loc2->id,
        'location_data' => ['latitude' => 33.6425919, 'longitude' => 73.0823168],
    ]);

    $response = $this->getJson(route('tracker.trips.data', ['device_id' => 'EDEN-TRACKER-002']));

    $response->assertOk();
    $response->assertJsonStructure([
        'devices',
        'selectedDevice',
        'trips',
        'stops',
        'gpsPoints',
        'totalMileageKm',
        'maxSpeedKmh',
        'totalDurationFormatted',
        'movingTimeFormatted',
    ]);

    $json = $response->json();
    expect($json['trips'])->toHaveCount(1);
    expect($json['stops'])->toHaveCount(1);
    expect($json['gpsPoints'])->toHaveCount(2);
});
