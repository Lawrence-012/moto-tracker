<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'picture',
        'plate_number',
        'model',
        'type',
        'fuel_type',
        'seating_capacity',
        'mileage',
        'last_maintenance_mileage',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seating_capacity' => 'integer',
        'mileage' => 'integer',
        'last_maintenance_mileage' => 'integer',
    ];

    /**
     * Get the itineraries for the vehicle.
     */
    public function itineraries()
    {
        return $this->hasMany(Itinerary::class);
    }
    
    /**
     * Get the travel history records for the vehicle.
     */
    public function travelHistory()
    {
        return $this->hasMany(VehicleTravelHistory::class, 'vehicle_id');
    }
    
    /**
     * Get the maintenance records for the vehicle.
     */
    public function maintenanceRecords()
    {
        return $this->hasMany(VehicleMaintenance::class, 'vehicle_id');
    }
    
    /**
     * Check if the vehicle needs maintenance based on mileage
     * Maintenance is needed every 5000 km
     */
    public function needsMaintenance(): bool
    {
        if ($this->mileage <= 0) {
            return false;
        }
        
        // Use last_maintenance_mileage from database if available
        $lastMaintenanceMileage = $this->last_maintenance_mileage ?? 0;
        
        // If no last maintenance mileage, check from maintenance records
        if ($lastMaintenanceMileage === 0) {
            $lastMaintenanceRecord = $this->maintenanceRecords()->orderBy('created_at', 'desc')->first();
            $lastMaintenanceMileage = $lastMaintenanceRecord ? ($lastMaintenanceRecord->odometer_reading ?? $this->mileage) : 0;
        }
        
        // Check if the difference between current mileage and last maintenance exceeds 5000 km
        return ($this->mileage - $lastMaintenanceMileage) >= 5000;
    }
    
    /**
     * Get the next maintenance due mileage
     */
    public function getNextMaintenanceDue(): int
    {
        // Use last_maintenance_mileage from database if available
        $lastMaintenanceMileage = $this->last_maintenance_mileage ?? 0;
        
        // If no last maintenance mileage, check from maintenance records
        if ($lastMaintenanceMileage === 0) {
            $lastMaintenanceRecord = $this->maintenanceRecords()->orderBy('created_at', 'desc')->first();
            $lastMaintenanceMileage = $lastMaintenanceRecord ? ($lastMaintenanceRecord->odometer_reading ?? 0) : 0;
        }
        
        // Return the next milestone after the last maintenance
        return floor(($lastMaintenanceMileage + 5000) / 5000) * 5000;
    }
    
    /**
     * Get the remaining mileage before next maintenance
     */
    public function getRemainingMileageForMaintenance(): int
    {
        $nextMaintenance = $this->getNextMaintenanceDue();
        $remaining = $nextMaintenance - $this->mileage;
        return max(0, $remaining);
    }
    
    /**
     * Get kilometers traveled since last maintenance
     */
    public function getKilometersSinceLastMaintenance(): int
    {
        $lastMaintenanceMileage = $this->last_maintenance_mileage ?? 0;
        return $this->mileage - $lastMaintenanceMileage;
    }
    
    /**
     * Check if vehicle has exceeded 5000 km since last maintenance
     */
    public function hasExceededMaintenanceThreshold(): bool
    {
        return $this->getKilometersSinceLastMaintenance() >= 5000;
    }
    
    /**
     * Get the picture URL for the vehicle
     */
    public function getPictureUrl(): string
    {
        if ($this->picture) {
            // Check if it's the default image
            if ($this->picture === 'vehicle_default.png') {
               return asset('vehicles/images/' . $this->picture);
            }
            // For other images, they are stored directly in public/vehicles/images/
          return asset('vehicles/images/' . $this->picture);
        }
        
        // Return default image if no picture
      return asset('vehicles/images/vehicle_default.png');
    }
    
    /**
     * Check if the vehicle has active maintenance issues
     */
    public function hasActiveMaintenanceIssues(): bool
    {
     return $this->maintenanceRecords()
            ->whereIn('status', ['Need Maintenance', 'Under Maintenance'])
            ->exists();
    }
    
    /**
     * Get the count of active maintenance issues
     */
    public function getActiveMaintenanceIssuesCount(): int
    {
     return $this->maintenanceRecords()
            ->whereIn('status', ['Need Maintenance', 'Under Maintenance'])
            ->count();
    }
    
    /**
     * Check if vehicle needs oil change (exceeded 5000 km threshold)
     */
    public function needsOilChange(): bool
    {
        return $this->hasExceededMaintenanceThreshold();
    }
    
    /**
     * Check if oil change notification has been dismissed by the current user
     * Note: Dismissal persists until vehicle mileage changes (new trip completed)
     */
    public function isOilChangeNotificationDismissed(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        // Get the dismissed notification record
        $dismissedRecord = DismissedOilChangeNotification::where('user_id', auth()->id())
            ->where('vehicle_id', $this->id)
            ->first();
        
        if (!$dismissedRecord) {
            return false; // Not dismissed
        }
        
        // Check if vehicle mileage has changed since dismissal
        $currentMileage = $this->mileage ?? 0;
        $mileageAtDismissal = $dismissedRecord->mileage_at_dismissal ?? 0;
        
        // If mileage has changed since dismissal, the dismissal is no longer valid
        if ($currentMileage !== $mileageAtDismissal) {
            // Delete the old dismissal so notification can show again if threshold exceeded
            $dismissedRecord->delete();
            return false;
        }
        
        return true; // Still dismissed (mileage hasn't changed since dismissal)
    }
    
    /**
     * Get count of vehicles needing oil change (for sidebar badge)
     */
    public static function getVehiclesNeedingOilChangeCount(): int
    {
        if (!auth()->check()) {
            return 0;
        }
        
        // Get all vehicles that need oil change
        return Vehicle::all()
            ->filter(function($vehicle) {
                // Check if vehicle needs oil change AND hasn't been dismissed by current user
                return $vehicle->needsOilChange() && !$vehicle->isOilChangeNotificationDismissed();
            })
            ->count();
    }
}