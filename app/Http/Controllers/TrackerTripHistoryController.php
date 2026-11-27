<?php

namespace App\Http\Controllers;

use App\Models\TrackerDevice;
use App\Models\TrackerLocation;
use App\Models\VehicleParked;
use App\Models\VehicleTrip;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackerTripHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildTripHistoryData($request);

        return view('tracker.trips.index', $data);
    }

    public function data(Request $request): JsonResponse
    {
        $data = $this->buildTripHistoryData($request);

        return response()->json($data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTripHistoryData(Request $request): array
    {
        $devices = TrackerDevice::query()
            ->orderBy('name')
            ->get(['id', 'device_id', 'name', 'is_active', 'last_seen_at']);

        $selectedDeviceId = $request->query('device_id');
        $selectedDevice = $devices->firstWhere('device_id', $selectedDeviceId) ?? $devices->first();

        $preset = $request->query('preset', 'today');
        $tz = 'Asia/Karachi';
        $now = CarbonImmutable::now($tz);

        if ($preset === 'yesterday') {
            $from = $now->subDay()->startOfDay();
            $to = $now->subDay()->endOfDay();
        } elseif ($preset === 'week') {
            $from = $now->subDays(7)->startOfDay();
            $to = $now->endOfDay();
        } elseif ($preset === 'month') {
            $from = $now->subDays(30)->startOfDay();
            $to = $now->endOfDay();
        } elseif ($request->filled('from') && $request->filled('to')) {
            $from = CarbonImmutable::parse($request->query('from'), $tz);
            $to = CarbonImmutable::parse($request->query('to'), $tz);
            $preset = 'custom';
        } else {
            // Default today
            $from = $now->startOfDay();
            $to = $now->endOfDay();
            $preset = 'today';
        }

        $trips = [];
        $stops = [];
        $gpsPoints = [];
        $totalMileageKm = 0.0;
        $maxSpeedKmh = 0.0;
        $movingDurationSeconds = 0;

        if ($selectedDevice) {
            // Convert to UTC for DB query
            $fromUtc = $from->setTimezone('UTC');
            $toUtc = $to->setTimezone('UTC');

            // 1. Fetch raw GPS locations
            $rawLocations = TrackerLocation::query()
                ->where('tracker_device_id', $selectedDevice->id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('recorded_at', '>=', $fromUtc)
                ->where('recorded_at', '<=', $toUtc)
                ->orderBy('recorded_at')
                ->get();

            // 2. Fetch recorded vehicle trips
            $dbTrips = VehicleTrip::query()
                ->where('tracker_device_id', $selectedDevice->id)
                ->where(function ($q) use ($fromUtc, $toUtc) {
                    $q->whereBetween('started_at', [$fromUtc, $toUtc])
                        ->orWhereBetween('ended_at', [$fromUtc, $toUtc]);
                })
                ->orderBy('started_at')
                ->get();

            // 3. Fetch recorded vehicle parking/stops
            $dbParkings = VehicleParked::query()
                ->where('tracker_device_id', $selectedDevice->id)
                ->where(function ($q) use ($fromUtc, $toUtc) {
                    $q->whereBetween('started_at', [$fromUtc, $toUtc])
                        ->orWhereBetween('ended_at', [$fromUtc, $toUtc]);
                })
                ->orderBy('started_at')
                ->get();

            // Format raw GPS points with course calculation
            $pointsCount = $rawLocations->count();
            for ($i = 0; $i < $pointsCount; $i++) {
                $curr = $rawLocations[$i];
                $next = $i + 1 < $pointsCount ? $rawLocations[$i + 1] : null;

                $lat = (float) $curr->latitude;
                $lng = (float) $curr->longitude;
                $speed = $curr->speed_kmh !== null ? (float) $curr->speed_kmh : 0.0;
                $alt = $curr->altitude_m !== null ? (float) $curr->altitude_m : 0.0;

                if ($speed > $maxSpeedKmh) {
                    $maxSpeedKmh = $speed;
                }

                $course = 0.0;
                if ($next && $next->latitude !== null && $next->longitude !== null) {
                    $course = $this->calculateCourse($lat, $lng, (float) $next->latitude, (float) $next->longitude);
                }

                $gpsPoints[] = [
                    'id' => $curr->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'speed' => $speed,
                    'altitude' => $alt,
                    'course' => round($course, 1),
                    'satellites' => $curr->satellites ?? 0,
                    'hdop' => $curr->hdop !== null ? (float) $curr->hdop : 1.0,
                    'gps_fix' => (bool) $curr->gps_fix,
                    'source' => $curr->source,
                    'timestamp' => $curr->recorded_at?->timezone($tz)->timestamp ?? 0,
                    'time_formatted' => $curr->recorded_at?->timezone($tz)->format('M j, Y · g:i:s A') ?? '',
                    'time_short' => $curr->recorded_at?->timezone($tz)->format('g:i:s A') ?? '',
                ];
            }

            // Calculate cumulative distance and moving duration
            for ($i = 0; $i < count($gpsPoints) - 1; $i++) {
                $p1 = $gpsPoints[$i];
                $p2 = $gpsPoints[$i + 1];
                $dist = $this->distanceKm($p1['lat'], $p1['lng'], $p2['lat'], $p2['lng']);
                $totalMileageKm += $dist;

                $dt = $p2['timestamp'] - $p1['timestamp'];
                if ($dt > 0 && $dt < 1800 && ($p1['speed'] > 2 || $dist > 0.01)) {
                    $movingDurationSeconds += $dt;
                }
            }

            // Build trips list
            if ($dbTrips->isNotEmpty()) {
                $tripColors = ['#2563eb', '#16a34a', '#d97706', '#9333ea', '#0284c7', '#ea580c', '#e11d48'];
                $tripIndex = 1;

                foreach ($dbTrips as $dbTrip) {
                    $color = $tripColors[($tripIndex - 1) % count($tripColors)];
                    $pts = $dbTrip->location_points ?? [];
                    $tripPts = [];
                    $tripDist = 0.0;
                    $tripSpeeds = [];

                    foreach ($pts as $pt) {
                        if (isset($pt['latitude'], $pt['longitude'])) {
                            $tripPts[] = [
                                'lat' => (float) $pt['latitude'],
                                'lng' => (float) $pt['longitude'],
                                'speed' => isset($pt['speed_kmh']) ? (float) $pt['speed_kmh'] : 0.0,
                            ];
                            if (isset($pt['speed_kmh'])) {
                                $tripSpeeds[] = (float) $pt['speed_kmh'];
                            }
                        }
                    }

                    for ($j = 0; $j < count($tripPts) - 1; $j++) {
                        $tripDist += $this->distanceKm($tripPts[$j]['lat'], $tripPts[$j]['lng'], $tripPts[$j + 1]['lat'], $tripPts[$j + 1]['lng']);
                    }

                    $startT = $dbTrip->started_at?->timezone($tz);
                    $endT = ($dbTrip->ended_at ?? $now)->timezone($tz);
                    $durationSec = max(0, $endT->timestamp - ($startT?->timestamp ?? $endT->timestamp));
                    $avgSpeed = count($tripSpeeds) > 0 ? round(array_sum($tripSpeeds) / count($tripSpeeds), 1) : 0.0;

                    $trips[] = [
                        'id' => $dbTrip->id,
                        'number' => $tripIndex,
                        'name' => 'Trip '.$tripIndex,
                        'color' => $color,
                        'distance_km' => round($tripDist, 1),
                        'duration_formatted' => $this->formatDuration($durationSec),
                        'avg_speed_kmh' => $avgSpeed,
                        'start_time' => $startT?->format('d M Y, H:i') ?? '—',
                        'end_time' => $endT->format('d M Y, H:i'),
                        'time_range' => ($startT?->format('d M Y, H:i') ?? '—').' - '.$endT->format('d M Y, H:i'),
                        'points' => $tripPts,
                    ];

                    $tripIndex++;
                }
            } elseif (count($gpsPoints) >= 2) {
                // Synthesize 1 continuous trip if no explicit trips table rows
                $trips[] = [
                    'id' => 1,
                    'number' => 1,
                    'name' => 'Trip 1',
                    'color' => '#2563eb',
                    'distance_km' => round($totalMileageKm, 1),
                    'duration_formatted' => $this->formatDuration($movingDurationSeconds ?: 600),
                    'avg_speed_kmh' => round($totalMileageKm / max(0.1, ($movingDurationSeconds ?: 600) / 3600), 1),
                    'start_time' => $gpsPoints[0]['time_formatted'],
                    'end_time' => $gpsPoints[count($gpsPoints) - 1]['time_formatted'],
                    'time_range' => $gpsPoints[0]['time_short'].' - '.$gpsPoints[count($gpsPoints) - 1]['time_short'],
                    'points' => array_map(fn ($p) => ['lat' => $p['lat'], 'lng' => $p['lng'], 'speed' => $p['speed']], $gpsPoints),
                ];
            }

            // Build stops list
            if ($dbParkings->isNotEmpty()) {
                $stopIndex = 1;
                foreach ($dbParkings as $dbPark) {
                    $startT = $dbPark->started_at?->timezone($tz);
                    $endT = ($dbPark->ended_at ?? $now)->timezone($tz);
                    $durationSec = max(0, $endT->timestamp - ($startT?->timestamp ?? $endT->timestamp));

                    $loc = $dbPark->location_data ?? [];
                    $lat = isset($loc['latitude']) ? (float) $loc['latitude'] : 0.0;
                    $lng = isset($loc['longitude']) ? (float) $loc['longitude'] : 0.0;

                    $stops[] = [
                        'id' => $dbPark->id,
                        'number' => $stopIndex,
                        'name' => 'Stop '.$stopIndex,
                        'duration_formatted' => $this->formatDuration($durationSec),
                        'duration_seconds' => $durationSec,
                        'start_time' => $startT?->format('d M Y, H:i') ?? '—',
                        'end_time' => $endT->format('d M Y, H:i'),
                        'time_range' => ($startT?->format('d M Y, H:i') ?? '—').' - '.$endT->format('d M Y, H:i'),
                        'lat' => $lat,
                        'lng' => $lng,
                        'coords_formatted' => round($lat, 5).', '.round($lng, 5),
                    ];
                    $stopIndex++;
                }
            }
        }

        $totalDurationSeconds = count($gpsPoints) >= 2
            ? max(0, $gpsPoints[count($gpsPoints) - 1]['timestamp'] - $gpsPoints[0]['timestamp'])
            : 0;

        return [
            'devices' => $devices,
            'selectedDevice' => $selectedDevice,
            'preset' => $preset,
            'from' => $from->format('Y-m-d\TH:i'),
            'to' => $to->format('Y-m-d\TH:i'),
            'fromDisplay' => $from->format('m/d/Y h:i A'),
            'toDisplay' => $to->format('m/d/Y h:i A'),
            'trips' => $trips,
            'stops' => $stops,
            'gpsPoints' => $gpsPoints,
            'totalMileageKm' => round($totalMileageKm, 2),
            'maxSpeedKmh' => round($maxSpeedKmh, 1),
            'totalDurationFormatted' => $this->formatDuration($totalDurationSeconds),
            'movingTimeFormatted' => $this->formatDuration($movingDurationSeconds),
        ];
    }

    protected function formatDuration(int $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return "{$h}h {$m}m {$s}s";
        }

        if ($m > 0) {
            return "{$m}m {$s}s";
        }

        return "{$s}s";
    }

    protected function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $toRad = pi() / 180;
        $r = 6371; // Earth radius in km

        $dLat = ($lat2 - $lat1) * $toRad;
        $dLon = ($lon2 - $lon1) * $toRad;

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1 * $toRad) * cos($lat2 * $toRad) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }

    protected function calculateCourse(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLon = deg2rad($lon2 - $lon1);
        $y = sin($dLon) * cos(deg2rad($lat2));
        $x = cos(deg2rad($lat1)) * sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($dLon);
        $brng = rad2deg(atan2($y, $x));

        return fmod(($brng + 360), 360);
    }
}
