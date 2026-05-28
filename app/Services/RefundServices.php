<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Midtrans\Transaction;

class RefundService
{
    public static function process(Payment $payment)
    {
        $reservation = $payment->reservation;

        $today = Carbon::today();
        $startDate = Carbon::parse($reservation->start_date);

        $daysBefore = $today->diffInDays($startDate, false);

        $percentage = 0;

        if ($daysBefore >= 3) {
            $percentage = 75;

        } elseif ($daysBefore == 2) {
            $percentage = 70;

        } elseif ($daysBefore == 1) {
            $percentage = 50;

        } else {
            throw new \Exception('refund tidak tersedia');
        }

        $refundAmount = ($payment->amount * $percentage) / 100;

        // MIDTRANS REFUND
        $refund = Transaction::refund(
            $payment->order_id,
            [
                'refund_key' => 'refund-' . time(),
                'amount' => (int) $refundAmount,
                'reason' => 'Pembatalan reservasi'
            ]
        );

        // UPDATE PAYMENT
        $payment->update([
            'status' => 'refunded'
        ]);

        // UPDATE RESERVATION
        $reservation->update([
            'refund_status' => 'refunded'
        ]);

        // UPDATE CAR
        $reservation->car->update([
            'availability_status' => 'available'
        ]);

        return [
            'refund' => $refund,
            'refund_amount' => $refundAmount
        ];
    }
}