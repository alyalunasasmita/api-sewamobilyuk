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
        'payment_status',
        'reservations_status', 
        'refund_status',
        'cancelled_at'
    ];
}
