<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripTicketLocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trip_ticket_id',
        'latitude',
        'longitude',
        'speed_kph',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed_kph' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function tripTicket(): BelongsTo
    {
        return $this->belongsTo(TripTicket::class);
    }
}
