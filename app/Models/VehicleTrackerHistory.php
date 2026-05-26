<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleTrackerHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'device_token',
        'lat',
        'lng',
        'speed',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'speed' => 'float',
    ];

    public function tracker()
{
    return $this->belongsTo(VehicleTracker::class);
}
}
