<?php

namespace App\Services;

class GeofenceService
{
    /*
    |--------------------------------------------------------------------------
    | CLSU CAMPUS GEOFENCE
    |--------------------------------------------------------------------------
    */

    const CLSU_LAT = 15.734413;
    const CLSU_LNG = 120.927484;

    const CAMPUS_LAT = self::CLSU_LAT;
    const CAMPUS_LNG = self::CLSU_LNG;

    /**
     * General CLSU campus geofence radius.
     */
    const GEOFENCE_RADIUS_METERS = 1200;


    /*
    |--------------------------------------------------------------------------
    | CLSU MOTORPOOL GATE
    |--------------------------------------------------------------------------
    |
    | Purpose:
    | - Odometer Start
    | - Trip Completion / Return
    |
    | Actual coordinates:
    | Latitude  : 15.734349
    | Longitude : 120.927623
    |
    | These can also be overridden through .env.
    |
    */

    const DEFAULT_MOTORPOOL_GATE_LAT = 15.734349;
    const DEFAULT_MOTORPOOL_GATE_LNG = 120.927623;

    /**
     * GPS radius considered "at" the Motorpool Gate.
     */
    const MOTORPOOL_GATE_RADIUS_METERS = 75;


    /*
    |--------------------------------------------------------------------------
    | CLSU MAIN GATE
    |--------------------------------------------------------------------------
    |
    | Purpose:
    | - Actual Trip Start
    |
    | Coordinates provided:
    | Latitude  : 15.7300
    | Longitude : 120.9296
    |
    | These can also be overridden through .env.
    |
    */

    const DEFAULT_MAIN_GATE_LAT = 15.7300;
    const DEFAULT_MAIN_GATE_LNG = 120.9296;

    /**
     * GPS radius considered "at" the CLSU Main Gate.
     */
    const MAIN_GATE_RADIUS_METERS = 75;


    /*
    |--------------------------------------------------------------------------
    | MOTORPOOL GATE COORDINATES
    |--------------------------------------------------------------------------
    */

    public static function motorpoolGateLatitude(): float
    {
        return (float) env(
            'CLSU_MOTORPOOL_GATE_LAT',
            self::DEFAULT_MOTORPOOL_GATE_LAT
        );
    }

    public static function motorpoolGateLongitude(): float
    {
        return (float) env(
            'CLSU_MOTORPOOL_GATE_LNG',
            self::DEFAULT_MOTORPOOL_GATE_LNG
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MAIN GATE COORDINATES
    |--------------------------------------------------------------------------
    */

    public static function mainGateLatitude(): float
    {
        return (float) env(
            'CLSU_MAIN_GATE_LAT',
            self::DEFAULT_MAIN_GATE_LAT
        );
    }

    public static function mainGateLongitude(): float
    {
        return (float) env(
            'CLSU_MAIN_GATE_LNG',
            self::DEFAULT_MAIN_GATE_LNG
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HAVERSINE DISTANCE - KILOMETERS
    |--------------------------------------------------------------------------
    */

    public static function distanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadiusKm = 6371.0088;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a =
            sin($dLat / 2) ** 2
            +
            cos(deg2rad($lat1))
            *
            cos(deg2rad($lat2))
            *
            sin($dLng / 2) ** 2;

        /*
         * Protect against floating-point values slightly
         * outside the valid range of acos/atan2 calculations.
         */
        $a = min(1.0, max(0.0, $a));

        return $earthRadiusKm
            * 2
            * atan2(
                sqrt($a),
                sqrt(1 - $a)
            );
    }


    /*
    |--------------------------------------------------------------------------
    | HAVERSINE DISTANCE - METERS
    |--------------------------------------------------------------------------
    */

    public static function distanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        return self::distanceKm(
            $lat1,
            $lng1,
            $lat2,
            $lng2
        ) * 1000;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK CLSU CAMPUS GEOFENCE
    |--------------------------------------------------------------------------
    */

    public static function isInsideClsu(
        float $lat,
        float $lng
    ): bool {
        return self::distanceMeters(
            $lat,
            $lng,
            self::CLSU_LAT,
            self::CLSU_LNG
        ) <= self::GEOFENCE_RADIUS_METERS;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK MOTORPOOL GATE
    |--------------------------------------------------------------------------
    |
    | Returns TRUE when the driver's current GPS position
    | is within the Motorpool Gate radius.
    |
    | This location is used for:
    | - Odometer baseline
    | - Return detection
    | - Trip completion
    |
    */

    public static function isNearMotorpoolGate(
        float $lat,
        float $lng
    ): bool {
        $distance = self::distanceMeters(
            $lat,
            $lng,
            self::motorpoolGateLatitude(),
            self::motorpoolGateLongitude()
        );

        return $distance <= self::MOTORPOOL_GATE_RADIUS_METERS;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK MAIN GATE
    |--------------------------------------------------------------------------
    |
    | Returns TRUE when the driver's current GPS position
    | is within the CLSU Main Gate radius.
    |
    | This location is used for:
    | - Actual trip departure
    | - Trip start
    |
    */

    public static function isNearMainGate(
        float $lat,
        float $lng
    ): bool {
        $distance = self::distanceMeters(
            $lat,
            $lng,
            self::mainGateLatitude(),
            self::mainGateLongitude()
        );

        return $distance <= self::MAIN_GATE_RADIUS_METERS;
    }
}