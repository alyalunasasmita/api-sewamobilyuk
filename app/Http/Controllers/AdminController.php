<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Notification;
use App\Models\User;
use App\Services\RefundService;
use App\Services\MidtransService;


class AdminController extends Controller
{
    public function ApproveRefund($id)
    {
        MidtransService::init();
        $reservation = Reservation::with([
            'payment',
            'car'
        ])->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ]);
        }

        $payment = $reservation->payment;

        if (!$payment || $payment->status != 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'payment tidak valid'
            ]);
        }
        try {
            $result = RefundService::process($payment);
            Notification::create([
                'user_id' => $reservation->user_id,
                'title' => 'Refund Disetujui',
                'message' => 'dana pembayaran Anda dengan nomor ' . $reservation->payment->order_id . ' telah dikembalikan .'
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'refund berhasil',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ApproveReserv($id)
    {
        $reservation = Reservation::with([
            'user',
            'car',
            'payment'
        ])->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ], 404);
        }

        $payment = $reservation->payment;
        if (!$payment || $payment->status != 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'customer belum melakukan pembayaran'
            ], 400);
        }
        $reservation->update([
            'reservations_status' => 'confirmed'
        ]);

        $reservation->car->update([
            'availability_status' => 'on rent'
        ]);

        Notification::create([
            'user_id' => $reservation->user_id,
            'title' => 'Reservasi Disetujui',
            'message' => 'Reservasi Anda telah disetujui admin.'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'reservasi berhasil diapprove',
            'data' => $reservation
        ]);
    }
        

    public function RejectReserv(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'reason' => 'required|string'
        ]);

        $reservation = Reservation::with([
            'user',
            'car',
            'payment'
        ])->find($request->id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ], 404);
        }

        $payment = $reservation->payment;

        if (!$payment || $payment->status != 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'customer belum melakukan pembayaran'
            ], 400);
        }

        try {
            $reservation->update([
                'reason_rejected' => $request->reason,
                'reservations_status' => 'cancelled',
                'refund_status' => 'pending',
                'cancelled_at' => now()
            ]);

            $reservation->car->update([
                'availability_status' => 'available'
            ]);

            MidtransService::init();
            $refundResult = RefundService::process($payment);

            return response()->json([
                'status' => 'success',
                'message' => 'reservasi ditolak dan refund berhasil',
                'refund' => $refundResult
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listReservasi()
    {
        $reservations = Reservation::with([
            'user',
            'car',
            'payment'
        ])
        ->latest()
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reservations
        ]);
    }

    public function detailReserv ($id){
        $reservation = Reservation::with(['user', 'car', 'payment'])->where('id', $id)->where('reservations_status', '!=', 'failed')->first();
        if(!$reservation){
            return response()->json([
                'status' => 'error', 
                'message' => "data reservasi tidak ditemukan"
            ]);
        }
        return response()->json([
            'status' => 'success', 
            'data' => $reservation
        ]);
    }

    public function customerProfile(){
        $user = User::where('role','customer')->get(); 
        return response()->json([
            'status' => 'success', 
            'data' => $user
        ]);
    }

    public function reservationCompleted(Request $request, $id)
    {
        $request->validate([
            'returned_at' => 'required|date'
        ]);

        $reservation = Reservation::with('car')->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ], 404);
        }

        $endDate = Carbon::parse($reservation->end_date);
        $returnedAt = Carbon::parse($request->returned_at);

        $lateDays = 0;
        $penaltyAmount = 0;

        if ($returnedAt->toDateString() > $endDate->toDateString()) {

            $lateDays = $endDate->diffInDays($returnedAt);

            $penaltyAmount = $reservation->car->price * $lateDays;
        }

        $reservation->update([
            'reservations_status' => 'completed',
            'returned_at' => $returnedAt,
            'late_days' => $lateDays,
            'penalty_amount' => $penaltyAmount
        ]);

        $reservation->car->update([
            'availability_status' => 'available'
        ]);

        Notification::create([
            'user_id' => $reservation->user_id,
            'title' => 'Reservasi Selesai',
            'message' => 'Reservasi Anda dengan nomor ' . $reservation->no_reservasi . ' telah selesai.'
        ]);

        return response()->json([
            'status' => 'success',
            'late_days' => $lateDays,
            'penalty_amount' => $penaltyAmount
        ]);
    }

    public function confirmCashPayment($id)
    {
        $payment = Payment::with('reservation')->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'payment tidak ditemukan'
            ], 404);
        }

        if ($payment->payment_method !== 'cash') {
            return response()->json([
                'status' => 'error',
                'message' => 'metode pembayaran bukan cash'
            ], 400);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'pembayaran sudah dikonfirmasi'
            ], 400);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        $payment->reservation->update([
            'reservations_status' => 'waiting_confirmation'
        ]);

        Notification::create([
            'user_id' => $reservation->user_id,
            'title' => 'Pembayaran Cash berhasil',
            'message' => 'Pembauaran Anda dengan nomor ' . $reservation->payment->order_id . ' sudah dibayar dengan metode Cash.'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'pembayaran cash berhasil dikonfirmasi'
        ]);
    }

    public function startRental($id)
    {
        $reservation = Reservation::with('car')->find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi tidak ditemukan'
            ], 404);
        }

        if ($reservation->reservations_status !== 'confirmed') {
            return response()->json([
                'status' => 'error',
                'message' => 'reservasi belum dapat dimulai'
            ], 400);
        }

        $reservation->update([
            'reservations_status' => 'on-going'
        ]);

        $reservation->car->update([
            'availability_status' => 'on-rent'
        ]);

        Notification::create([
            'user_id' => $reservation->user_id,
            'title' => 'Rental dimulai',
            'message' => 'Reservasi Anda dengan nomor ' . $reservation->no_reservasi . ' telah dimulai.'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'rental berhasil dimulai',
            'data' => $reservation->fresh(['user', 'car', 'payment'])
        ]);
    }
    
}
