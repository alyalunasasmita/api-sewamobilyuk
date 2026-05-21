<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Reservation;

class AdminController extends Controller
{
    public function ApproveRefund($id){
        $payment = Payment::find($id); 
        $payment->update([
            'status' => 'refunded', 
        ]); 
        $payment->reservation->update([
            'refund_status' => 'refunded'
        ]);
        $payment->reservation->car->update([
            'availability_status' => 'available'
        ]); 
        return response()->json([
            'status' => 'success',
            'message' => 'Refund berhasil'
        ]);
    }

    public function ApproveReserv($id){
        $payment = Payment::find($id); 
        if(!$payment){
            return response()->json([
                'status' => 'error', 
                'message' => 'Customer belum melakukan pembayaran'
            ]);
        }
        $payment->reservation->car->update([
            'availability_status' => 'on rent'
        ]);
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
        
    }

    public function customerProfile(){
        $user = User::where('role','user')->get(); 
        return response()->json([
            'status' => 'success', 
            'data' => $user
        ]);
    }
    
}
