<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Midtrans\Config;
use Midtrans\Snap;


class PaymentsController extends Controller
{
    private function midtrans(){
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function store(Request $request)
    {
        $this->midtrans();
        
        $reservation = Reservation::with('user')
        ->findOrFail($request->reservation_id);
        $orderId = 'ORDER-' . time();
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int)$reservation->total_price,
            ],

            'customer_details' => [
                'first_name' => $reservation->user->name,
                'email' => $reservation->user->email,
            ],

            'expiry' => [
                'unit' => 'minutes',
                'duration' => 15
            ]
        ];
        $snapToken = Snap::getSnapToken($params);
        $payment = Payment::create([
            'user_id' => $reservation->user_id,
            'reservation_id' => $reservation->id,
            'order_id' => $orderId,
            'snap_token' => $snapToken,
            'amount' => $reservation->total_price,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(15)
        ]);
        return response()->json([
            'status' => 'success',
            'snap_token' => $snapToken,
            'payment' => $payment
        ]);
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $signatureKey = hash(
            'sha512',
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        if ($signatureKey != $request->signature_key) {
            return response()->json([
                'message' => 'invalid signature'
            ], 403);
        }

        $payment = Payment::where('no_payment', $request->order_id)->first();
        if (!$payment) {
            return response()->json([
                'message' => 'payment tidak ditemukan'
            ], 404);
        }

        $transactionStatus = $request->transaction_status;

        if ($transactionStatus == 'settlement') {
            $payment->update([
                'status' => 'success'
            ]);
            $payment->reservation->update([
                'status' => 'paid'
            ]);
            $payment->reservation->car->update([
                'availability_status' => 'booked'
            ]);

        } elseif ($transactionStatus == 'expire') {
            $payment->update([
                'status' => 'expired'
            ]);

        } elseif ($transactionStatus == 'cancel') {
            $payment->update([
                'status' => 'failed'
            ]);
        }

        return response()->json([
            'message' => 'callback berhasil'
        ]);
    }

    public function refund($id)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $payment = Payment::findOrFail($id);

        if($payment->status != 'success'){
            return response()->json([
                'status' => 'error',
                'message' => 'payment belum sukses'
            ], 400);
        }

        try {
            $reservation = $payment->reservation;

            $today = Carbon::parse(today()); 
            $start_date = carbon::parse($reservation->start_date); 
            $daysBefore = $today(diffInDays($start_date, false));
            $precentase = 0;

            if($daysBefore == 3) {
                $precentase = 75 ; 
            } else if ($daysBefore == 2 ){
                $precentase = 70;
            } else if ($daysBefore == 1){
                $precentase = 50;
            } else {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'refund tidak tersedia'
                ], 400);
            }
            $refundAmount = ($payment->amount * $precentase ) / 100;

            $refund = Transaction::refund(
                $payment->order_id,
                [
                    'refund_key' => 'refund-' . time(),
                    'amount' => $refundAmount,
                    'reason' => 'Pembatalan reservasi'
                ]
            );

            $payment->update([
                'status' => 'refunded'
            ]);

            $payment->reservation->update([
                'status' => 'cancelled'
            ]);

            $payment->reservation->car->update([
                'availability_status' => 'available'
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $refund
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}