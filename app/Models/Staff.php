<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'address',
        'salary',
        'start_date',
        'emergency_contact',
        'notes',
        'active_today',
    ];

    protected $casts = [
        'start_date' => 'date',
        'active_today' => 'boolean',
    ];
}