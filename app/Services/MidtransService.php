<?php

namespace App\Services;

use Midtrans\Config;

class MidtransService
{
    public static function init()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = filter_var(
            env('MIDTRANS_IS_PRODUCTION'),
            FILTER_VALIDATE_BOOLEAN
        );
        Config::$isSanitized = true;
        Config::$is3ds = true;

        
    }
}