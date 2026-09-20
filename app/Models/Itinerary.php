<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TravelOrder;

class Itinerary extends Model
{
    protected $fillable = [
        'travel_order_id', // NOW JSON - can store single ID or array of IDs
        'driver_id',
        'vehicle_id',
        'date_from',
        'date_to',
        'destination',
        'destination_latitude',
        'destination_longitude',
        'purpose',
        'departure_time',
        'status',
        'rejection_reason',
        'rejected_by_employee_id',
        'rejected_at',
        'unit_head_approved',
        'unit_head_approved_by',
        'unit_head_approved_at',
        'vp_approved',
        'vp_approved_by',
        'vp_approved_at'
    ];
    
    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        // Removed 'travel_order_id' => 'array' cast - using custom mutator instead
        'destination_latitude' => 'decimal:7',
        'destination_longitude' => 'decimal:7',
        'unit_head_approved' => 'boolean',
        'unit_head_approved_at' => 'datetime',
        'vp_approved' => 'boolean',
        'vp_approved_at' => 'datetime',
        'rejected_at' => 'datetime'
        // departure_time is a TIME field in MySQL, no specific cast needed
    ];
    
    /**
     * Get the travel_order_id attribute.
     * Decodes the JSON stored value to array.
     */
    public function getTravelOrderIdAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }
        
        // Decode JSON to get the array
        $decoded = json_decode($value, true);
        
        // Handle case where it might already be an array (legacy data)
        if (is_array($decoded)) {
            return $decoded;
        }
        
        // If it's a single value, return as array
        return [$value];
    }
    
    /**
     * Set the travel_order_id attribute.
     * Ensures proper handling of array values for JSON storage.
     */
    public function setTravelOrderIdAttribute($value)
    {
        // If it's already an array, use it directly
        if (is_array($value)) {
            // Filter out empty values
            $this->attributes['travel_order_id'] = json_encode(array_values(array_filter($value)));
        } else {
            // Single value - store as array with one element
            $this->attributes['travel_order_id'] = json_encode([$value]);
        }
    }
    
    /**
     * Get all travel orders associated with the itinerary.
     * Uses travel_order_id JSON column which can store single ID or array of IDs
     * Note: This is NOT a standard Eloquent relationship, just a helper method.
     */
    public function getAllTravelOrders()
    {
        $travelOrderIds = $this->travel_order_id ?? [];
        
        // Handle case where it's a single ID (not array yet)
        if (!is_array($travelOrderIds)) {
            $travelOrderIds = [$travelOrderIds];
        }
        
        // Filter out nulls
        $travelOrderIds = array_filter($travelOrderIds);
        
        if (empty($travelOrderIds)) {
            return collect([]);
        }
        
        return TravelOrder::whereIn('id', $travelOrderIds)->get();
    }
    
    /**
     * Get the first travel order associated with the itinerary.
     * This is an accessor (not a relationship) for backward compatibility.
     * Use travelOrders() for eager loading if needed.
     */
    public function getTravelOrderAttribute()
    {
        $travelOrderIds = $this->travel_order_id ?? [];
        
        // Handle case where it's a single ID (not array yet)
        if (!is_array($travelOrderIds)) {
            $travelOrderIds = [$travelOrderIds];
        }
        
        // Filter out nulls and empty values
        $travelOrderIds = array_filter($travelOrderIds);
        
        if (empty($travelOrderIds)) {
            return null;
        }
        
        // Return the first travel order
        return TravelOrder::find(reset($travelOrderIds));
    }
    
    /**
     * Get all travel orders associated with the itinerary (Eloquent relationship).
     * Uses travel_order_id JSON column which can store single ID or array of IDs
     */
    public function travelOrders()
    {
        $travelOrderIds = $this->travel_order_id ?? [];
        
        // Handle case where it's a single ID (not array yet)
        if (!is_array($travelOrderIds)) {
            $travelOrderIds = [$travelOrderIds];
        }
        
        // Filter out nulls
        $travelOrderIds = array_filter($travelOrderIds);
        
        if (empty($travelOrderIds)) {
            return collect([]);
        }
        
        return TravelOrder::whereIn('id', $travelOrderIds);
    }
    
    /**
     * Get the vehicle associated with the itinerary.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    
    /**
     * Get the driver assigned to the itinerary.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
    
    /**
     * Get the trip tickets associated with the itinerary.
     */
    public function tripTickets()
    {
        return $this->hasMany(TripTicket::class);
    }
    
    /**
     * Get the employee who rejected this itinerary.
     */
    public function rejectedBy()
    {
        return $this->belongsTo(Employee::class, 'rejected_by_employee_id');
    }
}