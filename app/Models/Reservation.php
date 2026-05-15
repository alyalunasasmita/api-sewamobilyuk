<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 
        'data_car_id',
        'start_date',
        'end_date',
        'count_days',
        'total_price',
        'reservations_status', 
        'refund_status',
        'cancelled_at', 
        'no_reservasi'
    ];

    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function car()
{
    return $this->belongsTo(DataCar::class, 'data_car_id');
}

public function payment()
{
    return $this->hasOne(Payments::class);
}
}
