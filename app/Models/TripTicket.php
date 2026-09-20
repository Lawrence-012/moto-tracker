<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\TripTicketObserver;

#[ObservedBy([TripTicketObserver::class])]
class TripTicket extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'itinerary_id',
        'ticket_number',
        'status',
        'passengers',
        'head_of_party',
        'completion_file',
        'cancellation_reason',
        'odometer_start_km',
        'odometer_end_km',
        'actual_departure_at',
        'actual_arrival_at',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'auto_started',
    ];
    
    protected $casts = [
        'status' => 'string',
        'passengers' => 'array',
        'head_of_party' => 'string',
        'odometer_start_km' => 'integer',
        'odometer_end_km' => 'integer',
        'actual_departure_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
        'start_latitude' => 'float',
        'start_longitude' => 'float',
        'end_latitude' => 'float',
        'end_longitude' => 'float',
        'auto_started' => 'boolean',
    ];
    
    /**
     * Get the Google Drive link for the completion file.
     */
    public function getCompletionFileLinkAttribute(): ?string
    {
        if ($this->completion_file) {
            return 'https://drive.google.com/file/d/' . $this->completion_file . '/view?usp=drivesdk';
        }
        
        return null;
    }
    
    /**
     * Get the itinerary associated with the trip ticket.
     */
    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }
    
    /**
     * Get the vehicle travel history records for this trip ticket.
     */
    public function travelHistory()
    {
        return $this->hasMany(VehicleTravelHistory::class, 'trip_ticket_id');
    }

    /**
     * GPS breadcrumb trail recorded while this trip was On-Going.
     */
    public function locations()
    {
        return $this->hasMany(TripTicketLocation::class);
    }

    /**
     * Distance covered so far/total, based on the odometer readings taken by the driver.
     */
    public function getOdometerDistanceKmAttribute(): ?int
    {
        if ($this->odometer_start_km === null || $this->odometer_end_km === null) {
            return null;
        }

        return max(0, $this->odometer_end_km - $this->odometer_start_km);
    }

}