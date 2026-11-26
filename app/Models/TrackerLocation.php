<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'latitude',
        'longitude',
        'altitude_m',
        'speed_kmh',
        'satellites',
        'hdop',
        'gps_fix',
        'source',
        'recorded_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'altitude_m' => 'decimal:2',
            'speed_kmh' => 'decimal:2',
            'satellites' => 'integer',
            'hdop' => 'decimal:2',
            'gps_fix' => 'boolean',
            'recorded_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
        ];
    }

    public function trackerDevice(): BelongsTo
    {
        return $this->belongsTo(TrackerDevice::class);
    }
}
