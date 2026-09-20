<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\TripTicket;
use App\Models\TripTicketLocation;
use App\Services\GeofenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FleetTrackingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Driver-facing endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * Record the driver's decision on the "Enable Location" prompt shown at login.
     */
    public function consent(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean'
        ]);

        $driver = $this->currentDriver();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'No driver record found.'
            ], 404);
        }

        $driver->location_sharing_enabled = $request->boolean('enabled');
        $driver->location_consent_at = now();
        $driver->save();

        if (!$driver->location_sharing_enabled) {
            DriverLocation::where('driver_id', $driver->id)
                ->update([
                    'is_sharing' => false
                ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Prompt shown to the driver at login asking them to pick the vehicle
     * they'll be driving and confirm/update its current odometer reading.
     *
     * This remains available for the existing vehicle odometer workflow.
     */
    public function submitInitialOdometer(Request $request): JsonResponse
    {
        $driver = $this->currentDriver();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'No driver record found.'
            ], 404);
        }

        if ($request->boolean('skip')) {
            return response()->json([
                'success' => true,
                'skipped' => true
            ]);
        }

        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'odometer_km' => 'required|integer|min:0',
        ]);

        $vehicle = \App\Models\Vehicle::findOrFail(
            $request->input('vehicle_id')
        );

        $newReading = (int) $request->input('odometer_km');

        /*
         * Never allow the stored vehicle mileage to move backwards.
         */
        if (
            $vehicle->mileage === null ||
            $newReading >= $vehicle->mileage
        ) {
            $vehicle->mileage = $newReading;
            $vehicle->save();
        }

        $driver->initial_odometer_prompted_at = now();
        $driver->save();

        return response()->json([
            'success' => true,
            'skipped' => false
        ]);
    }

    /**
     * Receive a GPS ping from the driver's PWA.
     *
     * TWO-GATE AUTOMATIC FLOW:
     *
     * 1. Motorpool Gate
     *      - Establishes the odometer starting baseline.
     *
     * 2. CLSU Main Gate
     *      - Automatically starts the approved trip.
     *
     * 3. During trip
     *      - GPS breadcrumbs are recorded.
     *
     * 4. CLSU Main Gate on return
     *      - Records that the vehicle has returned toward campus.
     *
     * 5. Motorpool Gate on return
     *      - Automatically completes the trip.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric',
        ]);

        $driver = $this->currentDriver();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'No driver record found.',
            ], 404);
        }

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $speed = $request->input('speed');
        $accuracy = $request->input('accuracy');
        $heading = $request->input('heading');

        /*
        |--------------------------------------------------------------------------
        | Determine current gate status
        |--------------------------------------------------------------------------
        */

        $atMotorpoolGate = GeofenceService::isNearMotorpoolGate(
            $lat,
            $lng
        );

        $atMainGate = GeofenceService::isNearMainGate(
            $lat,
            $lng
        );

        /*
        |--------------------------------------------------------------------------
        | Previous GPS state
        |--------------------------------------------------------------------------
        */

        $previousLocation = DriverLocation::where(
            'driver_id',
            $driver->id
        )->first();

        $previouslyAtMotorpoolGate = $previousLocation
            ? (bool) $previousLocation->inside_clsu_geofence
            : null;

        /*
        |--------------------------------------------------------------------------
        | Find active trip
        |--------------------------------------------------------------------------
        */

        $activeTicket = TripTicket::with('itinerary.vehicle')
            ->where('status', 'On-Going')
            ->whereHas('itinerary', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id);
            })
            ->orderByDesc('actual_departure_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Find today's approved ticket
        |--------------------------------------------------------------------------
        */

        $approvedTicket = $this->findTodaysApprovedTrip($driver);

        /*
        |--------------------------------------------------------------------------
        | Save latest driver GPS position
        |--------------------------------------------------------------------------
        |
        | The existing database field "inside_clsu_geofence" is retained
        | for compatibility.
        |
        | For automatic tracking it represents the Motorpool Gate state.
        |
        */

        DriverLocation::updateOrCreate(
            [
                'driver_id' => $driver->id
            ],
            [
                'vehicle_id' => $activeTicket?->itinerary?->vehicle_id
                    ?? $approvedTicket?->itinerary?->vehicle_id,

                'trip_ticket_id' => $activeTicket?->id,

                'latitude' => $lat,
                'longitude' => $lng,

                'accuracy_meters' => $accuracy,
                'speed_kph' => $speed,
                'heading' => $heading,

                'is_sharing' => true,

                'inside_clsu_geofence' => $atMotorpoolGate,

                'recorded_at' => now(),
            ]
        );

        $autoStarted = false;
        $autoCompleted = false;
        $odometerInitialized = false;

        $affectedTicket = null;

        /*
        |--------------------------------------------------------------------------
        | STEP 1
        | MOTORPOOL GATE -> ODOMETER START
        |--------------------------------------------------------------------------
        |
        | Being at the Motorpool Gate does NOT start the trip.
        |
        | Instead, it establishes the odometer baseline for today's
        | approved trip.
        */

        if ($atMotorpoolGate && !$activeTicket) {
            $affectedTicket = $this->initializeMotorpoolOdometer(
                $driver,
                $lat,
                $lng
            );

            if ($affectedTicket) {
                $odometerInitialized = true;
                $approvedTicket = $affectedTicket;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 2
        | MAIN GATE -> AUTOMATIC TRIP START
        |--------------------------------------------------------------------------
        |
        | The trip starts only at the CLSU Main Gate.
        |
        | The vehicle must already have passed through the Motorpool Gate
        | and obtained its odometer baseline.
        */

        if (
            !$activeTicket &&
            $atMainGate
        ) {
            $candidate = $approvedTicket
                ?: $this->findTodaysApprovedTrip($driver);

            if ($candidate) {
                $startedTicket = $this->tryAutoStartTrip(
                    $driver,
                    $candidate,
                    $lat,
                    $lng,
                    $speed
                );

                if ($startedTicket) {
                    $affectedTicket = $startedTicket;
                    $activeTicket = $startedTicket;
                    $autoStarted = true;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 3
        | GPS TRACKING DURING ACTIVE TRIP
        |--------------------------------------------------------------------------
        */

        if (
            $activeTicket &&
            $activeTicket->status === 'On-Going'
        ) {
            /*
             * Record the current GPS position.
             */
            $this->logBreadcrumb(
                $activeTicket->id,
                $lat,
                $lng,
                $speed
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 4
            | MAIN GATE RETURN
            |--------------------------------------------------------------------------
            |
            | We do NOT complete the trip at the Main Gate.
            |
            | The Main Gate only establishes that the vehicle has returned
            | through the outbound/inbound route.
            |
            | Completion happens at the Motorpool Gate.
            |--------------------------------------------------------------------------
            */

            $returnedThroughMainGate = false;

            if ($atMainGate) {
                $returnedThroughMainGate = $this->hasReturnedThroughMainGate(
                    $activeTicket
                );

                if ($returnedThroughMainGate) {
                    Log::info(
                        'Vehicle detected returning through CLSU Main Gate',
                        [
                            'trip_ticket_id' => $activeTicket->id,
                            'driver_id' => $driver->id,
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 5
            | MAIN GATE RETURN -> MOTORPOOL GATE -> COMPLETE
            |--------------------------------------------------------------------------
            */

            if (
                $atMotorpoolGate &&
                $returnedThroughMainGate &&
                $this->canAutoCompleteTrip($activeTicket)
            ) {
                $completedTicket = $this->autoCompleteTrip(
                    $activeTicket,
                    $driver,
                    $lat,
                    $lng
                );

                if ($completedTicket) {
                    $affectedTicket = $completedTicket;
                    $autoCompleted = true;
                    $activeTicket = null;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $responseTicket =
            $affectedTicket
            ?? $activeTicket
            ?? $approvedTicket;

        return response()->json([
            'success' => true,

            /*
             * Existing compatibility field.
             */
            'inside_clsu' => $atMotorpoolGate,

            /*
             * Explicit two-gate status.
             */
            'at_motorpool_gate' => $atMotorpoolGate,
            'at_main_gate' => $atMainGate,

            /*
             * Automatic tracking states.
             */
            'odometer_initialized' => $odometerInitialized,
            'auto_started' => $autoStarted,
            'auto_completed' => $autoCompleted,

            'trip_status' => $responseTicket?->status ?? null,

            'trip_ticket' => $responseTicket ? [
                'id' => $responseTicket->id,

                'ticket_number' =>
                    $responseTicket->ticket_number,

                'destination' =>
                    $responseTicket->itinerary?->destination,

                'status' =>
                    $responseTicket->status,

                'actual_departure_at' =>
                    optional(
                        $responseTicket->actual_departure_at
                    )->toIso8601String(),

                'actual_arrival_at' =>
                    optional(
                        $responseTicket->actual_arrival_at
                    )->toIso8601String(),

                'odometer_start_km' =>
                    $responseTicket->odometer_start_km,

                'odometer_end_km' =>
                    $responseTicket->odometer_end_km,

                'distance_km' =>
                    $responseTicket->odometer_distance_km,
            ] : null,

            /*
             * Gate coordinates are returned to the driver PWA
             * so the interface can display both markers.
             */
            'gates' => [
                'motorpool' => [
                    'latitude' =>
                        GeofenceService::motorpoolGateLatitude(),

                    'longitude' =>
                        GeofenceService::motorpoolGateLongitude(),

                    'radius_meters' =>
                        GeofenceService::MOTORPOOL_GATE_RADIUS_METERS,
                ],

                'main' => [
                    'latitude' =>
                        GeofenceService::mainGateLatitude(),

                    'longitude' =>
                        GeofenceService::mainGateLongitude(),

                    'radius_meters' =>
                        GeofenceService::MAIN_GATE_RADIUS_METERS,
                ],
            ],
        ]);
    }

    /**
     * Establish the odometer baseline at the Motorpool Gate.
     *
     * The vehicle's current stored mileage becomes the trip's
     * odometer_start_km.
     *
     * The trip itself does NOT start here.
     */
    private function initializeMotorpoolOdometer(
        Driver $driver,
        float $lat,
        float $lng
    ): ?TripTicket {
        $candidate = $this->findTodaysApprovedTrip($driver);

        if (!$candidate) {
            return null;
        }

        /*
         * Do not overwrite an already-established trip baseline.
         */
        if ($candidate->odometer_start_km !== null) {
            return $candidate->fresh('itinerary.vehicle');
        }

        $vehicle = $candidate->itinerary?->vehicle;

        if (!$vehicle) {
            Log::warning(
                'Unable to initialize trip odometer because no vehicle is assigned.',
                [
                    'trip_ticket_id' => $candidate->id,
                    'driver_id' => $driver->id,
                ]
            );

            return null;
        }

        if ($vehicle->mileage === null) {
            Log::warning(
                'Unable to initialize trip odometer because vehicle mileage is null.',
                [
                    'trip_ticket_id' => $candidate->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                ]
            );

            return null;
        }

        /*
         * Motorpool Gate is the official odometer-start location.
         */
        $candidate->odometer_start_km =
            (int) $vehicle->mileage;

        $candidate->save();

        Log::info(
            'Trip ticket odometer baseline initialized at Motorpool Gate.',
            [
                'trip_ticket_id' => $candidate->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'odometer_start_km' =>
                    $candidate->odometer_start_km,
                'latitude' => $lat,
                'longitude' => $lng,
            ]
        );

        return $candidate->fresh('itinerary.vehicle');
    }

    /**
     * Only log a breadcrumb point every approximately 15 seconds
     * or approximately 25 meters of movement, whichever comes first.
     */
    private function logBreadcrumb(
        int $tripTicketId,
        float $lat,
        float $lng,
        $speed
    ): void {
        $last = TripTicketLocation::where(
            'trip_ticket_id',
            $tripTicketId
        )
            ->orderByDesc('recorded_at')
            ->first();

        $shouldLog = true;

        if ($last) {
            $secondsSince = $last->recorded_at
                ? $last->recorded_at->diffInSeconds(now())
                : 999999;

            $movedMeters =
                GeofenceService::distanceKm(
                    (float) $last->latitude,
                    (float) $last->longitude,
                    $lat,
                    $lng
                ) * 1000;

            $shouldLog =
                $secondsSince >= 15 ||
                $movedMeters >= 25;
        }

        if ($shouldLog) {
            TripTicketLocation::create([
                'trip_ticket_id' => $tripTicketId,
                'latitude' => $lat,
                'longitude' => $lng,
                'speed_kph' => $speed,
                'recorded_at' => now(),
            ]);
        }
    }

    /**
     * Find today's approved trip for the current driver.
     */
    private function findTodaysApprovedTrip(
        Driver $driver
    ): ?TripTicket {
        $today = Carbon::today();

        return TripTicket::with(
            'itinerary.vehicle'
        )
            ->where('status', 'Approved')
            ->whereHas(
                'itinerary',
                function ($query) use ($driver, $today) {
                    $query
                        ->where(
                            'driver_id',
                            $driver->id
                        )
                        ->whereDate(
                            'date_from',
                            '<=',
                            $today
                        )
                        ->whereDate(
                            'date_to',
                            '>=',
                            $today
                        );
                }
            )
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Automatically start the approved trip at the CLSU Main Gate.
     *
     * Requirements:
     *
     * - Ticket must be Approved.
     * - Driver must be assigned.
     * - Odometer baseline must already have been established
     *   at the Motorpool Gate.
     *
     * The Main Gate GPS position becomes the official
     * actual_departure location.
     */
    private function tryAutoStartTrip(
        Driver $driver,
        TripTicket $candidate,
        float $lat,
        float $lng,
        $speed
    ): ?TripTicket {
        if (!$candidate) {
            return null;
        }

        if ($candidate->status !== 'Approved') {
            return null;
        }

        /*
         * Motorpool Gate must establish the odometer baseline first.
         */
        if ($candidate->odometer_start_km === null) {
            Log::info(
                'Trip start blocked: vehicle has not established its Motorpool Gate odometer baseline.',
                [
                    'trip_ticket_id' => $candidate->id,
                    'driver_id' => $driver->id,
                ]
            );

            return null;
        }

        $vehicle = $candidate->itinerary?->vehicle;

        /*
         * Main Gate is the actual trip-start location.
         */
        $candidate->status = 'On-Going';

        $candidate->actual_departure_at = now();

        $candidate->start_latitude = $lat;
        $candidate->start_longitude = $lng;

        $candidate->auto_started = true;

        $candidate->save();

        /*
         * Associate the active trip with the driver's
         * current GPS location.
         */
        DriverLocation::where(
            'driver_id',
            $driver->id
        )->update([
            'trip_ticket_id' => $candidate->id,
            'vehicle_id' => $vehicle?->id,

            /*
             * The current position is at the Main Gate,
             * not the Motorpool Gate.
             */
            'inside_clsu_geofence' => false,
        ]);

        /*
         * First breadcrumb = CLSU Main Gate.
         */
        TripTicketLocation::create([
            'trip_ticket_id' => $candidate->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'speed_kph' => $speed,
            'recorded_at' => now(),
        ]);

        Log::info(
            'Trip ticket automatically started at CLSU Main Gate.',
            [
                'trip_ticket_id' => $candidate->id,
                'driver_id' => $driver->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'odometer_start_km' =>
                    $candidate->odometer_start_km,
            ]
        );

        return $candidate->fresh(
            'itinerary.vehicle'
        );
    }

    /**
     * Determine whether the vehicle has returned through the
     * CLSU Main Gate after the trip actually started.
     *
     * This prevents the first Main Gate point used to START
     * the trip from being mistaken for the return trip.
     */
    private function hasReturnedThroughMainGate(
        TripTicket $tripTicket
    ): bool {
        if (!$tripTicket->actual_departure_at) {
            return false;
        }

        /*
         * Require at least one minute after trip departure
         * before a Main Gate point can count as a return.
         */
        $minimumReturnTime =
            $tripTicket->actual_departure_at
                ->copy()
                ->addSeconds(60);

        $points = TripTicketLocation::where(
            'trip_ticket_id',
            $tripTicket->id
        )
            ->where(
                'recorded_at',
                '>=',
                $minimumReturnTime
            )
            ->orderBy('recorded_at')
            ->get([
                'latitude',
                'longitude',
                'recorded_at'
            ]);

        foreach ($points as $point) {
            $distance = GeofenceService::distanceMeters(
                (float) $point->latitude,
                (float) $point->longitude,
                GeofenceService::mainGateLatitude(),
                GeofenceService::mainGateLongitude()
            );

            if (
                $distance <=
                GeofenceService::MAIN_GATE_RADIUS_METERS
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require a real trip before automatic completion.
     *
     * Safeguards:
     *
     * - Actual departure must exist.
     * - At least 60 seconds must have passed.
     * - Vehicle must have travelled at least 100 meters
     *   away from the trip-start point.
     */
    private function canAutoCompleteTrip(
        TripTicket $tripTicket
    ): bool {
        if (!$tripTicket->actual_departure_at) {
            return false;
        }

        /*
         * Minimum trip duration.
         */
        if (
            $tripTicket
                ->actual_departure_at
                ->diffInSeconds(now()) < 60
        ) {
            return false;
        }

        /*
         * Trip start coordinates must exist.
         */
        if (
            $tripTicket->start_latitude === null ||
            $tripTicket->start_longitude === null
        ) {
            return false;
        }

        /*
         * Find the maximum distance reached during the trip.
         */
        $maxDistanceFromStart =
            TripTicketLocation::where(
                'trip_ticket_id',
                $tripTicket->id
            )
                ->get()
                ->reduce(
                    function (
                        $max,
                        $point
                    ) use ($tripTicket) {
                        $distance =
                            GeofenceService::distanceMeters(
                                (float)
                                    $tripTicket->start_latitude,
                                (float)
                                    $tripTicket->start_longitude,
                                (float)
                                    $point->latitude,
                                (float)
                                    $point->longitude
                            );

                        return max(
                            $max,
                            $distance
                        );
                    },
                    0
                );

        return $maxDistanceFromStart >= 100;
    }

    /**
     * Automatically complete the trip when the vehicle returns
     * to the Motorpool Gate after passing through the Main Gate
     * on the return journey.
     */
    private function autoCompleteTrip(
        TripTicket $tripTicket,
        Driver $driver,
        float $lat,
        float $lng
    ): ?TripTicket {
        /*
         * Calculate total GPS distance from the complete
         * breadcrumb trail.
         */
        $distanceKm =
            $this->calculateBreadcrumbDistanceKm(
                $tripTicket->id
            );

        $odometerStart =
            $tripTicket->odometer_start_km;

        $odometerEnd = null;

        if ($odometerStart !== null) {
            $odometerEnd = (int) round(
                ((int) $odometerStart)
                + $distanceKm
            );
        }

        /*
         * Motorpool Gate is the official ending location.
         */
        $tripTicket->odometer_end_km =
            $odometerEnd;

        $tripTicket->actual_arrival_at =
            now();

        $tripTicket->end_latitude =
            $lat;

        $tripTicket->end_longitude =
            $lng;

        $tripTicket->status =
            'Completed';

        $tripTicket->save();

        /*
         * Clear the driver's active trip.
         */
        DriverLocation::where(
            'driver_id',
            $driver->id
        )->update([
            'trip_ticket_id' => null,
            'vehicle_id' => null,

            /*
             * Vehicle is back inside the Motorpool Gate.
             */
            'inside_clsu_geofence' => true,
        ]);

        Log::info(
            'Trip ticket automatically completed at Motorpool Gate.',
            [
                'trip_ticket_id' => $tripTicket->id,
                'driver_id' => $driver->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'distance_km' => $distanceKm,
                'odometer_start_km' =>
                    $odometerStart,
                'odometer_end_km' =>
                    $odometerEnd,
            ]
        );

        return $tripTicket->fresh(
            'itinerary.vehicle'
        );
    }

    /**
     * Calculate total travelled distance from the stored GPS breadcrumbs.
     */
    private function calculateBreadcrumbDistanceKm(
        int $tripTicketId
    ): float {
        $points = TripTicketLocation::where(
            'trip_ticket_id',
            $tripTicketId
        )
            ->orderBy('recorded_at')
            ->get([
                'latitude',
                'longitude'
            ]);

        if ($points->count() < 2) {
            return 0.0;
        }

        $distance = 0.0;

        $previous = $points->first();

        foreach (
            $points->slice(1) as $point
        ) {
            $distance +=
                GeofenceService::distanceKm(
                    (float) $previous->latitude,
                    (float) $previous->longitude,
                    (float) $point->latitude,
                    (float) $point->longitude
                );

            $previous = $point;
        }

        return round(
            $distance,
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Manual Endpoints
    |--------------------------------------------------------------------------
    |
    | These are kept for route/backward compatibility.
    | The driver PWA should no longer expose manual Start/Complete buttons.
    |--------------------------------------------------------------------------
    */

    /**
     * Legacy manual Start Trip endpoint.
     */
    public function startTrip(
        Request $request,
        $id
    ): JsonResponse {
        $request->validate([
            'odometer_start_km' =>
                'required|integer|min:0',

            'latitude' =>
                'nullable|numeric|between:-90,90',

            'longitude' =>
                'nullable|numeric|between:-180,180',
        ]);

        $driver = $this->currentDriver();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No driver record found.'
            ], 404);
        }

        $tripTicket = TripTicket::with(
            'itinerary'
        )
            ->whereHas(
                'itinerary',
                fn ($q) =>
                    $q->where(
                        'driver_id',
                        $driver->id
                    )
            )
            ->findOrFail($id);

        if ($tripTicket->status !== 'Approved') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only approved trip tickets can be started. Current status: '
                    . $tripTicket->status,
            ], 422);
        }

        $tripTicket->status =
            'On-Going';

        $tripTicket->odometer_start_km =
            (int)
            $request->input(
                'odometer_start_km'
            );

        $tripTicket->actual_departure_at =
            now();

        $tripTicket->auto_started =
            false;

        if (
            $request->filled('latitude') &&
            $request->filled('longitude')
        ) {
            $tripTicket->start_latitude =
                $request->input(
                    'latitude'
                );

            $tripTicket->start_longitude =
                $request->input(
                    'longitude'
                );
        }

        $tripTicket->save();

        return response()->json([
            'success' => true,
            'message' =>
                'Trip started. Drive safely!'
        ]);
    }

    /**
     * Legacy manual Complete Trip endpoint.
     */
    public function completeTrip(
        Request $request,
        $id
    ): JsonResponse {
        $request->validate([
            'odometer_end_km' =>
                'required|integer|min:0',

            'latitude' =>
                'nullable|numeric|between:-90,90',

            'longitude' =>
                'nullable|numeric|between:-180,180',
        ]);

        $driver = $this->currentDriver();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No driver record found.'
            ], 404);
        }

        $tripTicket = TripTicket::with(
            'itinerary'
        )
            ->whereHas(
                'itinerary',
                fn ($q) =>
                    $q->where(
                        'driver_id',
                        $driver->id
                    )
            )
            ->findOrFail($id);

        if ($tripTicket->status !== 'On-Going') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Only ongoing trips can be completed. Current status: '
                    . $tripTicket->status,
            ], 422);
        }

        $odometerEnd =
            (int)
            $request->input(
                'odometer_end_km'
            );

        if (
            $tripTicket->odometer_start_km !== null &&
            $odometerEnd <
                $tripTicket->odometer_start_km
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Ending odometer reading cannot be less than the starting reading ('
                    . $tripTicket->odometer_start_km
                    . ' km).',
            ], 422);
        }

        $tripTicket->odometer_end_km =
            $odometerEnd;

        $tripTicket->actual_arrival_at =
            now();

        if (
            $request->filled('latitude') &&
            $request->filled('longitude')
        ) {
            $tripTicket->end_latitude =
                $request->input(
                    'latitude'
                );

            $tripTicket->end_longitude =
                $request->input(
                    'longitude'
                );
        }

        $tripTicket->status =
            'Completed';

        $tripTicket->save();

        DriverLocation::where(
            'driver_id',
            $driver->id
        )->update([
            'trip_ticket_id' => null
        ]);

        $distance =
            $tripTicket->odometer_distance_km;

        return response()->json([
            'success' => true,
            'message' =>
                $distance !== null
                    ? "Trip completed. Distance recorded: {$distance} km"
                    : 'Trip completed.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin-facing endpoints
    |--------------------------------------------------------------------------
    */

    /**
     * The "Ongoing Trips" page:
     * a live map of every vehicle currently on the road.
     */
    public function ongoingTrips(): View
    {
        $tickets = TripTicket::with([
            'itinerary.vehicle',
            'itinerary.driver.location'
        ])
            ->where(
                'status',
                'On-Going'
            )
            ->orderByDesc(
                'actual_departure_at'
            )
            ->get();

        return view(
            'trip-tickets.ongoing-map',
            compact('tickets')
        );
    }

    /**
     * JSON feed polled by the live map
     * to refresh vehicle positions.
     */
    public function fleetLocations(): JsonResponse
    {
        $tickets = TripTicket::with([
            'itinerary.vehicle',
            'itinerary.driver.location'
        ])
            ->where(
                'status',
                'On-Going'
            )
            ->orderByDesc(
                'actual_departure_at'
            )
            ->get();

        $ongoing =
            $tickets
                ->map(
                    function (
                        TripTicket $t
                    ) {
                        $driver =
                            $t->itinerary->driver
                            ?? null;

                        $vehicle =
                            $t->itinerary->vehicle
                            ?? null;

                        $loc =
                            $driver
                            ? $driver->location
                            : null;

                        return [
                            'trip_ticket_id' =>
                                $t->id,

                            'ticket_number' =>
                                $t->ticket_number,

                            'driver_name' =>
                                $driver->full_name
                                ?? 'Unassigned',

                            'vehicle' =>
                                $vehicle
                                    ? trim(
                                        $vehicle->model
                                        . ' ('
                                        . $vehicle->plate_number
                                        . ')'
                                    )
                                    : 'N/A',

                            'destination' =>
                                $t->itinerary->destination
                                ?? 'N/A',

                            'departed_at' =>
                                optional(
                                    $t->actual_departure_at
                                )->toIso8601String(),

                            'auto_started' =>
                                (bool)
                                $t->auto_started,

                            'odometer_start_km' =>
                                $t->odometer_start_km,

                            'latitude' =>
                                $loc->latitude
                                ?? $t->start_latitude,

                            'longitude' =>
                                $loc->longitude
                                ?? $t->start_longitude,

                            'speed_kph' =>
                                $loc->speed_kph
                                ?? null,

                            'last_update' =>
                                $loc &&
                                $loc->recorded_at
                                    ? $loc->recorded_at
                                        ->toIso8601String()
                                    : null,

                            'is_live' =>
                                $loc
                                    ? $loc->is_recent
                                    : false,

                            'is_sharing' =>
                                $loc
                                    ? (bool) $loc->is_sharing
                                    : false,

                            'location_status' =>
                                !$loc
                                    ? 'no_signal'
                                    : (!(bool) $loc->is_sharing
                                        ? 'location_off'
                                        : ($loc->is_recent ? 'live' : 'signal_lost')),
                        ];
                    }
                )
                ->values();

        /*
         * Drivers sharing their location without
         * an active trip.
         */
        $idle =
            DriverLocation::with(
                'driver'
            )
                ->whereNull(
                    'trip_ticket_id'
                )
                ->where(
                    'is_sharing',
                    true
                )
                ->where(
                    'recorded_at',
                    '>=',
                    now()->subMinutes(5)
                )
                ->get()
                ->filter(
                    fn (
                        $loc
                    ) =>
                        $loc->driver
                )
                ->map(
                    function (
                        DriverLocation $loc
                    ) {
                        return [
                            'driver_id' =>
                                $loc->driver->id,

                            'driver_name' =>
                                $loc->driver->full_name,

                            'latitude' =>
                                $loc->latitude,

                            'longitude' =>
                                $loc->longitude,

                            'last_update' =>
                                optional(
                                    $loc->recorded_at
                                )->toIso8601String(),

                            'is_live' =>
                                $loc->is_recent,

                            'is_sharing' =>
                                (bool) $loc->is_sharing,

                            'location_status' =>
                                !(bool) $loc->is_sharing
                                    ? 'location_off'
                                    : ($loc->is_recent ? 'live' : 'signal_lost'),
                        ];
                    }
                )
                ->values();

        $locationAlerts = $ongoing
            ->filter(function (array $trip) {
                return in_array($trip['location_status'], ['location_off', 'signal_lost'], true);
            })
            ->map(function (array $trip) {
                $status = $trip['location_status'] === 'location_off'
                    ? 'location_off'
                    : 'signal_lost';

                return [
                    'trip_ticket_id' => $trip['trip_ticket_id'],
                    'ticket_number' => $trip['ticket_number'],
                    'driver_name' => $trip['driver_name'],
                    'vehicle' => $trip['vehicle'],
                    'last_update' => $trip['last_update'],
                    'latitude' => $trip['latitude'],
                    'longitude' => $trip['longitude'],
                    'status' => $status,
                    'message' => $status === 'location_off'
                        ? 'Location sharing was turned off by the driver.'
                        : 'No new GPS location has been received from the driver.',
                ];
            })
            ->values();

        return response()->json([
            'ongoing' =>
                $ongoing,

            'idle' =>
                $idle,

            'location_alerts' =>
                $locationAlerts,

            /*
             * Existing CLSU campus point.
             */
            'clsu' => [
                'latitude' =>
                    GeofenceService::CAMPUS_LAT,

                'longitude' =>
                    GeofenceService::CAMPUS_LNG,
            ],

            /*
             * Two official gate locations.
             */
            'gates' => [
                'motorpool' => [
                    'latitude' =>
                        GeofenceService::motorpoolGateLatitude(),

                    'longitude' =>
                        GeofenceService::motorpoolGateLongitude(),

                    'radius_meters' =>
                        GeofenceService::MOTORPOOL_GATE_RADIUS_METERS,
                ],

                'main' => [
                    'latitude' =>
                        GeofenceService::mainGateLatitude(),

                    'longitude' =>
                        GeofenceService::mainGateLongitude(),

                    'radius_meters' =>
                        GeofenceService::MAIN_GATE_RADIUS_METERS,
                ],
            ],
        ]);
    }

    /**
     * Breadcrumb trail for a single trip.
     */
    public function tripTrail(
        $id
    ): JsonResponse {
        TripTicket::findOrFail($id);

        $points =
            TripTicketLocation::where(
                'trip_ticket_id',
                $id
            )
                ->orderBy(
                    'recorded_at'
                )
                ->get([
                    'latitude',
                    'longitude',
                    'recorded_at'
                ]);

        return response()->json([
            'points' => $points
        ]);
    }

    /**
     * Get the currently authenticated driver.
     */
    private function currentDriver(): ?Driver
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return Driver::where(
            'user_id',
            $user->id
        )->first();
    }
}