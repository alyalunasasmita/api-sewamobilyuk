<?php

namespace App\Services;

class DistanceService
{
    public function calculate(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {

        $earthRadius = 6371; // KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }


    public function getDeliveryFee(float $distance): int {

        if ($distance <= 3) {
            return 5000;
        }
        if ($distance <= 5) {
            return 10000;
        }
        if ($distance >= 10) {
            return 15000;
        }
        throw new \Exception(
            'Alamat di luar jangkauan pengiriman'
        );
    }

    public function calculateMeter(float $lat1,float $lon1,float $lat2,float $lon2): float {
        return $this->calculate($lat1,$lon1,$lat2,$lon2) * 1000;
    }

      
}