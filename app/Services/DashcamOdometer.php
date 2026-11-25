<?php

namespace App\Services;

use App\Models\Dashcam;
use Carbon\CarbonInterface;

/**
 * Measures distance from the dashcam's GPS fixes and keeps the vehicle mileage in step.
 *
 * Filters:
 *  - Fixes further apart than MAX_GAP_MINUTES are not joined (device was off or offline).
 *  - Moves under MIN_MOVE_KM are ignored (GPS jitter while parked).
 *  - Moves that imply more than MAX_SPEED_KPH are ignored (bad GPS fix).
 */
class DashcamOdometer
{
    private const EARTH_RADIUS_KM = 6371.0;
    private const MIN_MOVE_KM = 0.01;
    private const MAX_SPEED_KPH = 200.0;
    private const MAX_GAP_MINUTES = 15;

    /**
     * Adds the distance from the dashcam's last fix to this one, then stores the new fix.
     * Returns the distance added in km (0 if nothing was added).
     */
    public static function applyFix(Dashcam $dashcam, float $lat, float $lng, CarbonInterface $at): float
    {
        $added = 0.0;

        if ($dashcam->last_latitude !== null && $dashcam->last_longitude !== null && $dashcam->last_gps_at) {
            $minutes = $dashcam->last_gps_at->diffInSeconds($at) / 60;

            if ($minutes > 0 && $minutes <= self::MAX_GAP_MINUTES) {
                $km = self::haversineKm(
                    (float) $dashcam->last_latitude,
                    (float) $dashcam->last_longitude,
                    $lat,
                    $lng
                );
                $kmh = $km / ($minutes / 60);

                if ($km >= self::MIN_MOVE_KM && $kmh <= self::MAX_SPEED_KPH) {
                    $added = $km;
                }
            }
        }

        $dashcam->distance_km = round((float) $dashcam->distance_km + $added, 3);

        return $added;
    }

    /**
     * Current odometer in whole km, never less than the vehicle's stored mileage,
     * and syncs the vehicle's mileage upward when the dashcam has measured more.
     */
    public static function syncVehicleMileage(Dashcam $dashcam): int
    {
        $measured = (int) $dashcam->odometer_baseline_km + (int) floor((float) $dashcam->distance_km);

        $vehicle = $dashcam->vehicle;
        if (!$vehicle) {
            return $measured;
        }

        $current = (int) ($vehicle->mileage ?? 0);
        if ($measured > $current) {
            $vehicle->mileage = $measured;
            $vehicle->save();
            return $measured;
        }

        return $current;
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }
}
