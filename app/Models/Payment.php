<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 
        'reservation_id',
        'no_payment',
        'external_id',
        'invoice_id',
        'amount', //total pembayaran keseluruhan (+ pajak)
        'status', 
        'payment_method'
    ];

    function reservation(){
        return $this->belongsTo(Reservation::class, 'reservations_id');
    }
}
