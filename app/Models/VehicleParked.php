<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleParked extends Model
{
    protected $table = 'vehicle_parked';

    protected $fillable = ['tracker_device_id', 'started_at', 'ended_at', 'last_location_id', 'location_data'];

    protected function casts(): array
    {
        return ['started_at' => 'immutable_datetime', 'ended_at' => 'immutable_datetime', 'location_data' => 'array'];
    }

    public function trackerDevice(): BelongsTo
    {
        return $this->belongsTo(TrackerDevice::class);
    }
}
