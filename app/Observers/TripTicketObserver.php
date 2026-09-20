<?php

namespace App\Observers;

use App\Models\TripTicket;
use App\Models\VehicleTravelHistory;
use App\Services\DistanceEstimationService;

class TripTicketObserver
{
    /**
     * Track which trip tickets have already been processed
     * to prevent duplicate mileage updates
     */
    protected static $processedTripTickets = [];
    
    /**
     * Handle the TripTicket "updated" event.
     */
    public function updated(TripTicket $tripTicket): void
    {
        // Check if the status was changed to 'Completed'
        if ($tripTicket->isDirty('status') && $tripTicket->status === 'Completed') {
            // Prevent duplicate processing
            if (isset(self::$processedTripTickets[$tripTicket->id])) {
                return;
            }
            self::$processedTripTickets[$tripTicket->id] = true;
            
            $this->createVehicleTravelHistory($tripTicket);
            $this->updateVehicleMileage($tripTicket);
        }
    }
    
    /**
     * Handle the TripTicket "created" event.
     * This handles cases where a trip ticket is created directly with 'Completed' status
     * NOTE: Disabled to prevent duplicate processing with updated() event
     */
    public function created(TripTicket $tripTicket): void
    {
        // Don't process here - let updated() handle it to avoid duplicates
        // when status changes from non-Completed to Completed
    }
    
    /**
     * Create vehicle travel history record from completed trip ticket
     */
    private function createVehicleTravelHistory(TripTicket $tripTicket): void
    {
        // Load the itinerary with related models
        $tripTicket->load(['itinerary.vehicle', 'itinerary.driver']);
        
        // Check if itinerary exists
        if (!$tripTicket->itinerary) {
            return;
        }
        
        // Update itinerary status to Completed
        $tripTicket->itinerary->status = 'Completed';
        $tripTicket->itinerary->save();
        
        // Update all associated travel orders to Completed
        $travelOrderIdRaw = $tripTicket->itinerary->getAttributes()['travel_order_id'] ?? null;
        
        if ($travelOrderIdRaw) {
            // Decode JSON to get the actual value(s)
            $decoded = json_decode($travelOrderIdRaw, true);
            
            // Handle both array and single values
            $travelOrderIds = is_array($decoded) ? $decoded : [$decoded];
            
            // Filter out nulls
            $travelOrderIds = array_filter($travelOrderIds);
            
            if (!empty($travelOrderIds)) {
                // Update all travel orders to Completed
                \App\Models\TravelOrder::whereIn('id', $travelOrderIds)->update([
                    'status' => 'Completed',
                    'updated_at' => now()
                ]);
            }
        }
        
        // Check if a travel history record already exists for this trip ticket
        $existingRecord = VehicleTravelHistory::where('trip_ticket_id', $tripTicket->id)->first();
        if ($existingRecord) {
            return; // Prevent duplicate records
        }
        
        $odometerStart = $tripTicket->getAttribute('odometer_start_km');
        $odometerEnd = $tripTicket->getAttribute('odometer_end_km');
        $distance = null;

        if ($odometerStart !== null && $odometerEnd !== null) {
            $distance = max(0, (int) $odometerEnd - (int) $odometerStart);
        }

        if ($distance === null) {
            $distance = $this->estimateDistanceFromCLSU($tripTicket->itinerary->destination ?? '');
        }

        // Prepare data for vehicle travel history
        $data = [
            'trip_ticket_id' => $tripTicket->id,
            'vehicle_id' => $tripTicket->itinerary->vehicle_id,
            'driver_id' => $tripTicket->itinerary->driver_id,
            'head_of_party' => $tripTicket->head_of_party,
            'destination' => $tripTicket->itinerary->destination ?? 'N/A',
            'departure_date' => $tripTicket->itinerary->date_from ?? now(),
            'departure_time' => $tripTicket->itinerary->departure_time,
            'arrival_date' => $tripTicket->itinerary->date_to,
            'arrival_time' => $tripTicket->itinerary->departure_time, // Using departure time as default
            'odometer_start_km' => $odometerStart,
            'odometer_end_km' => $odometerEnd,
            'distance_km' => $distance,
            'remarks' => 'Auto-generated from completed trip ticket',
        ];
        
        // Create the vehicle travel history record
        VehicleTravelHistory::create($data);
    }
    
    /**
     * Estimate round-trip distance from CLSU to destination using Google Maps API
     */
    private function estimateDistanceFromCLSU(string $destination): float|null
    {
        if (empty($destination)) {
            return null;
        }
        
        // Use Google's Directions API for accurate driving distance calculation
        $distanceService = new DistanceEstimationService();
        
        // CLSU is located in Science City of Muñoz, Nueva Ecija, Philippines
        $origin = 'Central Luzon State University, Science City of Muñoz, Nueva Ecija, Philippines';
        
        // Get round-trip distance (Google Directions API handles the calculation)
        $roundTripDistance = $distanceService->getRoundTripDistance($origin, $destination);
        
        return $roundTripDistance;
    }
    
    /**
     * Update vehicle mileage when trip ticket is completed
     */
    private function updateVehicleMileage(TripTicket $tripTicket): void
    {
        // Load the itinerary with vehicle
        $tripTicket->load(['itinerary.vehicle']);
        
        // Check if vehicle exists
        if (!$tripTicket->itinerary || !$tripTicket->itinerary->vehicle) {
            return;
        }
        
        $vehicle = $tripTicket->itinerary->vehicle;
        
        // Get the travel history record we just created
        $travelHistory = VehicleTravelHistory::where('trip_ticket_id', $tripTicket->id)
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$travelHistory) {
            return;
        }

        $oldMileage = $vehicle->mileage ?? 0;
        $newMileage = $oldMileage;
        $distance = null;

        $odometerEnd = $travelHistory->odometer_end_km;
        if ($odometerEnd !== null && $odometerEnd !== '') {
            $newMileage = (int) $odometerEnd;
        } elseif ($travelHistory->distance_km !== null) {
            $distance = (float) $travelHistory->distance_km;

            // Fallback for trips without odometer readings: update mileage by adding the estimated distance traveled
            $newMileage = $oldMileage + $distance;
        } else {
            return;
        }

        $vehicle->mileage = $newMileage;
        
        \Log::info("Vehicle mileage updated:", [
            'vehicle_id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'old_mileage' => $oldMileage,
            'distance_added' => $distance,
            'new_mileage' => $newMileage,
            'calculation' => $distance !== null ? "{$oldMileage} + {$distance} = {$newMileage}" : "set directly to {$newMileage}"
        ]);
        
        // Note: last_maintenance_mileage is NOT updated here
        // It only changes when a "Change Oil" maintenance record is completed
        // This is handled in VehicleMaintenanceObserver
        
        $vehicle->save();
        
        // Clear any dismissed oil change notifications for this vehicle
        // Since mileage has changed, all previous dismissals are now invalid
        \App\Models\DismissedOilChangeNotification::where('vehicle_id', $vehicle->id)->delete();
        
        // Check if maintenance is needed and create notification/alert
        if ($vehicle->hasExceededMaintenanceThreshold()) {
            // Notification will automatically show since dismissal was cleared
            \Log::warning(
                "Vehicle {$vehicle->plate_number} has exceeded 5000 km since last maintenance. " .
                "Current mileage: {$vehicle->mileage} km, Last maintenance: {$vehicle->last_maintenance_mileage} km, " .
                "Difference: {$vehicle->getKilometersSinceLastMaintenance()} km"
            );
        }
    }
}