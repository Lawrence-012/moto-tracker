<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'class';

    protected $fillable = [
        'class_name',
    ];

    public function getIdClassAttribute(): int
    {
        return (int) $this->id;
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'class_id');
    }
}
