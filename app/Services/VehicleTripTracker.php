<?php

namespace App\Services;

use App\Models\Dashcam;
use App\Models\TripTicket;
use App\Models\TripTicketLocation;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Trip start and end, based on the VEHICLE's GPS (dashcam), not the driver.
 *
 *  START: the vehicle leaves the Motorpool Gate and has an approved trip for today
 *  END:   the vehicle comes back into the Motorpool Gate
 */
class VehicleTripTracker
{
    /** Minimum seconds between start and end, so gate jitter does not end a trip early. */
    const MIN_TRIP_SECONDS = 120;



    /**
     * Called for every accepted GPS fix of a vehicle, in time order.
     * $prevLat/$prevLng is the fix before this one (null for the first fix).
     */
    public static function handleFix(
        Dashcam $dashcam,
        ?float $prevLat,
        ?float $prevLng,
        float $lat,
        float $lng,
        CarbonInterface $at,
        ?float $speed = null
    ): void {
        if (!$dashcam->vehicle_id) {
            return;
        }

        $wasInside = $prevLat !== null && $prevLng !== null
            && self::insideMotorpoolGate($prevLat, $prevLng);
        $nowInside = self::insideMotorpoolGate($lat, $lng);

        $active = TripTicket::where('status', 'On-Going')
            ->whereHas('itinerary', fn ($q) => $q->where('vehicle_id', $dashcam->vehicle_id))
            ->first();

        if (!$active) {
            // START: it has just left the gate (it was inside within the last hour), with an approved trip today
            if ($wasInside && !$nowInside) {
                $started = self::start($dashcam, $lat, $lng, $at);
                if ($started) {
                    self::recordPoint($started, $lat, $lng, $speed, $at);
                }
            }
            return;
        }

        // Route history: every fix while the trip is On-Going
        self::recordPoint($active, $lat, $lng, $speed, $at);

        // END: it is back inside the gate for several fixes in a row (not just a drive past the gate)
        if (
            !$wasInside && $nowInside
            && $active->actual_departure_at
            && $active->actual_departure_at->diffInSeconds($at) >= self::MIN_TRIP_SECONDS
        ) {
            self::end($dashcam, $active, $lat, $lng, $at);
        }
    }

    /** Saves one point of the route, so the trip history can be drawn on the map. */
    private static function recordPoint(TripTicket $ticket, float $lat, float $lng, ?float $speed, CarbonInterface $at): void
    {
        TripTicketLocation::create([
            'trip_ticket_id' => $ticket->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed_kph' => $speed,
            'recorded_at' => $at,
        ]);
    }

    private static function start(Dashcam $dashcam, float $lat, float $lng, CarbonInterface $at): ?TripTicket
    {
        $today = $at->toDateString();

        $ticket = TripTicket::where('status', 'Approved')
            ->whereHas('itinerary', fn ($q) => $q
                ->where('vehicle_id', $dashcam->vehicle_id)
                ->whereDate('date_from', '<=', $today)
                ->whereDate('date_to', '>=', $today))
            ->orderBy('created_at')
            ->first();

        if (!$ticket) {
            Log::info('Vehicle left Motorpool Gate, but no approved trip today', [
                'vehicle_id' => $dashcam->vehicle_id,
            ]);
            return null;
        }

        $ticket->status = 'On-Going';
        $ticket->actual_departure_at = $at;
        $ticket->start_latitude = $lat;
        $ticket->start_longitude = $lng;
        $ticket->odometer_start_km = $dashcam->currentOdometerKm();
        $ticket->auto_started = true;
        $ticket->save();

        return $ticket;
    }

    private static function end(
        Dashcam $dashcam,
        TripTicket $ticket,
        float $lat,
        float $lng,
        CarbonInterface $at
    ): void {
        $ticket->status = 'Completed';
        $ticket->actual_arrival_at = $at;
        $ticket->end_latitude = $lat;
        $ticket->end_longitude = $lng;
        $ticket->odometer_end_km = $dashcam->currentOdometerKm();
        $ticket->save();
    }

    /** Is this point within the Motorpool Gate radius? */
    public static function insideMotorpoolGate(float $lat, float $lng): bool
    {
        $distance = self::distanceMeters(
            $lat,
            $lng,
            GeofenceService::motorpoolGateLatitude(),
            GeofenceService::motorpoolGateLongitude()
        );

        return $distance <= GeofenceService::motorpoolGateRadiusMeters();
    }

    private static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
