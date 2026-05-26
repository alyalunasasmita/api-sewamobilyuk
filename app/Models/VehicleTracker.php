<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VehicleTracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'car_label',
        'device_token',
        'lat',
        'lng',
        'speed',
        'accuracy',
        'battery',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'lat'          => 'float',
        'lng'          => 'float',
        'speed'        => 'float',
        'accuracy'     => 'float',
        'battery'      => 'integer',
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Tracker dianggap online jika ping terakhir < 60 detik yang lalu.
     */
    public function getIsOnlineAttribute(): bool
    {
        if (!$this->last_seen_at) return false;
        return $this->last_seen_at->diffInSeconds(now()) < 60;
    }

    protected $appends = ['is_online'];

    public function car()
    {
        return $this->belongsTo(DataCar::class);
    }

    public function histories()
    {
        return $this->hasMany(VehicleTrackerHistory::class);
    }
}
