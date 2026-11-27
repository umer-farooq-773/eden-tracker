<?php

use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('locations index renders correctly with map modal and map locations data', function () {
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-TRACKER-001',
        'name' => 'Eden Demo Tracker',
        'token_hash' => hash('sha256', 'secret-token'),
        'is_active' => true,
    ]);

    TrackerLocation::query()->create([
        'tracker_device_id' => $device->id,
        'latitude' => 34.5020280,
        'longitude' => 74.0618590,
        'altitude_m' => 511.9,
        'speed_kmh' => 36.0,
        'satellites' => 18,
        'hdop' => 1.80,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now(),
        'received_at' => now(),
    ]);

    $response = $this->get(route('tracker.locations.index'));

    $response->assertOk();
    $response->assertSee('Location Activity');
    $response->assertSee('Full screen map');
    $response->assertSee('id="gps-map-modal"', false);
    $response->assertSee('id="maplibre-canvas"', false);
    $response->assertSee('34.502028');
    $response->assertSee('74.061859');
    $response->assertViewHas('mapLocations', function ($mapLocations) {
        return $mapLocations->count() === 1
            && $mapLocations->first()['latitude'] === 34.502028
            && $mapLocations->first()['longitude'] === 74.061859;
    });
});

test('locations destroy-all removes all location records', function () {
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-TRACKER-002',
        'name' => 'Eden Tracker 2',
        'token_hash' => hash('sha256', 'secret-token'),
        'is_active' => true,
    ]);

    TrackerLocation::query()->create([
        'tracker_device_id' => $device->id,
        'latitude' => 34.5020280,
        'longitude' => 74.0618590,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now(),
        'received_at' => now(),
    ]);

    expect(TrackerLocation::count())->toBe(1);

    $response = $this->delete(route('tracker.locations.destroy-all'));

    $response->assertRedirect(route('tracker.locations.index'));
    expect(TrackerLocation::count())->toBe(0);
});
