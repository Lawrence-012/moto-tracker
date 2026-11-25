<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DismissedMaintenanceNotification extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_maintenance_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenance::class, 'vehicle_maintenance_id');
    }
}
