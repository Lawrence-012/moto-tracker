<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TravelOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'emp_position_id',
        'destination',
        'latitude',
        'longitude',
        'region',
        'distance_km',
        'date_from',
        'date_to',
        'departure_time',
        'purpose',
        'status',
        'rejection_reason',
        'rejected_by_employee_id',
        'rejected_at',
        'approver1_employee_id',
        'approver2_employee_id',
        'approver1_approved_at',
        'approver2_approved_at',
    ];

    protected $casts = [
        'date_from' => 'date:Y-m-d',
        'date_to' => 'date:Y-m-d',
        'approver1_approved_at' => 'datetime',
        'approver2_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected $attributes = [
        // No default approval values needed - using timestamps now
    ];

    /**
     * Get the employee that owns the travel order.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    
    /**
     * Get the position associated with the travel order.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(EmpPosition::class, 'emp_position_id');
    }
    
    /**
     * Get the itinerary associated with the travel order.
     */
    public function itinerary(): HasOne
    {
        return $this->hasOne(Itinerary::class, 'travel_order_id');
    }
    
    /**
     * Get trip tickets associated with the travel order through itinerary.
     */
    public function tripTickets()
    {
        return $this->hasManyThrough(TripTicket::class, Itinerary::class, 'travel_order_id', 'itinerary_id');
    }
    
    /**
     * Get the employee who is designated to approve this travel order (Approver 1).
     */
    public function approver1(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver1_employee_id');
    }
    
    /**
     * Get the employee who is designated to approve this travel order (Approver 2).
     */
    public function approver2(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver2_employee_id');
    }
    
    /**
     * Get the employee who rejected this travel order.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'rejected_by_employee_id');
    }
    
    /**
     * Check if the travel order has completed trip tickets.
     */
    public function getIsCompletedAttribute(): bool
    {
        if ($this->itinerary) {
            return $this->itinerary->tripTickets()
                ->where('status', 'Completed')
                ->exists();
        }
        return false;
    }

    /**
     * Get remarks based on approval status using new approver system
     */
    public function getRemarksAttribute(): string
    {
        // Check if fully approved (both approvers)
        $fullyApproved = !is_null($this->approver1_approved_at) && !is_null($this->approver2_approved_at);
        
        // If status is approved, return Approved
        if ($this->status === 'approved' && $fullyApproved) {
            return 'Approved';
        }
        
        // If cancelled or rejected
        if ($this->status === 'cancelled') {
            return 'Cancelled';
        }
        
        // Check partial approval states
        $approver1Approved = !is_null($this->approver1_approved_at);
        $approver2Approved = !is_null($this->approver2_approved_at);
        
        // If only Approver 2 has approved (waiting for Approver 1)
        if ($approver2Approved && !$approver1Approved) {
            return 'For Approver 1 Approval';
        }
        
        // If only Approver 1 has approved (shouldn't happen in normal flow, but handle it)
        if ($approver1Approved && !$approver2Approved) {
            return 'For Approver 2 Approval';
        }
        
        // If neither has approved yet
        if (!$approver1Approved && !$approver2Approved) {
            return 'Pending';
        }
        
        // Default fallback
        return 'Pending';
    }
}