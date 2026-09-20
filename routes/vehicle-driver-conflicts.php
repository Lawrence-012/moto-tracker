<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Itinerary;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Models\Driver;

/*
|--------------------------------------------------------------------------
| Vehicle Conflict Check API
|--------------------------------------------------------------------------
|
| Returns vehicles that have itinerary conflicts or are under maintenance
| for the given date range.
|
*/

Route::get('/api/vehicles/check-conflicts', function (Request $request) {
    $dateFrom = $request->query('date_from');
    $dateTo = $request->query('date_to');
    
  if (!$dateFrom || !$dateTo) {
        return response()->json([
            'conflictingVehicles' => [],
            'underMaintenance' => []
        ]);
    }
    
    // Get vehicles with itineraries in this date range
    $conflictingVehicles = Itinerary::where(function($query) use ($dateFrom, $dateTo) {
            $query->where(function($q) use ($dateFrom, $dateTo) {
                // Overlapping date ranges
                $q->where(function($q2) use ($dateFrom, $dateTo) {
                    $q2->whereDate('date_from', '<=', $dateTo)
                       ->whereDate('date_to', '>=', $dateFrom);
                });
            });
        })
        ->pluck('vehicle_id')
        ->filter()
        ->unique()
        ->values()
        ->toArray();
    
    // Get vehicles under maintenance
    $underMaintenance = VehicleMaintenance::where('status', 'Ongoing')
        ->pluck('vehicle_id')
        ->toArray();
    
    return response()->json([
        'conflictingVehicles' => $conflictingVehicles,
        'underMaintenance' => $underMaintenance
    ]);
});

/*
|--------------------------------------------------------------------------
| Driver Conflict Check API
|--------------------------------------------------------------------------
|
| Returns drivers that have itinerary conflicts for the given date range.
|
*/

Route::get('/api/drivers/check-conflicts', function (Request $request) {
    $dateFrom = $request->query('date_from');
    $dateTo = $request->query('date_to');
    
  if (!$dateFrom || !$dateTo) {
        return response()->json([
            'conflictingDrivers' => []
        ]);
    }
    
    // Get drivers with itineraries in this date range (only active drivers)
    $conflictingDrivers = Itinerary::where(function($query) use ($dateFrom, $dateTo) {
            $query->where(function($q) use ($dateFrom, $dateTo) {
                // Overlapping date ranges
                $q->where(function($q2) use ($dateFrom, $dateTo) {
                    $q2->whereDate('date_from', '<=', $dateTo)
                       ->whereDate('date_to', '>=', $dateFrom);
                });
            });
        })
        ->whereHas('driver', function($query) {
            $query->where('availability_status', 'Active');
        })
        ->pluck('driver_id')
        ->filter()
        ->unique()
        ->values()
        ->toArray();
    
    return response()->json([
        'conflictingDrivers' => $conflictingDrivers
    ]);
});
