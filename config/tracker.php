<?php

return [
    'parking_after_minutes' => (int) env('TRACKER_PARKING_AFTER_MINUTES', 5),
    'moving_without_trip_speed_kmh' => (float) env('TRACKER_MOVING_WITHOUT_TRIP_SPEED_KMH', 5),
];
