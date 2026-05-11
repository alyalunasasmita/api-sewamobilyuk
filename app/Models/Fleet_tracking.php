<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fleet_tracking extends Model
{
    protected $fillable = [
        'DataCar_id', 
        'latitude',
        'longtitude',
        'movement',
        'speed', 
        'odometer', 
        'engine',
        'tracked_at'
    ];
}
