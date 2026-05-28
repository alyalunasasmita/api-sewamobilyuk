<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Midtrans\Transaction;
use Carbon\Carbon;

use Midtrans\Config;
use Midtrans\Snap;


class PaymentsController extends Controller
{


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

        $payment = Payment::where('order_id', $request->order_id)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'payment tidak ditemukan'
            ], 404);
        }

        $transactionStatus = $request->transaction_status;

        if ($transactionStatus == 'settlement') {

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_status' => $transactionStatus,
                'payment_type' => $request->payment_type
            ]);

            $payment->reservation->update([
                'reservations_status' => 'pending'
            ]);

        } elseif ($transactionStatus == 'expire') {

            $payment->update([
                'status' => 'expired'
            ]);

            $payment->reservation->update([
                'reservations_status' => 'cancelled'
            ]);

        } elseif ($transactionStatus == 'cancel') {

            $payment->update([
                'status' => 'failed'
            ]);

            $payment->reservation->update([
                'reservations_status' => 'cancelled'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'callback berhasil'
        ]);
    }
}