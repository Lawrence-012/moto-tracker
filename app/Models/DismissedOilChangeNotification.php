<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DismissedOilChangeNotification extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'mileage_at_dismissal',
    ];
    
    /**
     * Get the user who dismissed the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the vehicle that was dismissed.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
