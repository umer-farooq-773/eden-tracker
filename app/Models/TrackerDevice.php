<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrackerDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'name',
        'token_hash',
        'last_seen_at',
        'is_active',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TrackerLocation::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(TrackerLocation::class)->latestOfMany('recorded_at');
    }
}
