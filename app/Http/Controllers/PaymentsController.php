<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Notification;
use App\Services\ImageServices;
use Illuminate\Http\Request;

use Carbon\Carbon;


class PaymentsController extends Controller
{
    //upload bukti pembayaran 
    public function upload_bukti(
    Request $request,
    ImageServices $imageServices,
    Payment $payment
) {

    $request->validate([
        'proof_payment' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
    ]);

    if ($payment->status !== 'waiting_upload') {
        return response()->json([
            'status' => 'error',
            'message' => 'pengiriman bukti transfer sudah dilakukan'
        ], 400);
    }

    $image = $imageServices->uploadAndResize(
        $request->file('proof_payment'),
        'bukti_pembayaran'
    );

    dd([
    'image' => $image,
    'disk' => Storage::disk('public')->path($image)
]);

    $payment->update([
        'proof_payment' => $image,
        'status' => 'paid'
    ]);


    $payment->reservation->update([
        'reservations_status' => 'waiting_confirmation'
    ]);

    Notification::create([
        'user_id' => $payment->user_id,
        'title' => 'Bukti Transfer Terkirim',
        'message' => 'Bukti transfer berhasil dikirim dan sedang menunggu verifikasi admin.'
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Bukti transfer berhasil diupload',
        'payment' => $payment->fresh()
    ]);
}
}