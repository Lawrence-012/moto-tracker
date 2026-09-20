<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'trip_ticket_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'speed_kph',
        'heading',
        'is_sharing',
        'inside_clsu_geofence',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_meters' => 'float',
        'speed_kph' => 'float',
        'heading' => 'float',
        'is_sharing' => 'boolean',
        'inside_clsu_geofence' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tripTicket(): BelongsTo
    {
        return $this->belongsTo(TripTicket::class);
    }

    /**
     * Consider a location ping "live" if it happened in the last few minutes.
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->recorded_at && $this->recorded_at->gt(now()->subMinutes(5));
    }
}
