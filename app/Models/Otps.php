<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Otps extends Model
{
    protected $fillable = [
        'user_id',
        'OTP_code',
        'used_at',
        'exp',
        'type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
