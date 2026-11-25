<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Support\Facades\Log;

class VehicleMaintenanceObserver
{
    public function created(VehicleMaintenance $vehicleMaintenance): void
    {
        $this->updateLastMaintenanceMileage($vehicleMaintenance);
    }

    public function updated(VehicleMaintenance $vehicleMaintenance): void
    {
        // The updated event runs after persistence; wasChanged() detects the saved transition.
        if ($vehicleMaintenance->wasChanged('status') && $vehicleMaintenance->status === 'Completed') {
            $this->updateLastMaintenanceMileage($vehicleMaintenance);
        }
    }

    private function updateLastMaintenanceMileage(VehicleMaintenance $vehicleMaintenance): void
    {
        if ($vehicleMaintenance->status !== 'Completed') {
            return;
        }

        $materials = $vehicleMaintenance->materials_parts;
        $materialsText = is_array($materials) ? implode(' ', $materials) : (string) $materials;
        $combinedText = strtolower(implode(' ', array_filter([
            (string) $vehicleMaintenance->nature_of_work,
            (string) $vehicleMaintenance->make_or_type,
            $materialsText,
        ])));

        // A completed record mentioning oil (e.g. "Oil Change", "Change Engine Oil",
        // or "Engine Oil Replacement") resets the oil-service mileage baseline.
        if (preg_match('/\boil\b/i', $combinedText) !== 1) {
            return;
        }

        $vehicle = Vehicle::find($vehicleMaintenance->vehicle_id);
        if (!$vehicle) {
            return;
        }

        $serviceMileage = $vehicleMaintenance->odometer_reading;
        $vehicle->last_maintenance_mileage = $serviceMileage !== null
            ? (int) $serviceMileage
            : (int) ($vehicle->mileage ?? 0);
        $vehicle->save();

        Log::info(sprintf(
            'Vehicle %s last_maintenance_mileage reset to %d km after completed oil service (maintenance ID: %d).',
            $vehicle->plate_number,
            $vehicle->last_maintenance_mileage,
            $vehicleMaintenance->id
        ));
    }

    public function deleted(VehicleMaintenance $vehicleMaintenance): void
    {
        // Deleting a maintenance record does not modify the vehicle's mileage/status.
    }
}
