<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_initial',
        'last_name',
        'full_name',
        'full_name2',
        'contact_num',
        'email',
        'password',
        'address',
        'position',
        'official_station',
        'availability_status',
        'location_sharing_enabled',
        'location_consent_at',
        'initial_odometer_prompted_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'contact_num',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'location_sharing_enabled' => 'boolean',
        'location_consent_at' => 'datetime',
        'initial_odometer_prompted_at' => 'datetime',
    ];

    /**
     * Get the user associated with this driver.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the contact number from the associated user.
     */
    public function getContactNumAttribute(): ?string
    {
        return $this->user ? $this->user->contact_num : null;
    }

    /**
     * Get the email from the associated user.
     */
    public function getEmailAttribute(): ?string
    {
        return $this->user ? $this->user->email : null;
    }

    /**
     * Get the itineraries for this driver.
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class, 'driver_id');
    }

    /**
     * Get the vehicle travel history records for this driver.
     */
    public function travelHistory(): HasMany
    {
        return $this->hasMany(VehicleTravelHistory::class, 'driver_id');
    }

    /**
     * Get this driver's latest known GPS location (tracking device feed).
     */
    public function location(): HasOne
    {
        return $this->hasOne(DriverLocation::class);
    }

    /**
     * Helper to verify if the driver currently has active location sharing enabled.
     */
    public function isSharingLocation(): bool
    {
        return (bool) $this->location_sharing_enabled;
    }

    /**
     * Get the full name of the driver.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the formal name of the driver (with middle initial).
     */
    public function getFormalNameAttribute(): string
    {
        $fullName = $this->first_name;
        if ($this->middle_initial) {
            $fullName .= ' ' . $this->middle_initial . '.';
        }
        $fullName .= ' ' . $this->last_name;
        return trim($fullName);
    }

    /**
     * Hash the password when setting it.
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}