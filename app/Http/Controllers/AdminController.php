<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RefundService;
use App\Services\MidtransServices;

class AdminController extends Controller
{
    public function ApproveRefund($id)
    {
        MidtransServices::init();
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
        $reservation = Reservation::with(['user', 'car', 'payment'])->find($id); 
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

    public function reservationCompleted($id){
        $resev = Reservation::find($id); 
        if (!$resev){
            return response()->json([
                'status' => 'error', 
                'message' => 'reservasi tidak ditemukan'
            ]);
        }
        $end_date = $resev->end_date; 
        $now = Carbon::parse(now()); 
        
    }
    
}
