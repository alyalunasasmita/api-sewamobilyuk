<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip_log extends Model
{
    protected $fillable = [
        'DataCar_id', 
        'reservations_id', 
        'start_time', 
        'end_time', 
        'total_distance'
    ];
}
