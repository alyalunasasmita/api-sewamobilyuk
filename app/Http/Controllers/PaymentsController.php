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
        dd(config('MIDTRANS_IS_PRODUCTION'));
        \Log::info('CALLBACK MASUK');
        \Log::info(json_encode($request->all()));
        \Log::info('ORDER ID', [
            'order_id' => $request->order_id,
        ]);
        \Log::info('STATUS', [
            'transaction_status' => $request->transaction_status,
            'payment_type' => $request->payment_type,
        ]);

        $serverKey = env('MIDTRANS_SERVER_KEY');
        if (str_starts_with($request->order_id, 'payment_notif_test_')) {
            return response()->json([
                'status' => 'success'
            ]);
        }

        $signatureKey = hash(
            'sha512',
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );
        \Log::info('SIGNATURE DEBUG', [
    'order_id' => $request->order_id,
    'status_code' => $request->status_code,
    'gross_amount' => $request->gross_amount,
    'server_key' => substr($serverKey, 0, 15),
    'generated' => $signatureKey,
    'received' => $request->signature_key,
]);

         \Log::info('SIGNATURE CHECK', [
            'generated' => $signatureKey,
            'received' => $request->signature_key,
        ]);

        // if ($signatureKey != $request->signature_key) {
        //     return response()->json([
        //         'message' => 'invalid signature'
        //     ], 403);
        // }

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
                'reservations_status' => 'waiting_confirmation'
            ]);

            $payment->reservation->car->update([
                'availability_status' => 'booked'
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