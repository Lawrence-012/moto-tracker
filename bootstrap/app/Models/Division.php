<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    protected $fillable = [
        'division_name',
        'division_abbr',
        'office_id',
        'division_code',
        'division_isactive',
    ];

    protected $casts = [
        'division_isactive' => 'boolean',
    ];

    public function getIdDivisionAttribute(): int
    {
        return (int) $this->id;
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, EmpPosition::class, 'division_id', 'id', 'id', 'employee_id');
    }

    public function positions()
    {
        return $this->hasMany(EmpPosition::class, 'division_id');
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'division_id');
    }
}
