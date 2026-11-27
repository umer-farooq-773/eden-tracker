<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackerLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'altitude_m' => $this->altitude_m !== null ? (float) $this->altitude_m : null,
            'speed_kmh' => $this->speed_kmh !== null ? (float) $this->speed_kmh : null,
            'satellites' => $this->satellites,
            'hdop' => $this->hdop !== null ? (float) $this->hdop : null,
            'gps_fix' => (bool) $this->gps_fix,
            'source' => $this->source,
            'device_name' => $this->trackerDevice?->name ?: ($this->trackerDevice?->device_id ?: 'Unknown device'),
            'device_id' => $this->trackerDevice?->device_id ?? 'N/A',
            'recorded_at' => $this->recorded_at?->timezone('Asia/Karachi')->format('M j, Y · g:i:s A') ?? '—',
            'recorded_timestamp' => $this->recorded_at?->timestamp ?? 0,
        ];
    }
}
