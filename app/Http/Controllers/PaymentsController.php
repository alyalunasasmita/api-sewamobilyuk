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

    \Log::info('MASUK CONTROLLER CALLBACK');

    return response()->json([
        'ok' => true
    ]);
    $serverKey = env('MIDTRANS_SERVER_KEY');

    if (
        $request->order_id &&
        str_starts_with($request->order_id, 'payment_notif_test_')
    ) {
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

    if ($signatureKey !== $request->signature_key) {

        \Log::warning('INVALID MIDTRANS SIGNATURE', [
            'order_id' => $request->order_id,
            'transaction_status' => $request->transaction_status
        ]);

        return response()->json([
            'message' => 'invalid signature'
        ], 403);
    }

    $payment = Payment::with('reservation')
        ->where('order_id', $request->order_id)
        ->first();

    if (!$payment) {

        \Log::warning('PAYMENT NOT FOUND', [
            'order_id' => $request->order_id
        ]);

        return response()->json([
            'message' => 'payment tidak ditemukan'
        ], 404);
    }

    try {

        \DB::beginTransaction();

        $transactionStatus = $request->transaction_status;

        switch ($transactionStatus) {

            case 'settlement':

                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'transaction_status' => $transactionStatus,
                    'payment_type' => $request->payment_type
                ]);

                if ($payment->reservation) {

                    $payment->reservation->update([
                        'reservations_status' => 'waiting_confirmation'
                    ]);

                    // Sesuaikan nama relasi
                    if ($payment->reservation->car) {
                        $payment->reservation->car->update([
                            'availability_status' => 'booked'
                        ]);
                    }
                }

                break;

            case 'expire':

                $payment->update([
                    'status' => 'expired',
                    'transaction_status' => $transactionStatus
                ]);

                if ($payment->reservation) {
                    $payment->reservation->update([
                        'reservations_status' => 'cancelled'
                    ]);
                }

                break;

            case 'cancel':
            case 'deny':

                $payment->update([
                    'status' => 'failed',
                    'transaction_status' => $transactionStatus
                ]);

                if ($payment->reservation) {
                    $payment->reservation->update([
                        'reservations_status' => 'cancelled'
                    ]);
                }

                break;

            case 'pending':

                $payment->update([
                    'status' => 'pending',
                    'transaction_status' => $transactionStatus
                ]);

                break;
        }

        \DB::commit();

        return response()->json([
            'status' => 'success'
        ]);

    } catch (\Exception $e) {

        \DB::rollBack();

        \Log::error('MIDTRANS CALLBACK ERROR', [
            'order_id' => $request->order_id,
            'message' => $e->getMessage()
        ]);

        return response()->json([
            'message' => 'internal error'
        ], 500);
    }
}
}