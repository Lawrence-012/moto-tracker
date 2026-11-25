<?php

namespace App\Http\Controllers;

use App\Models\Dashcam;
use Carbon\Carbon;
use App\Services\VehicleTripTracker;
use App\Services\DashcamOdometer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints used by the dashcam Android app.
 * Auth: the device's own code, sent in the X-Dashcam-Code header (or "code" in the body).
 */
class DashcamApiController extends Controller
{
    /**
     * Check-in. The device sends its code. The first time, a pending dashcam is created.
     * The response says whether an admin has registered it to a vehicle yet.
     */
    public function register(Request $request): JsonResponse
    {
        $code = Dashcam::normalizeCode($request->header('X-Dashcam-Code') ?? $request->input('code'));

        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Invalid dashcam code.'], 422);
        }

        $dashcam = Dashcam::firstOrCreate(
            ['code' => $code],
            ['first_seen_at' => now()]
        );

        $dashcam->update(['last_seen_at' => now()]);
        $dashcam->load('vehicle');

        // Get the vehicle's current odometer as soon as the dashcam turns on
        if ($dashcam->isRegistered()) {
            DashcamOdometer::syncVehicleMileage($dashcam);
        }

        return response()->json([
            'success' => true,
            'registered' => $dashcam->isRegistered(),
            'odometer_km' => $dashcam->isRegistered() ? $dashcam->currentOdometerKm() : null,
            'dashcam' => [
                'code' => $dashcam->code,
                'vehicle' => $dashcam->isRegistered() ? [
                    'id' => $dashcam->vehicle->id,
                    'plate_number' => $dashcam->vehicle->plate_number,
                    'model' => $dashcam->vehicle->model,
                ] : null,
            ],
        ]);
    }

    /**
     * A batch of GPS fixes, including fixes recorded while the phone had no connection.
     * They are counted in time order, so the odometer keeps counting while offline.
     */
    public function locations(Request $request): JsonResponse
    {
        $code = Dashcam::normalizeCode($request->header('X-Dashcam-Code') ?? $request->input('code'));
        $dashcam = $code ? Dashcam::where('code', $code)->first() : null;

        if (!$dashcam) {
            return response()->json(['success' => false, 'message' => 'Unknown dashcam code.'], 404);
        }

        if (!$dashcam->isRegistered()) {
            return response()->json([
                'success' => false,
                'message' => 'Dashcam is not registered to a vehicle yet.',
            ], 409);
        }

        $data = $request->validate([
            'fixes' => 'required|array|min:1|max:100',
            'fixes.*.latitude' => 'required|numeric|between:-90,90',
            'fixes.*.longitude' => 'required|numeric|between:-180,180',
            'fixes.*.speed_kph' => 'nullable|numeric|min:0|max:400',
            'fixes.*.heading' => 'nullable|numeric|between:0,360',
            'fixes.*.recorded_at' => 'required|date',
        ]);

        $fixes = collect($data['fixes'])
            ->sortBy(fn ($f) => Carbon::parse($f['recorded_at'])->timestamp)
            ->values();

        $accepted = 0;
        foreach ($fixes as $fix) {
            // The phone sends UTC times. Convert them to the app timezone so they match the stored values.
            $at = Carbon::parse($fix['recorded_at'])->setTimezone(config('app.timezone'));

            // Skip fixes from before registration, and fixes that were already counted.
            if ($dashcam->registered_at && $at->lt($dashcam->registered_at)) {
                continue;
            }
            if ($dashcam->last_gps_at && $at->lte($dashcam->last_gps_at)) {
                continue;
            }

            $prevLat = $dashcam->last_latitude !== null ? (float) $dashcam->last_latitude : null;
            $prevLng = $dashcam->last_longitude !== null ? (float) $dashcam->last_longitude : null;

            DashcamOdometer::applyFix($dashcam, (float) $fix['latitude'], (float) $fix['longitude'], $at);

            $dashcam->fill([
                'last_latitude' => $fix['latitude'],
                'last_longitude' => $fix['longitude'],
                'last_speed_kph' => $fix['speed_kph'] ?? null,
                'last_heading' => $fix['heading'] ?? null,
                'last_gps_at' => $at,
                'last_seen_at' => now(),
            ])->save();

            VehicleTripTracker::handleFix(
                $dashcam, $prevLat, $prevLng,
                (float) $fix['latitude'], (float) $fix['longitude'], $at,
                isset($fix['speed_kph']) ? (float) $fix['speed_kph'] : null
            );

            $accepted++;
        }

        $odometer = DashcamOdometer::syncVehicleMileage($dashcam);

        return response()->json([
            'success' => true,
            'accepted' => $accepted,
            'skipped' => count($fixes) - $accepted,
            'last_gps_at' => optional($dashcam->last_gps_at)->toIso8601String(),
            'odometer_km' => $odometer,
        ]);
    }

    /**
     * Receive a GPS fix. Only accepted once the dashcam is registered to a vehicle.
     */
    public function location(Request $request): JsonResponse
    {
        $code = Dashcam::normalizeCode($request->header('X-Dashcam-Code') ?? $request->input('code'));
        $dashcam = $code ? Dashcam::where('code', $code)->first() : null;

        if (!$dashcam) {
            return response()->json(['success' => false, 'message' => 'Unknown dashcam code.'], 404);
        }

        if (!$dashcam->isRegistered()) {
            return response()->json([
                'success' => false,
                'message' => 'Dashcam is not registered to a vehicle yet.',
            ], 409);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kph' => 'nullable|numeric|min:0|max:400',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $now = now();

        $prevLat = $dashcam->last_latitude !== null ? (float) $dashcam->last_latitude : null;
        $prevLng = $dashcam->last_longitude !== null ? (float) $dashcam->last_longitude : null;

        DashcamOdometer::applyFix($dashcam, (float) $data['latitude'], (float) $data['longitude'], $now);

        $dashcam->fill([
            'last_latitude' => $data['latitude'],
            'last_longitude' => $data['longitude'],
            'last_speed_kph' => $data['speed_kph'] ?? null,
            'last_heading' => $data['heading'] ?? null,
            'last_seen_at' => $now,
            'last_gps_at' => $now,
        ])->save();

        VehicleTripTracker::handleFix(
            $dashcam, $prevLat, $prevLng,
            (float) $data['latitude'], (float) $data['longitude'], $now,
            isset($data['speed_kph']) ? (float) $data['speed_kph'] : null
        );

        $odometer = DashcamOdometer::syncVehicleMileage($dashcam);

        return response()->json([
            'success' => true,
            'odometer_km' => $odometer,
        ]);
    }

    /** Runs the trip logic for the driver of this vehicle, using a GPS fix from the dashcam. */
    private function runTripLogic(Dashcam $dashcam, array $fix): void
    {
        $driver = $this->driverForVehicle($dashcam);
        if (!$driver) {
            return;
        }

        try {
            app(\App\Http\Controllers\FleetTrackingController::class)->processGpsFix(
                $driver,
                (float) $fix['latitude'],
                (float) $fix['longitude'],
                $fix['speed_kph'] ?? null,
                null,
                $fix['heading'] ?? null
            );
        } catch (\Throwable $e) {
            \Log::warning('Trip logic from dashcam failed: '.$e->getMessage());
        }
    }

    /** The driver of this vehicle's On-Going trip, or of its approved trip (On-Going first). */
    private function driverForVehicle(Dashcam $dashcam): ?\App\Models\Driver
    {
        if (!$dashcam->vehicle_id) {
            return null;
        }

        $ticket = \App\Models\TripTicket::with('itinerary')
            ->whereIn('status', ['On-Going', 'Approved'])
            ->whereHas('itinerary', fn ($q) => $q
                ->where('vehicle_id', $dashcam->vehicle_id)
                ->whereNotNull('driver_id'))
            ->orderByRaw("CASE WHEN status = 'On-Going' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->first();

        $driverId = $ticket?->itinerary?->driver_id;

        return $driverId ? \App\Models\Driver::find($driverId) : null;
    }
}
