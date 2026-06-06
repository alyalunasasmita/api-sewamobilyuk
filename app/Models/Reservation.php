<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;
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
        'no_reservasi',
        'reason_rejected',
        'expired_at'
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
    return $this->hasOne(Payment::class, 'reservation_id');
}
}
