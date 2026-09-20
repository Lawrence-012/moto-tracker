<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTravelHistory extends Model
{
    use HasFactory;
    
    protected $table = 'vehicle_travel_history';
    
    protected $fillable = [
        'trip_ticket_id',
        'vehicle_id',
        'driver_id',
        'head_of_party',
        'destination',
        'departure_date',
        'departure_time',
        'arrival_date',
        'arrival_time',
        'odometer_start_km',
        'odometer_end_km',
        'distance_km',
        'remarks',
    ];
    
    protected $casts = [
        'departure_date' => 'date',
        'arrival_date' => 'date',
        'odometer_start_km' => 'integer',
        'odometer_end_km' => 'integer',
        'distance_km' => 'decimal:2',
    ];
    
    /**
     * Get departure_time as a simple string without Carbon conversion
     */
    public function getDepartureTimeAttribute($value)
    {
        if (is_string($value)) {
            return $value;
        }
        
        if ($value instanceof \DateTime || $value instanceof \Illuminate\Support\Carbon) {
            return $value->format('H:i:s');
        }
        
        return $value;
    }
    
    /**
     * Get arrival_time as a simple string without Carbon conversion
     */
    public function getArrivalTimeAttribute($value)
    {
        if (is_string($value)) {
            return $value;
        }
        
        if ($value instanceof \DateTime || $value instanceof \Illuminate\Support\Carbon) {
            return $value->format('H:i:s');
        }
        
        return $value;
    }
    
    /**
     * Get the trip ticket associated with this travel history record.
     */
    public function tripTicket(): BelongsTo
    {
        return $this->belongsTo(TripTicket::class, 'trip_ticket_id');
    }
    
    /**
     * Get the vehicle associated with this travel history record.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    
    /**
     * Get the driver associated with this travel history record.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}