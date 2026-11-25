<?php

namespace App\Observers;

use App\Models\VehicleMaintenance;
use App\Models\Vehicle;

class VehicleMaintenanceObserver
{
    /**
     * Handle the VehicleMaintenance "created" event.
     */
    public function created(VehicleMaintenance $vehicleMaintenance): void
    {
        // Check if this is a "Change Oil" or oil-related maintenance and is completed
        $this->updateLastMaintenanceMileage($vehicleMaintenance);
    }

    /**
     * Handle the VehicleMaintenance "updated" event.
     */
    public function updated(VehicleMaintenance $vehicleMaintenance): void
    {
        // Check if status was changed to "Completed" for oil-related maintenance
        if ($vehicleMaintenance->isDirty('status') && $vehicleMaintenance->status === 'Completed') {
            $this->updateLastMaintenanceMileage($vehicleMaintenance);
        }
    }

    /**
     * Handle the VehicleMaintenance "deleted" event.
     */
    public function deleted(VehicleMaintenance $vehicleMaintenance): void
    {
        // Do not update vehicle status - only update vehicle_maintenance table
        // Vehicle status updates are disabled per user requirement
    }
    
    /**
     * Update vehicle's last_maintenance_mileage if this is an oil change maintenance
     */
    private function updateLastMaintenanceMileage(VehicleMaintenance $vehicleMaintenance): void
    {
        // Check if this maintenance is specifically for "Change Oil"
        $natureOfWork = strtolower($vehicleMaintenance->nature_of_work ?? '');
        $materialsParts = is_array($vehicleMaintenance->materials_parts) 
            ? implode(' ', $vehicleMaintenance->materials_parts) 
            : ($vehicleMaintenance->materials_parts ?? '');
        $materialsParts = strtolower($materialsParts);
        
        // Combined text for checking
        $combinedText = $natureOfWork . ' ' . $materialsParts;
        
        // Strict check: Must contain "change oil" or "oil change" as specific phrases
        // This ensures we only update for actual oil changes, not other maintenance
        $isChangeOil = strpos($combinedText, 'change oil') !== false || 
                       strpos($combinedText, 'oil change') !== false;
        
        // If this is specifically a "Change Oil" maintenance and is completed
        if ($isChangeOil && $vehicleMaintenance->status === 'Completed') {
            $vehicle = Vehicle::find($vehicleMaintenance->vehicle_id);
            
            if ($vehicle) {
                // Update last_maintenance_mileage to current vehicle mileage
                $vehicle->last_maintenance_mileage = $vehicle->mileage;
                $vehicle->save();
                
                \Log::info(
                    "Vehicle {$vehicle->plate_number} last_maintenance_mileage updated to {$vehicle->mileage} km " .
                    "after 'Change Oil' maintenance (ID: {$vehicleMaintenance->id})"
                );
            }
        } else {
            \Log::debug(
                "Vehicle {$vehicleMaintenance->vehicle_id} maintenance (ID: {$vehicleMaintenance->id}) " .
                "skipped for last_maintenance_mileage update - Not a 'Change Oil' service or not completed. " .
                "Nature of work: {$natureOfWork}, Status: {$vehicleMaintenance->status}"
            );
        }
    }
}