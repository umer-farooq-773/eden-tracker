<?php

use App\Models\TrackerDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shows a notification when a moving vehicle has no active trip', function () {
    config(['tracker.moving_without_trip_speed_kmh' => 5]);
    $token = 'tracker-test-token';
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-MOVING-001',
        'name' => 'Moving Test Vehicle',
        'token_hash' => Hash::make($token),
        'is_active' => true,
        'is_trip_tracking' => false,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson(route('api.v1.tracker.location.store'), [
        'device_id' => $device->device_id,
        'device_name' => $device->name,
        'latitude' => 33.6844,
        'longitude' => 73.0479,
        'altitude_m' => 540,
        'speed_kmh' => 42,
        'satellites' => 10,
        'hdop' => 1.2,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now()->toIso8601String(),
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('tracker_notifications', [
        'tracker_device_id' => $device->id,
        'type' => 'vehicle_moving_without_trip',
        'title' => 'Vehicle moving without a trip',
    ]);
    $this->assertDatabaseCount('vehicle_trips', 0);

    $feed = $this->getJson(route('tracker.notifications.index'));
    $feed->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.title', 'Vehicle moving without a trip')
        ->assertJsonPath('notifications.0.device_name', 'Moving Test Vehicle')
        ->assertJsonPath('notifications.0.tracker_device_id', $device->id)
        ->assertJsonPath('notifications.0.data.speed_kmh', 42);

    $map = $this->get(route('tracker.live-map'));
    $map->assertOk()->assertSee('notification-toast-region')->assertSee('showNotificationToast');
});

it('does not send a movement notification below the speed threshold', function () {
    config(['tracker.moving_without_trip_speed_kmh' => 5]);
    $token = 'tracker-stationary-test-token';
    $device = TrackerDevice::query()->create([
        'device_id' => 'EDEN-STATIONARY-001',
        'name' => 'Stationary Test Vehicle',
        'token_hash' => Hash::make($token),
        'is_active' => true,
        'is_trip_tracking' => false,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)->postJson(route('api.v1.tracker.location.store'), [
        'device_id' => $device->device_id,
        'device_name' => $device->name,
        'latitude' => 33.6844,
        'longitude' => 73.0479,
        'altitude_m' => 540,
        'speed_kmh' => 0,
        'satellites' => 10,
        'hdop' => 1.2,
        'gps_fix' => true,
        'source' => 'live',
        'recorded_at' => now()->toIso8601String(),
    ]);

    $response->assertCreated();
    $this->assertDatabaseCount('tracker_notifications', 0);
    $this->assertDatabaseCount('vehicle_trips', 0);
});
