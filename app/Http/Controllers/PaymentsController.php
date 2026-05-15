<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use Illuminate\Http\Request;
use Xendit\Xendit;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Xendit::setApiKey(env('XENDIT_SECRET_KEY')); 
        $user = $request->attributes->get('user'); 
        $reservation = Reservation::where('user_id', $user->id)->latest()->first();
        if(!$reservation){
            return response([
                'status' => 'error', 
                'message' => 'reservasi tidak ditemukan',
            ], 404);
        }

        $amount = (int) ($reservation->total * 0.10);
        $externalId = 'reservation-' . $reservation->id;
        $noPayment = 'INV-'. time() . $reservation->id;

        $params = [
            'external_id'=> $externalId, 
            'amount' =>$amount,
            'description' => 'Pembayaran reservasi Mobil berhasil', 
            'payer_email' => $user->email,

            'metadata' => [
                'user_id' => $user->id, 
                'reservation_id' => $reservation->id, 
                'no_payment' => $noPayment
            ]
        ];

        $invoice = \Xendit\Invoice::create($params); 
        Payments::create([
            'external_id'=>$externalId, 
            'amount' =>$amount,
            'user_id' => $user->id, 
            'reservation_id' => $reservation->id, 
            'no_payment' => $noPayment
        ]);

        return response()->json([
            'checkout_URL' => $invoice['invoice_url']
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function webhook(Request $request)
    {
        $data = $request->all(); 

        if($data['status'] === 'PAID'){
            $payment = Payments::where ('external_id', $data['external_id'])->first();
            if(!$payment){
                return response()->json([
                    'status' => 'error', 
                    'message' => 'data pembayaran tidak ditemukan'
                ]);
            }
            $payment->update ([
                'status' => 'paid'
            ]);
            $payment->reservation->update([
                'reservations_status' => 'paid'
            ]);
            $payment->reservations->data_cars->update([
                'availability_status' => 'booked'
            ]);

        }
        return response()->json([
            'status' => 'success'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payments $payments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payments $payments)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payments $payments)
    {
        //
    }
}
