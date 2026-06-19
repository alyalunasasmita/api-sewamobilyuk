<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 
        'reservation_id',
        'order_id',
        'external_id',
        'snap_token',
        'invoice_id',
        'amount', 
        'status', 
        'payment_type',
        'payment_method',
        'expired_at', 
        'paid_at', 
        'tax_amount',
        'proof_payment'
    ];

    function reservation(){
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
}
