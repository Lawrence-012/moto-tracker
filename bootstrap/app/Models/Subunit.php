<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subunit extends Model
{
    use HasFactory;

    protected $table = 'subunits';

    protected $fillable = [
        'subunit_name',
        'subunit_abbr',
        'unit_id',
        'subunit_isactive',
    ];

    protected $casts = [
        'subunit_isactive' => 'boolean',
    ];

    public function getIdSubunitAttribute(): int
    {
        return (int) $this->id;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, EmpPosition::class, 'subunit_id', 'id', 'id', 'employee_id');
    }

    public function positions()
    {
        return $this->hasMany(EmpPosition::class, 'subunit_id');
    }
}
