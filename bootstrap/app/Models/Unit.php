<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'unit_name',
        'unit_abbr',
        'unit_code',
        'division_id',
        'unit_isactive',
    ];

    protected $casts = [
        'unit_isactive' => 'boolean',
    ];

    public function getUnitDivisionAttribute(): ?int
    {
        return $this->division_id;
    }

    public function setUnitDivisionAttribute($value): void
    {
        $this->attributes['division_id'] = $value;
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, EmpPosition::class, 'unit_id', 'id', 'id', 'employee_id');
    }

    public function positions()
    {
        return $this->hasMany(EmpPosition::class, 'unit_id');
    }

    public function subunits()
    {
        return $this->hasMany(Subunit::class, 'unit_id');
    }
}
