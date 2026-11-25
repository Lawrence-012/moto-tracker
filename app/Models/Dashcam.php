<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dashcam extends Model
{
    /** A dashcam counts as online if it checked in within this many minutes. */
    public const ONLINE_WINDOW_MINUTES = 5;

    protected $fillable = [
        'code',
        'vehicle_id',
        'registered_by',
        'first_seen_at',
        'registered_at',
        'odometer_baseline_km',
        'distance_km',
        'last_latitude',
        'last_longitude',
        'last_speed_kph',
        'last_heading',
        'last_seen_at',
        'last_gps_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'registered_at' => 'datetime',
        'odometer_baseline_km' => 'integer',
        'distance_km' => 'float',
        'last_seen_at' => 'datetime',
        'last_gps_at' => 'datetime',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_speed_kph' => 'float',
        'last_heading' => 'float',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** Current odometer in km: the registration baseline plus the distance measured since. */
    public function currentOdometerKm(): int
    {
        return (int) $this->odometer_baseline_km + (int) floor((float) $this->distance_km);
    }

    public function isRegistered(): bool
    {
        return $this->vehicle_id !== null;
    }

    /**
     * Accepts "DC-7K2M-9QXA", "dc7k2m9qxa", "DC 7K2M 9QXA" and returns the
     * canonical form, or null when the input is not a valid dashcam code.
     */
    public static function normalizeCode(?string $input): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));

        if (!preg_match('/^DC([A-Z0-9]{8})$/', $clean, $m)) {
            return null;
        }

        return 'DC-' . substr($m[1], 0, 4) . '-' . substr($m[1], 4, 4);
    }

    /** GPS counts as live if a fix arrived within this many minutes (the app sends one about every 10 seconds). */
    public const GPS_LIVE_MINUTES = 3;

    public function gpsLive(): bool
    {
        return $this->last_gps_at
            && $this->last_gps_at->gt(now()->subMinutes(self::GPS_LIVE_MINUTES));
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at
            && $this->last_seen_at->gt(now()->subMinutes(self::ONLINE_WINDOW_MINUTES));
    }
}
