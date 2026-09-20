<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenance extends Model
{
    use HasFactory;
    
    protected $table = 'vehicle_maintenance';
    
    protected $fillable = [
        'vehicle_id',
        'date_started',
        'make_or_type',
        'person_office_unit',
        'place',
        'nature_of_work',
        'materials_parts',
        'mechanic_assigned',
        'date_completed',
        'conforme',
        'position',
        'status',
        'archived',
    ];
    
    protected $casts = [
        'date_started' => 'date',
        'date_completed' => 'date',
        'status' => 'string',
        'archived' => 'boolean',
    ];
    
    /**
     * Accessor for materials_parts to handle both string and array data
     */
    public function getMaterialsPartsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        // Handle JSON string
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Handle old string format
            return [$value];
        }
        
        return [];
    }
    
    /**
     * Accessor for mechanic_assigned to handle both string and array data
     */
    public function getMechanicAssignedAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        // Handle JSON string
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Handle old string format
            return [$value];
        }
        
        return [];
    }
    
    /**
     * Mutator for materials_parts to ensure proper JSON encoding
     */
    public function setMaterialsPartsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['materials_parts'] = json_encode(array_values(array_filter($value)));
        } else {
            $this->attributes['materials_parts'] = $value;
        }
    }
    
    /**
     * Mutator for mechanic_assigned to ensure proper JSON encoding
     */
    public function setMechanicAssignedAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['mechanic_assigned'] = json_encode(array_values(array_filter($value)));
        } else {
            $this->attributes['mechanic_assigned'] = $value;
        }
    }
    
    /**
     * Get the vehicle associated with this maintenance record.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}