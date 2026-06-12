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
    \Log::info('MIDTRANS CALLBACK', $request->all());

return response()->json([
    'status' => 'ok'
], 200);
    // \Log::info('==============================');
    // \Log::info('MASUK CONTROLLER CALLBACK');

    // \Log::info('RAW BODY', [
    //     'content' => $request->getContent()
    // ]);

    // \Log::info('REQUEST ALL', $request->all());

    // $serverKey = env('MIDTRANS_SERVER_KEY');

    // \Log::info('SEBELUM SIGNATURE');

    // $signatureKey = hash(
    //     'sha512',
    //     $request->order_id .
    //     $request->status_code .
    //     $request->gross_amount .
    //     $serverKey
    // );

    // \Log::info('SETELAH SIGNATURE', [
    //     'order_id' => $request->order_id,
    //     'transaction_status' => $request->transaction_status,
    //     'signature_request' => $request->signature_key,
    //     'signature_generated' => $signatureKey,
    // ]);

    // if ($signatureKey !== $request->signature_key) {

    //     \Log::warning('INVALID MIDTRANS SIGNATURE', [
    //         'order_id' => $request->order_id,
    //         'transaction_status' => $request->transaction_status
    //     ]);

    //     return response()->json([
    //         'message' => 'invalid signature'
    //     ], 403);
    // }

    // \Log::info('SIGNATURE VALID');

    // $payment = Payment::with('reservation')
    //     ->where('order_id', $request->order_id)
    //     ->first();

    // \Log::info('HASIL PAYMENT', [
    //     'found' => $payment ? true : false,
    //     'order_id' => $request->order_id
    // ]);

    // if (!$payment) {

    //     \Log::warning('PAYMENT NOT FOUND', [
    //         'order_id' => $request->order_id
    //     ]);

    //     return response()->json([
    //         'message' => 'payment tidak ditemukan'
    //     ], 404);
    // }

    // \Log::info('PAYMENT DITEMUKAN');

    // try {

    //     \DB::beginTransaction();

    //     $transactionStatus = $request->transaction_status;

    //     \Log::info('STATUS TRANSAKSI', [
    //         'status' => $transactionStatus
    //     ]);

    //     switch ($transactionStatus) {

    //         case 'settlement':

    //             \Log::info('MASUK SETTLEMENT');

    //             $payment->update([
    //                 'status' => 'paid',
    //                 'paid_at' => now(),
    //                 'transaction_status' => $transactionStatus,
    //                 'payment_type' => $request->payment_type
    //             ]);

    //             break;

    //         case 'expire':

    //             \Log::info('MASUK EXPIRE');

    //             $payment->update([
    //                 'status' => 'expired',
    //                 'transaction_status' => $transactionStatus
    //             ]);

    //             if ($payment->reservation) {
    //                 $payment->reservation->update([
    //                     'reservations_status' => 'cancelled', 
    //                     'expired_at' => now()
    //                 ]);
    //             }

    //             break;

    //         case 'cancel':

    //             \Log::info('MASUK CANCEL');

    //             $payment->update([
    //                 'status' => 'failed',
    //                 'transaction_status' => $transactionStatus
    //             ]);

    //             break;

    //         case 'deny':

    //             \Log::info('MASUK DENY');

    //             $payment->update([
    //                 'status' => 'failed',
    //                 'transaction_status' => $transactionStatus
    //             ]);

    //             break;

    //         case 'pending':

    //             \Log::info('MASUK PENDING');

    //             $payment->update([
    //                 'status' => 'pending',
    //                 'transaction_status' => $transactionStatus
    //             ]);

    //             break;

    //         default:

    //             \Log::warning('STATUS TIDAK DIKENAL', [
    //                 'status' => $transactionStatus
    //             ]);
    //     }

    //     \DB::commit();

    //     \Log::info('UPDATE BERHASIL');

    //     return response()->json([
    //         'status' => 'success'
    //     ]);

    // } catch (\Exception $e) {

    //     \DB::rollBack();

    //     \Log::error('MIDTRANS CALLBACK ERROR', [
    //         'message' => $e->getMessage(),
    //         'line' => $e->getLine(),
    //         'file' => $e->getFile()
    //     ]);

    //     return response()->json([
    //         'message' => 'internal error'
    //     ], 500);
    // }
}
}